<?php

declare(strict_types=1);

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Models\Product;
use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A snapshot of the product at the time of purchase; stays valid if the product is later removed. */
#[Fillable(['product_id', 'sku', 'name_ar', 'name_en', 'unit_price', 'quantity', 'line_total'])]
class OrderItem extends Model
{
    use HasTranslations;

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
