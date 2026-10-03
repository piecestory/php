<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Models;

use App\Domain\Auctions\Enums\AuctionPhase;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Content\ContentAvailability;
use App\Domain\Shared\Concerns\HasWebpRenditions;
use App\Domain\Shared\Concerns\RecordsChanges;
use App\Support\Localization\HasLocalizedSlug;
use App\Support\Localization\HasTranslations;
use Database\Factories\AuctionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

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
    use HasFactory, HasLocalizedSlug, HasTranslations, HasWebpRenditions, RecordsChanges;

    public const string MEDIA_COVER = 'cover';

    /** WebP renditions (name => width) used for srcset; smallest first. */
    public const array IMAGE_SIZES = ['w800' => 800, 'w1600' => 1600];

    protected function casts(): array
    {
        return [
            'status' => AuctionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => ContentAvailability::flush());
        // Through the models, so each lot's photos are removed with it (a database cascade would leave the files).
        static::deleting(fn (Auction $auction) => $auction->lots()->get()->each->delete());
        static::deleted(fn () => ContentAvailability::flush());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COVER)->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Published auctions (cancelled and drafts are not shown to visitors).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->whereIn('status', [AuctionStatus::Scheduled, AuctionStatus::Live, AuctionStatus::Ended]);
    }

    public function phase(): AuctionPhase
    {
        return match (true) {
            $this->status === AuctionStatus::Cancelled => AuctionPhase::Cancelled,
            $this->ends_at->isPast() => AuctionPhase::Ended,
            $this->starts_at->isPast() => AuctionPhase::Live,
            default => AuctionPhase::Upcoming,
        };
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
