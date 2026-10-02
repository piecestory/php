<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Models\User;
use App\Domain\Inventory\Enums\InventoryReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Append-only ledger of stock changes. */
#[Fillable(['product_id', 'quantity_change', 'stock_after', 'reason', 'reference_type', 'reference_id', 'user_id', 'note'])]
class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'stock_after' => 'integer',
            'reason' => InventoryReason::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
