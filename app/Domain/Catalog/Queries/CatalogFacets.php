<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Models\Era;
use App\Domain\Catalog\Models\Material;
use App\Domain\Catalog\Models\Origin;
use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Filter options that lead to at least one published piece (no dead-end filters). */
final class CatalogFacets
{
    /** @return Collection<int, Era> */
    public function eras(): Collection
    {
        return Era::query()->whereHas('products', fn (Builder $q) => $q->published())->orderBy('sort_order')->get();
    }

    /** @return Collection<int, Origin> */
    public function origins(): Collection
    {
        return Origin::query()->whereHas('products', fn (Builder $q) => $q->published())->orderBy('sort_order')->get();
    }

    /** @return Collection<int, Material> */
    public function materials(): Collection
    {
        return Material::query()->whereHas('products', fn (Builder $q) => $q->published())->orderBy('sort_order')->get();
    }

    /** @return list<ProductCondition> */
    public function conditions(): array
    {
        $present = Product::query()->published()->whereNotNull('condition')->distinct()->pluck('condition')->all();

        return array_values(array_filter(ProductCondition::cases(), fn (ProductCondition $c) => in_array($c, $present, true)));
    }
}
