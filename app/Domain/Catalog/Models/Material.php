<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name_ar', 'name_en', 'slug', 'sort_order'])]
class Material extends Model
{
    use HasTranslations;

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
