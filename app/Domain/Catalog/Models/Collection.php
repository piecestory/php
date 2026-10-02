<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'name_ar', 'name_en', 'slug_ar', 'slug_en', 'description_ar', 'description_en',
    'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en', 'sort_order', 'is_active',
])]
class Collection extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    public const string MEDIA_COVER = 'cover';

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COVER)->singleFile();
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }
}
