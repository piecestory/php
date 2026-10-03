<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Inventory\Enums\InventoryReason;
use App\Domain\Notifications\Sms\SmsGateway;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\RefundStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\SalesOverview;
use App\Mail\OrderNoticeMail;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\ShippingMethodSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\FakeSmsGateway;

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, ShippingMethodSeeder::class, BranchSeeder::class, SettingsSeeder::class]);
    Mail::fake();
    $this->app->instance(SmsGateway::class, $this->sms = new FakeSmsGateway);
    $this->staff = User::factory()->create(['name' => 'موظف المعرض'])->assignRole(Role::CustomerService->value);
});

/** Places an order as a customer, then signs the staff member in. */
function placedOrder(OrderType $type, array $product = [], array $form = []): App\Domain\Orders\Models\Order
{
    $order = reserve(Product::factory()->published()->create(['price' => 1000, ...$product]), $type, $form);
    test()->actingAs(test()->staff);

    return $order;
}

it('finds orders by the customer\'s mobile as staff type it', function (): void {
    $order = placedOrder(OrderType::Reservation, form: ['payment_method' => null]);

    Livewire::test(ListOrders::class)->searchTable('0551234567')->assertCanSeeTableRecords([$order]);
    Livewire::test(ListOrders::class)->searchTable('0500000000')->assertCanNotSeeTableRecords([$order]);
});

it('records a showroom payment for a reservation, confirming the order', function (): void {
    $order = placedOrder(OrderType::Reservation, form: ['payment_method' => null]);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertActionVisible('recordPayment')
        ->callAction('recordPayment', ['note' => 'شبكة 4421'])
        ->assertHasNoActionErrors();

    $payment = $order->payments()->sole();
    expect($order->refresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($payment->method)->toBe(PaymentMethod::InStore)
        ->and($payment->amount)->toBe('1000.00')
        ->and($payment->metadata)->toMatchArray(['recorded_by' => $this->staff->id, 'note' => 'شبكة 4421'])
        ->and($order->stockReservations()->count())->toBe(0);
    Mail::assertQueued(OrderNoticeMail::class);
});

it('takes a deposit in the showroom for a deposit reservation', function (): void {
    $order = placedOrder(OrderType::DepositReservation);

    Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('recordPayment');

    expect($order->refresh()->status)->toBe(OrderStatus::Reserved)
        ->and($order->amount_paid)->toBe('150.00')
        ->and($order->balanceDue())->toBe('850.00');
});

it('walks a pickup order from paid to handed over', function (): void {
    $order = placedOrder(OrderType::Reservation, form: ['payment_method' => null]);
    $page = Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('recordPayment');

    $page->assertActionHidden('ship')->callAction('startPreparing');
    expect($order->refresh()->status)->toBe(OrderStatus::Processing);

    Livewire::test(ViewOrder::class, ['record' => $order->id])->assertActionHidden('ship')->callAction('delivered');
    expect($order->refresh()->status)->toBe(OrderStatus::Delivered)
        ->and($order->statusChanges()->latest('id')->first()->changed_by)->toBe($this->staff->id);
});

it('ships delivery orders with a tracking number and tells the customer', function (): void {
    App\Domain\Shipping\Models\ShippingMethod::query()->where('code', 'delivery')->update(['is_active' => true, 'rate' => 35]);
    $order = placedOrder(OrderType::Reservation, form: [
        'payment_method' => null,
        'shipping_method' => App\Domain\Shipping\Models\ShippingMethod::query()->where('code', 'delivery')->value('id'),
        'address' => ['city' => 'جدة', 'district' => 'الروضة', 'street' => 'شارع الأمير سلطان', 'building_number' => '2929', 'postal_code' => '23435'],
    ]);
    Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('recordPayment')->callAction('startPreparing');
    $this->sms->sent = [];

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->callAction('ship', ['carrier' => 'سمسا', 'tracking_number' => 'SM123456', 'tracking_url' => 'https://www.smsaexpress.com/track'])
        ->assertHasNoActionErrors();

    expect($order->refresh()->status)->toBe(OrderStatus::Shipped)
        ->and($order->shipments()->sole()->tracking_number)->toBe('SM123456')
        ->and($this->sms->sent[0]['message'])->toContain('SM123456');

    Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('delivered');
    expect($order->refresh()->status)->toBe(OrderStatus::Delivered)
        ->and($order->shipments()->sole()->delivered_at)->not->toBeNull();
});

it('cancels a paid order: the piece returns to stock and the showroom payment awaits refund', function (): void {
    $order = placedOrder(OrderType::Reservation, form: ['payment_method' => null]);
    Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('recordPayment');
    $product = Product::query()->find($order->items()->value('product_id'));
    expect($product->stock_quantity)->toBe(0);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->callAction('cancel', ['reason' => 'طلب العميل'])
        ->assertHasNoActionErrors();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->refresh()->stock_quantity)->toBe(1)
        ->and($product->inventoryMovements()->latest('id')->first()->reason)->toBe(InventoryReason::Cancellation)
        ->and($order->payments()->sole()->refunds()->sole()->status)->toBe(RefundStatus::Pending);
});

it('never offers editing, deleting or creating orders by hand', function (): void {
    $order = placedOrder(OrderType::Reservation, form: ['payment_method' => null]);

    $this->get("/admin/orders/{$order->id}")->assertOk()->assertSee($order->number);
    $this->get("/admin/orders/{$order->id}/edit")->assertNotFound();
    $this->get('/admin/orders/create')->assertNotFound();
});

it('shows the sales overview and latest orders on the dashboard', function (): void {
    $order = placedOrder(OrderType::Reservation, form: ['payment_method' => null]);

    Livewire::test(SalesOverview::class)->assertOk()->assertSee(__('admin.dashboard.orders_today'));
    Livewire::test(LatestOrders::class)->assertCanSeeTableRecords([$order]);
});
