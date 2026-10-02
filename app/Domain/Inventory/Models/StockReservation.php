<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Models\Order;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Holds stock while a customer completes payment; expired rows are released by the scheduler. */
#[Fillable(['product_id', 'order_id', 'quantity', 'expires_at'])]
class StockReservation extends Model
{
    protected function casts(): array
    {
        return ['quantity' => 'integer', 'expires_at' => 'datetime'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
