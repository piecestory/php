<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Concerns\HasWebpRenditions;
use App\Domain\Shared\Concerns\RecordsChanges;
use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;

#[Fillable([
    'auction_id', 'product_id', 'lot_number', 'title_ar', 'title_en', 'description_ar', 'description_en',
    'starting_price', 'estimate_low', 'estimate_high',
])]
class AuctionLot extends Model implements HasMedia
{
    use HasTranslations, HasWebpRenditions, RecordsChanges;

    public const string MEDIA_GALLERY = 'gallery';

    /** WebP renditions (name => width) used for srcset; smallest first. */
    public const array IMAGE_SIZES = ['card' => 800, 'large' => 1600];

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
        $this->addMediaCollection(self::MEDIA_GALLERY)->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * The lot's own photo, or the linked catalogue piece's main photo.
     *
     * @return array{src: string, srcset: ?string, alt: string}|null
     */
    public function coverImage(): ?array
    {
        return $this->responsiveImage(self::MEDIA_GALLERY)
            ?? $this->product?->responsiveImage(Product::MEDIA_GALLERY);
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
