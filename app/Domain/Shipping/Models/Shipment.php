<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Models;

use App\Domain\Orders\Models\Order;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['carrier', 'tracking_number', 'tracking_url', 'shipped_at', 'delivered_at'])]
class Shipment extends Model
{
    protected function casts(): array
    {
        return ['shipped_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
