@php
    use App\Domain\Catalog\Enums\ProductSort;
    use App\View\ProductCard;

    $category ??= null;
    $cover ??= null;
    $parent ??= null;
    $metaTitle ??= null;
    $metaDescription ??= null;
    $noindex ??= false;
    $byName = fn ($items) => $items->mapWithKeys(fn ($item) => [$item->id => $item->translate('name')])->all();

    $crumbs = [['label' => __('ui.home'), 'href' => localized_route('home')]];
    if ($category || $parent) {
        $crumbs[] = $parent ?? ['label' => __('catalog.store.title'), 'href' => localized_route('store')];
    }
    $crumbs[] = ['label' => $title];
@endphp

<x-layouts.store :title="$metaTitle ?: $title" :description="$metaDescription ?: $intro" :$noindex>
    <div x-data="catalogFilters" x-on:keydown.escape.window="close" class="container-page py-10 lg:py-14">
        <x-ui.breadcrumbs :items="$crumbs" />

        <header class="mt-6 flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl">
                <h1 class="text-display-lg">{{ $title }}</h1>
                @if ($intro)
                    <p class="mt-3 text-ink-soft">{{ $intro }}</p>
                @endif
            </div>
            <p class="text-sm text-ink-soft" role="status">{{ trans_choice('catalog.results', $products->total(), ['count' => $products->total()]) }}</p>
        </header>

        @if ($searchBox ?? false)
            <form method="GET" action="{{ $formAction }}" role="search" class="relative mt-6 max-w-xl">
                <label for="page-search" class="sr-only">{{ __('catalog.search.label') }}</label>
                <input id="page-search" type="search" name="q" value="{{ $filters->search }}" maxlength="100"
                    placeholder="{{ __('catalog.search.placeholder') }}" class="form-control h-12 pe-12 text-base" @unless ($filters->isSearching()) autofocus @endunless>
                <button type="submit" class="absolute inset-y-0 end-0 grid w-12 place-items-center text-ink-soft hover:text-bronze">
                    <x-ui.icon name="search" /><span class="sr-only">{{ __('catalog.search.submit') }}</span>
                </button>
            </form>
        @endif

        @if ($cover)
            <img src="{{ $cover['src'] }}" @if ($cover['srcset']) srcset="{{ $cover['srcset'] }}" sizes="100vw" @endif
                alt="{{ $cover['alt'] }}" class="mt-8 aspect-[3/1] w-full rounded-xs object-cover" fetchpriority="high">
        @endif

        <form id="catalog-filters" method="GET" action="{{ $formAction }}" class="mt-8 lg:grid lg:grid-cols-[15rem_1fr] lg:gap-10">
            @if ($filters->isSearching())
                <input type="hidden" name="q" value="{{ $filters->search }}">
            @endif

            {{-- Filters: sidebar on desktop, slide-in panel on mobile --}}
            <aside :data-open="open" aria-label="{{ __('catalog.filters.title') }}"
                class="hidden data-[open=true]:fixed data-[open=true]:inset-0 data-[open=true]:z-40 data-[open=true]:flex lg:block">
                <div class="absolute inset-0 bg-night/50 lg:hidden" x-on:click="close"></div>
                <div class="relative ms-auto flex h-full w-[min(22rem,90vw)] flex-col bg-paper lg:h-auto lg:w-auto lg:bg-transparent">
                    <div class="flex h-16 items-center justify-between border-b border-line px-5 lg:hidden">
                        <p class="font-display text-display-sm">{{ __('catalog.filters.title') }}</p>
                        <button type="button" x-on:click="close" class="grid size-10 place-items-center rounded-xs hover:bg-linen">
                            <x-ui.icon name="x" /><span class="sr-only">{{ __('catalog.filters.close') }}</span>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 lg:overflow-visible lg:px-0">
                        <nav class="border-b border-line py-4" aria-label="{{ __('catalog.filters.categories') }}">
                            <p class="text-sm font-semibold">{{ __('catalog.filters.categories') }}</p>
                            <ul class="mt-3 space-y-2 text-sm">
                                <li><a href="{{ localized_route('store') }}" @class(['hover:text-bronze', 'font-semibold text-bronze' => ! $category, 'text-ink-soft' => $category])>{{ __('catalog.filters.all') }}</a></li>
                                @foreach ($categories as $item)
                                    <li>
                                        <a href="{{ localized_route('category', $item) }}" @if ($category?->is($item)) aria-current="page" @endif
                                            @class(['hover:text-bronze', 'font-semibold text-bronze' => $category?->is($item), 'text-ink-soft' => ! $category?->is($item)])>{{ $item->translate('name') }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>

                        <div class="space-y-3 border-b border-line py-4">
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm">
                                <input type="checkbox" name="available" value="1" x-on:change="changed" @checked($filters->availableOnly) class="size-4 accent-bronze">
                                {{ __('catalog.filters.available') }}
                            </label>
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm">
                                <input type="checkbox" name="rare" value="1" x-on:change="changed" @checked($filters->rareOnly) class="size-4 accent-bronze">
                                {{ __('catalog.filters.rare') }}
                            </label>
                        </div>

                        <fieldset class="border-b border-line py-4">
                            <legend class="text-sm font-semibold">{{ __('catalog.filters.price') }}</legend>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <label class="text-xs text-ink-soft">{{ __('catalog.filters.price_min') }}
                                    <input type="number" name="min" min="0" step="50" inputmode="numeric" value="{{ $filters->priceMin !== null ? (int) $filters->priceMin : '' }}" class="form-control mt-1 h-10" dir="ltr">
                                </label>
                                <label class="text-xs text-ink-soft">{{ __('catalog.filters.price_max') }}
                                    <input type="number" name="max" min="0" step="50" inputmode="numeric" value="{{ $filters->priceMax !== null ? (int) $filters->priceMax : '' }}" class="form-control mt-1 h-10" dir="ltr">
                                </label>
                            </div>
                        </fieldset>

                        <x-catalog.filter-group :title="__('catalog.filters.era')" name="era" :options="$byName($eras)" :selected="$filters->eraIds" />
                        <x-catalog.filter-group :title="__('catalog.filters.origin')" name="origin" :options="$byName($origins)" :selected="$filters->originIds" />
                        <x-catalog.filter-group :title="__('catalog.filters.material')" name="material" :options="$byName($materials)" :selected="$filters->materialIds" />
                        <x-catalog.filter-group :title="__('catalog.filters.condition')" name="condition"
                            :options="collect($conditions)->mapWithKeys(fn ($c) => [$c->value => __('catalog.condition.'.$c->value)])->all()"
                            :selected="array_map(fn ($c) => $c->value, $filters->conditions)" />
                    </div>

                    <div class="flex gap-3 border-t border-line p-5 lg:border-0 lg:px-0">
                        <x-ui.button type="submit" class="flex-1">{{ __('catalog.filters.apply') }}</x-ui.button>
                        @if ($filters->activeCount() > 0)
                            <x-ui.button variant="ghost" :href="$formAction.($filters->isSearching() ? '?q='.urlencode($filters->search) : '')">{{ __('catalog.filters.reset') }}</x-ui.button>
                        @endif
                    </div>
                </div>
            </aside>

            {{-- Results --}}
            <section aria-label="{{ $title }}">
                <div class="mb-6 flex items-center justify-between gap-4 lg:justify-end">
                    <button type="button" x-on:click="toggle" :aria-expanded="open"
                        class="inline-flex h-10 items-center gap-2 rounded-xs border border-line-strong bg-paper px-4 text-sm lg:hidden">
                        {{ __('catalog.filters.open') }}
                        @if ($filters->activeCount() > 0)
                            <span class="numerals grid size-5 place-items-center rounded-full bg-bronze text-[0.6875rem] text-white">{{ $filters->activeCount() }}</span>
                        @endif
                    </button>
                    <label class="flex items-center gap-2 text-sm text-ink-soft">
                        <span class="hidden sm:inline">{{ __('catalog.sort.label') }}</span>
                        <span class="relative">
                            <select name="sort" x-on:change="submitNow" aria-label="{{ __('catalog.sort.label') }}" class="form-control h-10 appearance-none pe-9 text-sm">
                                @foreach (ProductSort::options($filters->isSearching()) as $option)
                                    <option value="{{ $option->value }}" @selected($filters->sort === $option)>{{ $option->label() }}</option>
                                @endforeach
                            </select>
                            <x-ui.icon name="chevron-down" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2" />
                        </span>
                    </label>
                </div>

                @if ($products->isEmpty())
                    <x-ui.empty-state icon="search" :title="__('catalog.empty.title')"
                        :text="$filters->activeCount() > 0 || $filters->isSearching() ? __('catalog.empty.text') : __('catalog.empty.catalog')"
                        class="rounded-xs border border-line bg-paper">
                        @if ($filters->activeCount() > 0)
                            <x-ui.button variant="outline" :href="$formAction">{{ __('catalog.empty.reset') }}</x-ui.button>
                        @endif
                    </x-ui.empty-state>
                @else
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3 xl:grid-cols-4">
                        @foreach ($products as $product)
                            @php($card = ProductCard::from($product))
                            <li>
                                <x-product-card :name="$card['name']" :href="$card['href']" :price="$card['price']"
                                    :compare-at="$card['compareAt']" :image="$card['image']" :srcset="$card['srcset']"
                                    :badges="$card['badges']" :purchasable="$card['purchasable']" :on-request="$card['onRequest']" />
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-12">{{ $products->links('partials.pagination') }}</div>
                @endif
            </section>
        </form>
    </div>
</x-layouts.store>
