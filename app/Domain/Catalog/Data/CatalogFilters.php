<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Enums\ProductSort;

/** What the customer asked to see in a product listing. Built from validated input by the HTTP layer. */
final readonly class CatalogFilters
{
    /**
     * @param  list<int>  $eraIds
     * @param  list<int>  $originIds
     * @param  list<int>  $materialIds
     * @param  list<ProductCondition>  $conditions
     */
    public function __construct(
        public ?string $search = null,
        public ?int $categoryId = null,
        public ?int $collectionId = null,
        public array $eraIds = [],
        public array $originIds = [],
        public array $materialIds = [],
        public array $conditions = [],
        public ?string $priceMin = null,
        public ?string $priceMax = null,
        public bool $availableOnly = false,
        public bool $rareOnly = false,
        public ProductSort $sort = ProductSort::Newest,
    ) {}

    public function isSearching(): bool
    {
        return $this->search !== null && trim($this->search) !== '';
    }

    /** Number of refinements the customer applied (shown on the mobile "Filters" button). */
    public function activeCount(): int
    {
        return count($this->eraIds) + count($this->originIds) + count($this->materialIds) + count($this->conditions)
            + ($this->priceMin !== null || $this->priceMax !== null ? 1 : 0)
            + (int) $this->availableOnly + (int) $this->rareOnly;
    }
}
