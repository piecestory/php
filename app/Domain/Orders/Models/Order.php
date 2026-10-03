<?php

declare(strict_types=1);

namespace App\Domain\Orders\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Inventory\Models\StockReservation;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentPurpose;
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
 * `status`, `payment_status` and `amount_paid` are deliberately not mass assignable.
 */
#[Fillable([
    'number', 'type', 'access_token', 'user_id', 'customer_name', 'phone', 'email', 'locale', 'currency',
    'subtotal', 'discount_total', 'shipping_total', 'tax_total', 'grand_total', 'deposit_total', 'payment_method',
    'shipping_method_id', 'pickup_branch_id',
    'ship_recipient_name', 'ship_phone', 'ship_city', 'ship_district', 'ship_street',
    'ship_building_number', 'ship_postal_code', 'ship_additional_number', 'ship_short_address',
    'customer_note', 'placed_at', 'hold_expires_at', 'reserved_until',
])]
#[UseFactory(OrderFactory::class)]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasStatusHistory;

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'type' => 'purchase',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => OrderPaymentStatus::class,
            'type' => OrderType::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'deposit_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'placed_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'reserved_until' => 'datetime',
            'reminded_at' => 'datetime',
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

    /** @return HasMany<StockReservation, $this> */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /** What is still owed: the total less everything paid so far. */
    public function balanceDue(): string
    {
        return bcsub((string) $this->grand_total, (string) $this->amount_paid, 2);
    }

    /** The customer can pay online now: the order is open, its pieces are still held and something is owed. */
    public function awaitsPayment(): bool
    {
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::Reserved], true)
            && $this->hold_expires_at?->isFuture() === true
            && bccomp($this->balanceDue(), '0', 2) > 0;
    }

    /** Pending deposit reservations pay the deposit first; everything else pays what is left. */
    public function nextPaymentAmount(): string
    {
        return $this->nextPaymentPurpose() === PaymentPurpose::Deposit
            ? (string) $this->deposit_total
            : $this->balanceDue();
    }

    public function nextPaymentPurpose(): PaymentPurpose
    {
        return match (true) {
            $this->type === OrderType::DepositReservation && $this->status === OrderStatus::Pending => PaymentPurpose::Deposit,
            bccomp((string) $this->amount_paid, '0', 2) > 0 => PaymentPurpose::Balance,
            default => PaymentPurpose::Full,
        };
    }
}
