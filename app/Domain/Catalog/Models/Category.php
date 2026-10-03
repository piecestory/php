<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Shared\Concerns\HasWebpRenditions;
use App\Domain\Shared\Concerns\RecordsChanges;
use App\Support\Localization\HasLocalizedSlug;
use App\Support\Localization\HasTranslations;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

#[Fillable([
    'parent_id', 'name_ar', 'name_en', 'slug_ar', 'slug_en', 'description_ar', 'description_en',
    'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en', 'sort_order', 'is_active',
])]
#[UseFactory(CategoryFactory::class)]
class Category extends Model implements HasMedia
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasLocalizedSlug, HasTranslations, HasWebpRenditions, RecordsChanges, SoftDeletes;

    public const string MEDIA_IMAGE = 'image';

    /** WebP renditions (name => width), smallest first. */
    public const array IMAGE_SIZES = ['tile' => 240, 'w480' => 480];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_IMAGE)->singleFile();
    }

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
