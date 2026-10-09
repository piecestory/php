<?php

declare(strict_types=1);

use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Models\User;
use App\Domain\Inventory\Enums\InventoryReason;
use App\Domain\Inventory\Models\StockReservation;
use App\Domain\Notifications\Sms\SmsGateway;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Domain\Store\Models\Branch;
use App\Http\Support\CurrentCart;
use App\Mail\OrderNoticeMail;
use App\Mail\StoreOrderAlertMail;
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

it('sends visitors with an empty cart back to the cart page', function (): void {
    $this->get('/checkout')->assertRedirect('/cart');
});

it('shows pickup showrooms, order types and the offered payment methods', function (): void {
    $product = Product::factory()->published()->create(['price' => 1000]);

    $this->withCookie(CurrentCart::COOKIE, checkoutCart($product))
        ->get('/checkout')
        ->assertOk()
        ->assertSee('معرض البوادي')
        ->assertSee('معرض الحرازات')
        ->assertDontSee('مستودع الخمرة')
        ->assertSee('شراء الآن')
        ->assertSee('حجز بعربون 15%')
        ->assertSee('حجز بدون دفع')
        ->assertSee('مدى')
        ->assertSee('Apple Pay')
        ->assertSee('تابي')
        ->assertSee('تمارا')
        ->assertDontSee('بطاقة ائتمانية')
        // Delivery stays hidden until the owner prices it.
        ->assertDontSee('name="shipping_method" value="'.ShippingMethod::query()->where('code', ShippingMethod::DELIVERY)->value('id').'"', false);
});

it('places a guest order, holds the piece and sends the customer to the payment page', function (): void {
    $product = Product::factory()->published()->create(['price' => 1150, 'stock_quantity' => 1, 'name_ar' => 'ساعة جيب']);

    $response = $this->withCookie(CurrentCart::COOKIE, checkoutCart($product))->post('/checkout', checkoutForm());

    $order = Order::query()->sole();
    $payment = $order->payments()->sole();
    $response->assertRedirect(route('payments.sandbox', ['reference' => $payment->provider_reference]));

    expect($order->number)->toBe(sprintf('PS-%s-%06d', now()->format('Y'), $order->id))
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->phone)->toBe('+966551234567')
        ->and($order->grand_total)->toBe('1150.00')
        ->and($order->tax_total)->toBe('150.00')
        ->and($order->hold_expires_at->diffInMinutes(now(), true))->toBeGreaterThan(14.0)
        ->and($order->items()->sole()->name_ar)->toBe('ساعة جيب')
        ->and($payment->amount)->toBe('1150.00')
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and(StockReservation::query()->where('order_id', $order->id)->exists())->toBeTrue()
        ->and($product->inventoryMovements()->sole()->reason)->toBe(InventoryReason::Hold)
        ->and(Cart::query()->sole()->items()->count())->toBe(0);
});

it('confirms the order once the provider reports a successful payment', function (): void {
    $product = Product::factory()->published()->create(['price' => 1150, 'stock_quantity' => 1]);
    $this->withCookie(CurrentCart::COOKIE, checkoutCart($product))->post('/checkout', checkoutForm());
    $order = Order::query()->sole();
    $reference = $order->payments()->sole()->provider_reference;

    // The tester approves on the sandbox page, which sends the customer back to the shop.
    $back = $this->post(route('payments.sandbox.decide', ['reference' => $reference]), ['outcome' => 'approve']);
    $this->get($back->headers->get('Location'))
        ->assertRedirect(localized_route('order', ['order' => $order->number, 'key' => $order->access_token]))
        ->assertSessionMissing('error');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($order->amount_paid)->toBe('1150.00')
        ->and($order->hold_expires_at)->toBeNull()
        ->and($order->payments()->sole()->status)->toBe(PaymentStatus::Captured)
        ->and($order->stockReservations()->count())->toBe(0)
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($order->statusChanges()->pluck('to_status')->all())->toBe(['confirmed', 'pending']);

    Mail::assertQueued(OrderNoticeMail::class, fn (OrderNoticeMail $mail) => $mail->hasTo('sara@example.com'));
    Mail::assertQueued(StoreOrderAlertMail::class, fn (StoreOrderAlertMail $mail) => $mail->hasTo('info@php.piecenstory.com'));
    expect($this->sms->sent)->toHaveCount(1)
        ->and($this->sms->sent[0]['phone'])->toBe('+966551234567')
        ->and($this->sms->sent[0]['message'])->toContain($order->number);
});

it('keeps the order open after a declined payment so the customer can try again', function (): void {
    $product = Product::factory()->published()->create(['price' => 500]);
    $this->withCookie(CurrentCart::COOKIE, checkoutCart($product))->post('/checkout', checkoutForm());
    $order = Order::query()->sole();

    $back = $this->post(route('payments.sandbox.decide', ['reference' => $order->payments()->sole()->provider_reference]), ['outcome' => 'decline']);
    $this->get($back->headers->get('Location'))->assertSessionHas('error', __('payments.flash.failed'));

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Failed);

    $page = localized_route('order', ['order' => $order->number, 'key' => $order->access_token]);
    $this->get($page)->assertOk()->assertSee(__('orders.page.pay_button', ['amount' => '500 ر.س']));

    $this->post("/orders/{$order->number}/pay", ['key' => $order->access_token, 'payment_method' => 'apple_pay'])
        ->assertRedirectContains('/payments/sandbox/');
    expect($order->payments()->count())->toBe(2);
});

it('validates the form according to the choices made', function (array $input, string $field): void {
    $product = Product::factory()->published()->create();
    if (($input['pickup_branch'] ?? null) === 'warehouse') {
        $input['pickup_branch'] = Branch::query()->where('is_pickup_point', false)->value('id');
    }

    $this->withCookie(CurrentCart::COOKIE, checkoutCart($product))
        ->from('/checkout')
        ->post('/checkout', checkoutForm($input))
        ->assertRedirect('/checkout')
        ->assertSessionHasErrors($field);

    expect(Order::query()->count())->toBe(0);
})->with([
    'missing mobile' => [['phone' => ''], 'phone'],
    'non-Saudi mobile' => [['phone' => '0123'], 'phone'],
    'bad email' => [['email' => 'not-an-email'], 'email'],
    'no showroom for pickup' => [['pickup_branch' => ''], 'pickup_branch'],
    'warehouse is not a pickup point' => [['pickup_branch' => 'warehouse'], 'pickup_branch'],
    'no payment method for a purchase' => [['payment_method' => ''], 'payment_method'],
    'credit cards are not offered' => [['payment_method' => 'credit_card'], 'payment_method'],
    'instalments cannot pay a deposit' => [['order_type' => 'deposit_reservation', 'payment_method' => 'tabby'], 'payment_method'],
    'unknown order type' => [['order_type' => 'layaway'], 'order_type'],
]);

it('requires the national address for delivery once the owner enables it', function (): void {
    ShippingMethod::query()->where('code', ShippingMethod::DELIVERY)->update(['is_active' => true, 'rate' => 35]);
    $product = Product::factory()->published()->create(['price' => 120]); // under the 150 SAR free-delivery threshold
    $delivery = ShippingMethod::query()->where('code', ShippingMethod::DELIVERY)->value('id');
    $token = checkoutCart($product);

    $this->withCookie(CurrentCart::COOKIE, $token)->from('/checkout')
        ->post('/checkout', checkoutForm(['shipping_method' => $delivery, 'pickup_branch' => null]))
        ->assertSessionHasErrors(['address.city', 'address.district', 'address.street', 'address.building_number', 'address.postal_code']);

    $this->withCookie(CurrentCart::COOKIE, $token)->post('/checkout', checkoutForm([
        'shipping_method' => $delivery,
        'address' => ['city' => 'جدة', 'district' => 'الروضة', 'street' => 'شارع الأمير سلطان', 'building_number' => '٢٩٢٩', 'postal_code' => '23435'],
    ]))->assertRedirectContains('/payments/sandbox/');

    $order = Order::query()->sole();
    expect($order->shipping_total)->toBe('35.00')
        ->and($order->grand_total)->toBe('155.00')
        ->and($order->ship_building_number)->toBe('2929') // Arabic digits normalised
        ->and($order->pickup_branch_id)->toBeNull();
});

it('delivers orders over 150 SAR for free', function (): void {
    ShippingMethod::query()->where('code', ShippingMethod::DELIVERY)->update(['is_active' => true, 'rate' => 35]);
    $product = Product::factory()->published()->create(['price' => 1000]);

    $token = checkoutCart($product);
    $this->withCookie(CurrentCart::COOKIE, $token)->get('/checkout')->assertSee('مجاني للطلبات فوق 150 ر.س');

    $this->withCookie(CurrentCart::COOKIE, $token)->post('/checkout', checkoutForm([
        'shipping_method' => ShippingMethod::query()->where('code', ShippingMethod::DELIVERY)->value('id'),
        'address' => ['city' => 'جدة', 'district' => 'الروضة', 'street' => 'شارع الأمير سلطان', 'building_number' => '2929', 'postal_code' => '23435'],
    ]));

    expect(Order::query()->sole()->shipping_total)->toBe('0.00')->and(Order::query()->sole()->grand_total)->toBe('1000.00');
});

it('refuses a piece someone else bought meanwhile, without creating an order', function (): void {
    $product = Product::factory()->published()->create(['stock_quantity' => 1]);
    $token = checkoutCart($product);
    $product->update(['stock_quantity' => 0]);

    $this->withCookie(CurrentCart::COOKIE, $token)->from('/checkout')
        ->post('/checkout', checkoutForm())
        ->assertRedirect('/checkout')
        ->assertSessionHas('error', __('checkout.errors.unavailable', ['name' => $product->name_ar]));

    expect(Order::query()->count())->toBe(0);
});

it('links signed-in customers to their order and prefills their details', function (): void {
    $user = User::factory()->create(['name' => 'خالد', 'phone' => '+966501112233', 'email' => 'k@example.com']);
    $product = Product::factory()->published()->create();
    $cart = Cart::query()->create(['user_id' => $user->id, 'token' => str_repeat('a', 40)]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)->get('/checkout')->assertOk()->assertSee('value="0501112233"', false)->assertSee('value="k@example.com"', false);
    $this->actingAs($user)->post('/checkout', checkoutForm(['phone' => '0501112233']));

    $order = Order::query()->sole();
    expect($order->user_id)->toBe($user->id);
    $this->actingAs($user)->get("/orders/{$order->number}")->assertOk(); // the owner needs no key
});

it('hides order pages from anyone without the key', function (): void {
    $order = Order::factory()->create(['access_token' => str_repeat('k', 40)]);

    $this->get("/orders/{$order->number}")->assertNotFound();
    $this->get("/orders/{$order->number}?key=wrong")->assertNotFound();
    $this->get("/orders/{$order->number}?key=".str_repeat('k', 40))->assertOk()->assertSee($order->number);
    $this->actingAs(User::factory()->create())->get("/orders/{$order->number}")->assertNotFound();
});

it('does not let a payment return link be used for someone else\'s order', function (): void {
    $product = Product::factory()->published()->create();
    $this->withCookie(CurrentCart::COOKIE, checkoutCart($product))->post('/checkout', checkoutForm());
    $payment = Order::query()->sole()->payments()->sole();

    $this->get("/payments/{$payment->id}/return?key=guess")->assertNotFound();
});

it('serves the English checkout and keeps the customer in English after payment', function (): void {
    $product = Product::factory()->published()->create(['price' => 800]);
    $token = checkoutCart($product);

    $this->withCookie(CurrentCart::COOKIE, $token)->get('/en/checkout')->assertOk()->assertSee('Buy now')->assertSee('Reserve with a 15% deposit');
    $this->withCookie(CurrentCart::COOKIE, $token)->post('/en/checkout', checkoutForm());

    $order = Order::query()->sole();
    expect($order->locale)->toBe('en');

    $back = $this->post(route('payments.sandbox.decide', ['reference' => $order->payments()->sole()->provider_reference]), ['outcome' => 'approve']);
    expect($back->headers->get('Location'))->toContain('/en/payments/');
    $this->get($back->headers->get('Location'))->assertRedirectContains("/en/orders/{$order->number}");
});
