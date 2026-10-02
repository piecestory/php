<?php

declare(strict_types=1);

namespace App\Domain\Orders\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Shared\Concerns\HasStatusHistory;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Domain\Store\Models\Branch;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Status changes go through an Action that validates the transition and records history;
 * `status` is deliberately not mass assignable.
 */
#[Fillable([
    'number', 'user_id', 'customer_name', 'phone', 'email', 'locale', 'currency',
    'subtotal', 'discount_total', 'shipping_total', 'tax_total', 'grand_total',
    'shipping_method_id', 'pickup_branch_id',
    'ship_recipient_name', 'ship_phone', 'ship_city', 'ship_district', 'ship_street',
    'ship_building_number', 'ship_postal_code', 'ship_additional_number', 'ship_short_address',
    'customer_note', 'placed_at',
])]
#[UseFactory(OrderFactory::class)]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasStatusHistory;

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => OrderPaymentStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'placed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Shipment, $this> */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /** @return BelongsTo<ShippingMethod, $this> */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function pickupBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'pickup_branch_id');
    }
}
