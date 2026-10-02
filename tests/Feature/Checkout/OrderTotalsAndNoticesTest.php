<?php

declare(strict_types=1);

use App\Domain\Checkout\Data\OrderTotals;
use App\Domain\Orders\Enums\OrderNotice;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Mail\OrderNoticeMail;
use App\Mail\StoreOrderAlertMail;
use App\Support\Money\Vat;
use Database\Seeders\SettingsSeeder;

it('adds the delivery fee unless the order reaches the free-delivery threshold', function (string $subtotal, string $shipping, string $total): void {
    $method = new ShippingMethod(['rate' => '150', 'free_shipping_threshold' => '5000']);

    $totals = OrderTotals::calculate($subtotal, $method);

    expect($totals->shipping)->toBe($shipping)->and($totals->grandTotal)->toBe($total);
})->with([
    'below threshold' => ['4999.00', '150.00', '5149.00'],
    'at threshold' => ['5000.00', '0.00', '5000.00'],
]);

it('extracts VAT from VAT-inclusive totals and rounds deposits to the halala', function (): void {
    expect(Vat::included('1150.00'))->toBe('150.00')
        ->and(Vat::percentOf('2350.00', '15'))->toBe('352.50')
        ->and(Vat::percentOf('999.99', '15'))->toBe('150.00') // 149.9985 → 150.00
        ->and(OrderTotals::calculate('1000.00', null, OrderType::DepositReservation)->deposit)->toBe('150.00')
        ->and(OrderTotals::calculate('1000.00', null, OrderType::Reservation)->deposit)->toBe('0.00');
});

it('renders every customer message in Arabic and English with the private order link', function (OrderNotice $notice, string $locale): void {
    $this->seed(SettingsSeeder::class);
    $order = Order::factory()->create([
        'type' => OrderType::DepositReservation,
        'access_token' => str_repeat('t', 40),
        'locale' => $locale,
        'reserved_until' => now()->addDays(4),
        'hold_expires_at' => now()->addDays(7),
    ]);
    $order->items()->create(['sku' => 'PS-1', 'name_ar' => 'مزهرية', 'name_en' => 'Vase', 'unit_price' => 500, 'quantity' => 1, 'line_total' => 500]);

    $html = (new OrderNoticeMail($order, $notice))->locale($locale)->render();

    expect($html)->toContain($order->number)
        ->toContain('key='.str_repeat('t', 40))
        ->toContain($locale === 'ar' ? 'dir="rtl"' : 'dir="ltr"')
        ->toContain($locale === 'ar' ? 'مزهرية' : 'Vase')
        ->toContain('info@piecenstory.com');
})->with(OrderNotice::cases())->with(['ar', 'en']);

it('renders the store alert with the customer and pickup details', function (): void {
    $order = Order::factory()->create(['customer_name' => 'نورة', 'phone' => '+966551234567', 'customer_note' => 'بعد العصر']);

    $html = (new StoreOrderAlertMail($order, OrderNotice::Confirmed))->locale('ar')->render();

    expect($html)->toContain('نورة')->toContain('0551234567')->toContain('بعد العصر');
});

it('lets a visitor who tracked their order open its page', function (): void {
    $order = Order::factory()->create(['number' => 'PS-2026-000777', 'phone' => '+966512345678', 'access_token' => str_repeat('z', 40)]);
    $order->recordStatusChange(null, $order->status);

    $this->post('/track-order', ['number' => 'PS-2026-000777', 'phone' => '0512345678'])
        ->assertOk()
        ->assertSee('/orders/PS-2026-000777?key='.str_repeat('z', 40), false);
});
