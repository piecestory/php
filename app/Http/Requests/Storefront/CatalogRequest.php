<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Domain\Catalog\Data\CatalogFilters;
use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Enums\ProductSort;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Listing query string: ?q=&era[]=&origin[]=&material[]=&condition[]=&min=&max=&available=1&rare=1&sort=
 * Invalid values are ignored rather than shown as errors: a shared or old URL always still works.
 */
class CatalogRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'era' => ['nullable', 'array', 'max:20'], 'era.*' => ['integer'],
            'origin' => ['nullable', 'array', 'max:20'], 'origin.*' => ['integer'],
            'material' => ['nullable', 'array', 'max:20'], 'material.*' => ['integer'],
            'condition' => ['nullable', 'array'], 'condition.*' => [Rule::enum(ProductCondition::class)],
            'min' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'max' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'available' => ['nullable', 'boolean'],
            'rare' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::enum(ProductSort::class)],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Drop only the invalid keys and continue with the rest.
        $this->replace($this->except(array_map(fn (string $key) => explode('.', $key)[0], $validator->errors()->keys())));
    }

    public function filters(?int $categoryId = null, ?int $collectionId = null): CatalogFilters
    {
        $search = trim($this->string('q')->toString());
        $sort = ProductSort::tryFrom($this->string('sort')->toString())
            ?? ($search !== '' ? ProductSort::Relevance : ProductSort::Newest);

        return new CatalogFilters(
            search: $search === '' ? null : $search,
            categoryId: $categoryId,
            collectionId: $collectionId,
            eraIds: $this->ids('era'),
            originIds: $this->ids('origin'),
            materialIds: $this->ids('material'),
            conditions: array_values(array_filter(array_map(
                fn (mixed $v) => is_string($v) ? ProductCondition::tryFrom($v) : null,
                (array) $this->input('condition', []),
            ))),
            priceMin: $this->price('min'),
            priceMax: $this->price('max'),
            availableOnly: $this->boolean('available'),
            rareOnly: $this->boolean('rare'),
            sort: $sort,
        );
    }

    /** @return list<int> */
    private function ids(string $key): array
    {
        return array_values(array_unique(array_map('intval', array_filter((array) $this->input($key, []), 'is_numeric'))));
    }

    private function price(string $key): ?string
    {
        $value = $this->input($key);

        return is_numeric($value) && $value >= 0 ? bcadd((string) $value, '0', 2) : null;
    }
}
