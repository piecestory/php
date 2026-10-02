<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use Illuminate\Support\Facades\DB;

it('starts new orders as pending and unpaid', function (): void {
    $order = Order::factory()->create()->fresh();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Unpaid);
});

it('keeps order lines intact when the product is permanently deleted', function (): void {
    $product = Product::factory()->create(['name_ar' => 'ساعة', 'sku' => 'PS-KEEP']);
    $order = Order::factory()->create();
    $item = $order->items()->create([
        'product_id' => $product->id, 'sku' => $product->sku, 'name_ar' => $product->name_ar,
        'name_en' => $product->name_en, 'unit_price' => '1000.00', 'quantity' => 1, 'line_total' => '1000.00',
    ]);

    $product->forceDelete();

    expect($item->fresh())
        ->product_id->toBeNull()
        ->sku->toBe('PS-KEEP')
        ->name_ar->toBe('ساعة');
});

it('records status history with the acting user under a stable morph alias', function (): void {
    $admin = User::factory()->create();
    $order = Order::factory()->create();

    $order->recordStatusChange(OrderStatus::Pending, OrderStatus::Confirmed, $admin->id, 'تم التأكيد هاتفيًا');

    $change = $order->statusChanges()->sole();
    expect($change)
        ->from_status->toBe('pending')
        ->to_status->toBe('confirmed')
        ->changed_by->toBe($admin->id)
        ->and(DB::table('status_changes')->value('subject_type'))->toBe('order');
});

it('keeps the order when the customer account is deleted', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $user->delete();

    expect($order->fresh())->not->toBeNull()->user_id->toBeNull();
});
