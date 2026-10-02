<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Data\CatalogFilters;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Collection;
use App\Domain\Catalog\Queries\BrowseProducts;
use App\Domain\Catalog\Queries\CatalogFacets;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CatalogRequest;
use Illuminate\Contracts\View\View;

/** Every product listing (store, category, collection, search) renders through one view. */
class CatalogController extends Controller
{
    public function __construct(
        private readonly BrowseProducts $browse,
        private readonly CatalogFacets $facets,
    ) {}

    public function store(CatalogRequest $request): View
    {
        return $this->listing($request->filters(), [
            'title' => __('catalog.store.title'),
            'intro' => __('catalog.store.intro'),
            'formAction' => localized_route('store'),
        ]);
    }

    public function search(CatalogRequest $request): View
    {
        $filters = $request->filters();

        return $this->listing($filters, [
            'title' => $filters->isSearching() ? __('catalog.search.results_for', ['query' => $filters->search]) : __('catalog.search.title'),
            'intro' => null,
            'formAction' => localized_route('search'),
            'searchBox' => true,
            'noindex' => true,
        ]);
    }

    public function category(CatalogRequest $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        return $this->listing($request->filters(categoryId: $category->id), [
            'title' => (string) $category->translate('name'),
            'intro' => $category->translate('description'),
            'metaTitle' => $category->translate('meta_title'),
            'metaDescription' => $category->translate('meta_description'),
            'formAction' => localized_route('category', $category),
            'category' => $category,
        ]);
    }

    public function collection(CatalogRequest $request, Collection $collection): View
    {
        abort_unless($collection->is_active, 404);

        return $this->listing($request->filters(collectionId: $collection->id), [
            'title' => (string) $collection->translate('name'),
            'intro' => $collection->translate('description'),
            'metaTitle' => $collection->translate('meta_title'),
            'metaDescription' => $collection->translate('meta_description'),
            'formAction' => localized_route('collection', $collection),
            'cover' => $collection->responsiveImage(Collection::MEDIA_COVER),
            'parent' => ['label' => __('site.nav.collections'), 'href' => localized_route('collections')],
        ]);
    }

    public function collections(): View
    {
        return view('storefront.catalog.collections', [
            'collections' => Collection::query()->where('is_active', true)->with('media')->withCount([
                'products' => fn ($q) => $q->published(),
            ])->orderBy('sort_order')->get(),
        ]);
    }

    /** @param array<string, mixed> $page */
    private function listing(CatalogFilters $filters, array $page): View
    {
        return view('storefront.catalog.listing', [
            ...$page,
            'filters' => $filters,
            'products' => $this->browse->paginate($filters),
            'categories' => Category::query()->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->get(),
            'eras' => $this->facets->eras(),
            'origins' => $this->facets->origins(),
            'materials' => $this->facets->materials(),
            'conditions' => $this->facets->conditions(),
        ]);
    }
}
