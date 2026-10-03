<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\StockReservation;
use App\Domain\Shared\Concerns\HasPublication;
use App\Domain\Shared\Concerns\HasWebpRenditions;
use App\Domain\Shared\Concerns\RecordsChanges;
use App\Domain\Shared\Enums\PublicationStatus;
use App\Support\Localization\HasLocalizedSlug;
use App\Support\Localization\HasTranslations;
use App\Support\Search\ArabicNormalizer;
use Carbon\CarbonInterface;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

#[Fillable([
    'category_id', 'origin_id', 'era_id', 'sku', 'name_ar', 'name_en', 'slug_ar', 'slug_en',
    'description_ar', 'description_en', 'story_ar', 'story_en', 'price', 'sale_price',
    'sale_starts_at', 'sale_ends_at', 'stock_quantity', 'availability', 'condition',
    'width_cm', 'height_cm', 'depth_cm', 'weight_kg', 'is_rare', 'is_featured', 'status', 'published_at',
    'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en',
])]
#[Hidden(['search_text'])]
#[UseFactory(ProductFactory::class)]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasLocalizedSlug, HasPublication, HasTranslations, HasWebpRenditions, RecordsChanges, SoftDeletes;

    /** Ordered gallery; the first image is the primary image. */
    public const string MEDIA_GALLERY = 'gallery';

    private const array SEARCHABLE = ['sku', 'name_ar', 'name_en', 'description_ar', 'description_en'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'stock_quantity' => 'integer',
            'availability' => ProductAvailability::class,
            'condition' => ProductCondition::class,
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'depth_cm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'is_rare' => 'boolean',
            'is_featured' => 'boolean',
            'status' => PublicationStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            if ($product->isDirty(self::SEARCHABLE) || $product->search_text === null) {
                $product->search_text = ArabicNormalizer::forIndex(
                    implode(' ', array_map(fn (string $field) => (string) $product->getAttribute($field), self::SEARCHABLE)),
                );
            }
        });
    }

    /** WebP renditions (name => width) used for srcset; smallest first. */
    public const array IMAGE_SIZES = ['thumb' => 400, 'card' => 800, 'large' => 1600];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_GALLERY)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function isOnSale(?CarbonInterface $at = null): bool
    {
        if ($this->sale_price === null || bccomp($this->sale_price, $this->price, 2) >= 0) {
            return false;
        }

        $at ??= now();

        return ($this->sale_starts_at === null || $this->sale_starts_at->lte($at))
            && ($this->sale_ends_at === null || $this->sale_ends_at->gt($at));
    }

    /** Can be added to a cart right now: visible, marked available and in stock. */
    public function isPurchasable(): bool
    {
        return ! $this->trashed()
            && $this->isPublished()
            && $this->availability === ProductAvailability::Available
            && $this->stock_quantity > 0;
    }

    /** The price the customer pays right now (VAT inclusive). */
    public function effectivePrice(?CarbonInterface $at = null): string
    {
        return $this->isOnSale($at) ? (string) $this->sale_price : (string) $this->price;
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Origin, $this> */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(Origin::class);
    }

    /** @return BelongsTo<Era, $this> */
    public function era(): BelongsTo
    {
        return $this->belongsTo(Era::class);
    }

    /** @return BelongsToMany<Material, $this> */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class);
    }

    /** @return BelongsToMany<Collection, $this> */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('sort_order');
    }

    /** @return HasMany<InventoryMovement, $this> */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Stock has its own ledger (inventory_movements); the change log keeps to the piece's details.
     *
     * @return list<string>
     */
    protected function changesNotRecorded(): array
    {
        return ['stock_quantity'];
    }

    /** @return HasMany<StockReservation, $this> */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /** Out of stock only because an open order or reservation holds it (shown as "reserved", not "sold"). */
    public function isOnHold(): bool
    {
        return $this->stock_quantity === 0
            && $this->stockReservations()->where('expires_at', '>', now())->exists();
    }
}
