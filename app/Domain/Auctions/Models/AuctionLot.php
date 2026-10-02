<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Models;

use App\Domain\Catalog\Models\Product;
use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'auction_id', 'product_id', 'lot_number', 'title_ar', 'title_en', 'description_ar', 'description_en',
    'starting_price', 'estimate_low', 'estimate_high',
])]
class AuctionLot extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    public const string MEDIA_GALLERY = 'gallery';

    protected function casts(): array
    {
        return [
            'lot_number' => 'integer',
            'starting_price' => 'decimal:2',
            'estimate_low' => 'decimal:2',
            'estimate_high' => 'decimal:2',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_GALLERY);
    }

    /** @return BelongsTo<Auction, $this> */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
