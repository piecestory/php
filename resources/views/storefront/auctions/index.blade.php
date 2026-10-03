<x-layouts.store :title="__('auctions.title')" :description="__('auctions.intro')">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('auctions.title')]]" />

        <header class="mt-6 max-w-2xl">
            <h1 class="text-display-lg">{{ __('auctions.title') }}</h1>
            <x-ui.ornament class="mt-4 w-40" />
            <p class="mt-4 text-ink-soft">{{ __('auctions.intro') }}</p>
        </header>

        <section aria-labelledby="current-auctions" class="mt-12">
            <h2 id="current-auctions" class="text-display-md">{{ __('auctions.current') }}</h2>
            @if ($current->isEmpty())
                <x-ui.empty-state icon="gavel" :title="__('auctions.empty')" :text="__('auctions.empty_text')" class="mt-6 rounded-xs border border-line bg-paper">
                    @if (\App\Support\Localization\LocalizedRoute::has('contact'))
                        <x-ui.button :href="localized_route('contact')" variant="dark" size="sm">{{ __('site.nav.contact') }}</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <div class="mt-8 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($current as $auction)
                        <x-auction.card :$auction :eager="$loop->first" />
                    @endforeach
                </div>
            @endif
        </section>

        @if ($past->isNotEmpty())
            <section aria-labelledby="past-auctions" class="mt-20 border-t border-line pt-12">
                <h2 id="past-auctions" class="text-display-md">{{ __('auctions.past') }}</h2>
                <div class="mt-8 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($past as $auction)
                        <x-auction.card :$auction />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.store>
