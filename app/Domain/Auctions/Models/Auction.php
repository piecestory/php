<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Models;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Support\Localization\HasTranslations;
use Database\Factories\AuctionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Showcase-only in v1: auctions and lots are displayed and visitors register interest.
 * Bidding (bids, settlement) is intentionally not modelled yet.
 */
#[Fillable([
    'title_ar', 'title_en', 'slug_ar', 'slug_en', 'description_ar', 'description_en', 'starts_at', 'ends_at', 'status',
])]
#[UseFactory(AuctionFactory::class)]
class Auction extends Model implements HasMedia
{
    /** @use HasFactory<AuctionFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    public const string MEDIA_COVER = 'cover';

    protected function casts(): array
    {
        return [
            'status' => AuctionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COVER)->singleFile();
    }

    /** @return HasMany<AuctionLot, $this> */
    public function lots(): HasMany
    {
        return $this->hasMany(AuctionLot::class)->orderBy('lot_number');
    }

    /** @return HasMany<AuctionInterest, $this> */
    public function interests(): HasMany
    {
        return $this->hasMany(AuctionInterest::class);
    }
}
