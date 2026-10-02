@php use App\Domain\Catalog\Models\Collection; @endphp

<x-layouts.store :title="__('catalog.collections.title')" :description="__('catalog.collections.intro')">
    <div class="container-page py-10 lg:py-14">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('catalog.collections.title')]]" />

        <header class="mt-6 max-w-2xl">
            <h1 class="text-display-lg">{{ __('catalog.collections.title') }}</h1>
            <x-ui.ornament class="mt-4 w-40" />
            <p class="mt-4 text-ink-soft">{{ __('catalog.collections.intro') }}</p>
        </header>

        @if ($collections->isEmpty())
            <x-ui.empty-state icon="store" :title="__('catalog.collections.empty')" class="mt-10 rounded-xs border border-line bg-paper" />
        @else
            <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($collections as $collection)
                    @php($cover = $collection->responsiveImage(Collection::MEDIA_COVER))
                    <li>
                        <a href="{{ localized_route('collection', $collection) }}" class="group block">
                            <x-ui.image :src="$cover['src'] ?? null" :srcset="$cover['srcset'] ?? null" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 92vw"
                                :alt="$cover['alt'] ?? ''" ratio="4/3" class="rounded-xs transition-transform duration-500 group-hover:scale-[1.02]" />
                            <h2 class="mt-4 text-display-sm group-hover:text-bronze">{{ $collection->translate('name') }}</h2>
                            <p class="mt-1 text-sm text-ink-soft">{{ trans_choice('catalog.collections.pieces', $collection->products_count, ['count' => $collection->products_count]) }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.store>
