<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Enums\InventoryReason;
use App\Domain\Notifications\Sms\SmsGateway;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentPurpose;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\RefundStatus;
use App\Http\Support\CurrentCart;
use App\Mail\OrderNoticeMail;
use Database\Seeders\BranchSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\ShippingMethodSeeder;
use Illuminate\Support\Facades\Mail;
use Tests\Support\FakeSmsGateway;

beforeEach(function (): void {
    $this->seed([ShippingMethodSeeder::class, BranchSeeder::class, SettingsSeeder::class]);
    Mail::fake();
    $this->app->instance(SmsGateway::class, $this->sms = new FakeSmsGateway);
});

it('reserves a piece without payment for 4 days plus 3 reminder days', function (): void {
    $product = Product::factory()->published()->create(['price' => 2000]);

    $order = reserve($product, OrderType::Reservation, ['payment_method' => null]);

    expect($order->status)->toBe(OrderStatus::Reserved)
        ->and($order->payments()->count())->toBe(0)
        ->and($order->reserved_until->diffInHours(now()->addDays(4), true))->toBeLessThan(1.0)
        ->and($order->hold_expires_at->diffInHours(now()->addDays(7), true))->toBeLessThan(1.0)
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($this->sms->sent[0]['message'])->toContain($order->number);
    Mail::assertQueued(OrderNoticeMail::class);

    // The piece now shows as reserved, not sold, and cannot be added to another cart.
    $this->get("/product/{$product->slug_ar}")->assertSee('محجوزة حاليًا')->assertDontSee('مُباعة');
    $this->postJson("/cart/items/{$product->id}")->assertStatus(422);
});

it('takes a 15% deposit, then reserves the piece and lets the customer pay the balance', function (): void {
    $product = Product::factory()->published()->create(['price' => 2350]);

    $order = reserve($product, OrderType::DepositReservation);
    $deposit = $order->payments()->sole();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->deposit_total)->toBe('352.50')
        ->and($deposit->purpose)->toBe(PaymentPurpose::Deposit)
        ->and($deposit->amount)->toBe('352.50');

    approveLatestPayment($order);
    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Reserved)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::DepositPaid)
        ->and($order->amount_paid)->toBe('352.50')
        ->and($order->balanceDue())->toBe('1997.50')
        ->and($order->hold_expires_at->diffInHours(now()->addDays(7), true))->toBeLessThan(1.0)
        ->and($order->stockReservations()->sole()->expires_at->equalTo($order->hold_expires_at))->toBeTrue();

    // Balance: Tabby is fine for the rest of the amount.
    $this->post("/orders/{$order->number}/pay", ['key' => $order->access_token, 'payment_method' => 'tabby'])->assertRedirectContains('/payments/sandbox/');
    expect($order->payments()->latest('id')->first()->amount)->toBe('1997.50');
    approveLatestPayment($order);

    expect($order->refresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->amount_paid)->toBe('2350.00')
        ->and($order->stockReservations()->count())->toBe(0);
});

it('reminds the customer once a day after the reservation period, until the final date', function (): void {
    $order = reserve(Product::factory()->published()->create(), OrderType::Reservation, ['payment_method' => null]);
    $this->sms->sent = [];

    $this->travel(3)->days();
    $this->artisan('orders:remind-reservations')->assertSuccessful();
    expect($this->sms->sent)->toBeEmpty(); // still within the reservation period

    $this->travel(1)->days();
    $this->travel(1)->hours();
    $this->artisan('orders:remind-reservations');
    $this->artisan('orders:remind-reservations'); // a second run the same day sends nothing more
    expect($this->sms->sent)->toHaveCount(1)
        ->and($this->sms->sent[0]['message'])->toContain('تذكير');

    $this->travel(1)->days();
    $this->artisan('orders:remind-reservations');
    expect($this->sms->sent)->toHaveCount(2)
        ->and($order->refresh()->status)->toBe(OrderStatus::Reserved);
});

it('cancels unpaid reservations after the final date and puts the piece back on sale', function (): void {
    $product = Product::factory()->published()->create();
    $order = reserve($product, OrderType::Reservation, ['payment_method' => null]);
    $this->sms->sent = [];

    $this->travel(7)->days();
    $this->travel(1)->minutes();
    $this->artisan('orders:expire-holds')->assertSuccessful();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->refresh()->stock_quantity)->toBe(1)
        ->and($product->inventoryMovements()->latest('id')->first()->reason)->toBe(InventoryReason::HoldReleased)
        ->and($order->stockReservations()->count())->toBe(0)
        ->and($this->sms->sent[0]['message'])->toContain('أُلغي');
});

it('refunds the deposit in full when a reservation is cancelled', function (): void {
    $order = reserve(Product::factory()->published()->create(['price' => 1000]), OrderType::DepositReservation);
    approveLatestPayment($order);

    $this->travel(8)->days();
    $this->artisan('orders:expire-holds');

    $deposit = $order->payments()->sole();
    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Refunded)
        ->and($deposit->status)->toBe(PaymentStatus::Refunded)
        ->and($deposit->refunds()->sole()->amount)->toBe('150.00')
        ->and($deposit->refunds()->sole()->status)->toBe(RefundStatus::Completed);
});

it('releases pieces held by checkouts that were never paid', function (): void {
    $product = Product::factory()->published()->create();
    $order = reserve($product, OrderType::Purchase);

    $this->travel(10)->minutes();
    $this->artisan('orders:expire-holds');
    expect($order->refresh()->status)->toBe(OrderStatus::Pending); // still within the payment window

    $this->travel(6)->minutes();
    $this->artisan('orders:expire-holds');
    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->refresh()->stock_quantity)->toBe(1)
        ->and($this->sms->sent)->toBeEmpty(); // abandoned checkouts are not messaged
});

it('refunds a payment that arrives after the order expired', function (): void {
    $order = reserve(Product::factory()->published()->create(['price' => 700]), OrderType::Purchase);

    $this->travel(16)->minutes();
    $this->artisan('orders:expire-holds');
    $reference = $order->payments()->sole()->provider_reference;
    $back = $this->post(route('payments.sandbox.decide', ['reference' => $reference]), ['outcome' => 'approve']);
    $this->get($back->headers->get('Location'))->assertSessionHas('error', __('payments.flash.refunded'));

    $payment = $order->payments()->sole();
    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($payment->status)->toBe(PaymentStatus::Refunded)
        ->and($payment->refunds()->sole()->amount)->toBe('700.00');
});

it('limits open reservations per mobile number', function (): void {
    config(['store.reservation.max_open_per_phone' => 2]);
    reserve(Product::factory()->published()->create(), OrderType::Reservation, ['payment_method' => null]);
    reserve(Product::factory()->published()->create(), OrderType::Reservation, ['payment_method' => null]);

    $this->withCookie(CurrentCart::COOKIE, checkoutCart(Product::factory()->published()->create()))
        ->from('/checkout')
        ->post('/checkout', checkoutForm(['order_type' => 'reservation', 'phone' => '+966 55 123 4567']))
        ->assertSessionHas('error', __('checkout.errors.too_many_reservations', ['limit' => 2]));

    expect(Order::query()->count())->toBe(2);
});

it('offers only reservation without deposit when no online payment is configured', function (): void {
    config(['payments.sandbox.enabled' => false]);
    $this->app->forgetInstance(App\Domain\Payments\PaymentGateways::class);
    $product = Product::factory()->published()->create();
    $token = checkoutCart($product);

    $this->withCookie(CurrentCart::COOKIE, $token)->get('/checkout')
        ->assertOk()
        ->assertSee(__('checkout.no_online_payment'))
        ->assertDontSee('value="purchase"', false)
        ->assertSee('value="reservation"', false);

    $this->withCookie(CurrentCart::COOKIE, $token)->from('/checkout')
        ->post('/checkout', checkoutForm())
        ->assertSessionHasErrors('order_type');
});
