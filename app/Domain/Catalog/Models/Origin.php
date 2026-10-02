<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name_ar', 'name_en', 'slug', 'sort_order'])]
class Origin extends Model
{
    use HasTranslations;

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
