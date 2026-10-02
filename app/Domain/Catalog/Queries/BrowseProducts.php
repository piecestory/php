<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Data\CatalogFilters;
use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Enums\ProductSort;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Support\Search\ArabicNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Published products matching the customer's filters. Prices are compared on the price the
 * customer actually pays (an active sale price, otherwise the regular price).
 */
final class BrowseProducts
{
    public const int PER_PAGE = 24;

    /** InnoDB FULLTEXT ignores words shorter than this (innodb_ft_min_token_size). */
    private const int FULLTEXT_MIN_LENGTH = 3;

    /** @return LengthAwarePaginator<int, Product> */
    public function paginate(CatalogFilters $filters, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with('media')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Builder<Product> */
    public function query(CatalogFilters $filters): Builder
    {
        [$priceSql, $priceBindings] = self::effectivePriceSql();
        $query = Product::query()->published();

        if ($filters->categoryId !== null) {
            $ids = Category::query()->where('parent_id', $filters->categoryId)->pluck('id')->push($filters->categoryId);
            $query->whereIn('category_id', $ids);
        }
        if ($filters->collectionId !== null) {
            $query->whereHas('collections', fn (Builder $q) => $q->whereKey($filters->collectionId));
        }
        if ($filters->eraIds !== []) {
            $query->whereIn('era_id', $filters->eraIds);
        }
        if ($filters->originIds !== []) {
            $query->whereIn('origin_id', $filters->originIds);
        }
        if ($filters->materialIds !== []) {
            $query->whereHas('materials', fn (Builder $q) => $q->whereIn('materials.id', $filters->materialIds));
        }
        if ($filters->conditions !== []) {
            $query->whereIn('condition', $filters->conditions);
        }
        if ($filters->availableOnly) {
            $query->where('availability', ProductAvailability::Available)->where('stock_quantity', '>', 0);
        }
        if ($filters->rareOnly) {
            $query->where('is_rare', true);
        }
        if ($filters->priceMin !== null) {
            $query->whereRaw("{$priceSql} >= ?", [...$priceBindings, $filters->priceMin]);
        }
        if ($filters->priceMax !== null) {
            $query->whereRaw("{$priceSql} <= ?", [...$priceBindings, $filters->priceMax]);
        }

        $relevance = $filters->isSearching() ? $this->applySearch($query, (string) $filters->search) : null;

        match ($filters->sort) {
            ProductSort::PriceAsc => $query->orderByRaw("{$priceSql} asc", $priceBindings),
            ProductSort::PriceDesc => $query->orderByRaw("{$priceSql} desc", $priceBindings),
            ProductSort::Relevance => $relevance !== null
                ? $query->orderByRaw('MATCH(search_text) AGAINST (? IN BOOLEAN MODE) desc', [$relevance])
                : $query->latest('published_at'),
            ProductSort::Newest => $query->latest('published_at'),
        };

        return $query->orderByDesc('id');
    }

    /**
     * @param  Builder<Product>  $query
     * @return ?string the boolean full-text expression used, for relevance ordering
     */
    private function applySearch(Builder $query, string $search): ?string
    {
        $terms = ArabicNormalizer::queryTerms($search);
        if ($terms === []) {
            $query->whereRaw('1 = 0');

            return null;
        }

        // Every word must match; words are prefixes so partial typing still finds results.
        $long = array_filter($terms, fn (string $t) => mb_strlen($t) >= self::FULLTEXT_MIN_LENGTH);
        $short = array_diff($terms, $long);

        $expression = $long === [] ? null : implode(' ', array_map(fn (string $t) => "+{$t}*", $long));
        if ($expression !== null) {
            $query->whereFullText('search_text', $expression, ['mode' => 'boolean']);
        }
        foreach ($short as $term) {
            $query->where('search_text', 'like', '%'.$term.'%');
        }

        return $expression;
    }

    /** @return array{0: string, 1: list<string>} SQL for the price currently charged, with its bindings */
    public static function effectivePriceSql(): array
    {
        $now = now()->toDateTimeString();

        return [
            '(CASE WHEN products.sale_price IS NOT NULL AND products.sale_price < products.price'
            .' AND (products.sale_starts_at IS NULL OR products.sale_starts_at <= ?)'
            .' AND (products.sale_ends_at IS NULL OR products.sale_ends_at > ?)'
            .' THEN products.sale_price ELSE products.price END)',
            [$now, $now],
        ];
    }
}
