<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name_ar', 'name_en', 'slug', 'year_from', 'year_to', 'sort_order'])]
class Era extends Model
{
    use HasTranslations;

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'year_from' => 'integer', 'year_to' => 'integer'];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
