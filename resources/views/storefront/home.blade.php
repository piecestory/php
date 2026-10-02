@php
    use App\Domain\Catalog\Models\Category;
    use App\Domain\Content\Models\HeroSlide;
    use App\Support\Localization\LocalizedRoute;
    use App\View\ProductCard;

    $storeUrl = LocalizedRoute::has('store') ? localized_route('store') : null;
    $categoryRoute = LocalizedRoute::has('category');
    $promos = array_filter([
        LocalizedRoute::has('auctions') ? ['tone' => 'dark', 'key' => 'auctions', 'url' => localized_route('auctions')] : null,
        LocalizedRoute::has('personal-finder') ? ['tone' => 'light', 'key' => 'finder', 'url' => localized_route('personal-finder')] : null,
    ]);
@endphp

<x-layouts.store :description="__('site.home.hero_text')">
    {{-- Hero --}}
    @if ($slides->isNotEmpty())
        <section x-data="slider" x-on:touchstart.passive="touchStart" x-on:touchend="touchEnd"
            class="relative isolate overflow-hidden bg-night" aria-roledescription="carousel" aria-label="{{ __('site.home.highlights') }}">
            @foreach ($slides as $slide)
                <div data-slide="{{ $loop->index }}" x-show="isActive" @if (! $loop->first) x-cloak @endif
                    class="relative flex min-h-[30rem] items-center lg:min-h-[34rem]" role="group" aria-roledescription="slide">
                    @php
                        $desktop = $slide->responsiveImage(HeroSlide::MEDIA_DESKTOP);
                        $mobile = $slide->responsiveImage(HeroSlide::MEDIA_MOBILE);
                    @endphp
                    <picture class="absolute inset-0 -z-10">
                        @if ($mobile)
                            <source media="(max-width: 767px)" srcset="{{ $mobile['srcset'] ?? $mobile['src'] }}" sizes="100vw">
                        @endif
                        <img src="{{ $desktop['src'] ?? asset('images/placeholder.svg') }}"
                            @if ($desktop['srcset'] ?? null) srcset="{{ $desktop['srcset'] }}" sizes="100vw" @endif
                            alt="{{ $desktop['alt'] ?? '' }}" class="size-full object-cover object-top"
                            @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                            data-fallback="{{ asset('images/placeholder.svg') }}">
                    </picture>
                    <div class="absolute inset-0 -z-10 bg-linear-to-l from-night/95 from-15% via-night/65 to-night/10 ltr:bg-linear-to-r"></div>
                    <div class="container-page pt-16 pb-32 lg:pb-40">
                        <div class="max-w-xl space-y-5 text-ivory">
                            <h1 class="text-display-xl text-ivory">{{ $slide->translate('title') }}</h1>
                            <x-ui.ornament class="w-40" />
                            @if ($slide->translate('subtitle'))
                                <p class="text-lg leading-relaxed text-ivory/85">{{ $slide->translate('subtitle') }}</p>
                            @endif
                            @if ($slide->cta_url && $slide->translate('cta_label'))
                                <x-ui.button :href="LocalizedRoute::path($slide->cta_url)" size="lg" icon="arrow-right">{{ $slide->translate('cta_label') }}</x-ui.button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if ($slides->count() > 1)
                <button type="button" x-on:click="previous" class="absolute start-4 top-1/2 hidden size-12 -translate-y-1/2 place-items-center rounded-full border border-ivory/40 text-ivory transition-colors hover:bg-ivory/10 md:grid">
                    <x-ui.icon name="chevron-left" class="size-5" />
                    <span class="sr-only">{{ __('site.home.previous_slide') }}</span>
                </button>
                <button type="button" x-on:click="next" class="absolute end-4 top-1/2 hidden size-12 -translate-y-1/2 place-items-center rounded-full border border-ivory/40 text-ivory transition-colors hover:bg-ivory/10 md:grid">
                    <x-ui.icon name="chevron-right" class="size-5" />
                    <span class="sr-only">{{ __('site.home.next_slide') }}</span>
                </button>
                <div class="absolute inset-x-0 bottom-28 flex justify-center gap-2 lg:bottom-32">
                    @foreach ($slides as $slide)
                        <button type="button" data-dot="{{ $loop->index }}" x-on:click="goTo" :aria-current="isActive"
                            class="h-1.5 w-6 rounded-full bg-ivory/40 transition-all aria-[current=true]:w-10 aria-[current=true]:bg-gold">
                            <span class="sr-only">{{ __('site.home.go_to_slide', ['number' => $loop->iteration]) }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        {{-- No campaign images yet: a brand hero built from the identity itself. --}}
        <section class="relative isolate overflow-hidden bg-night text-ivory">
            <div class="pointer-events-none absolute -end-24 top-1/2 -z-10 h-[150%] -translate-y-1/2 text-gold/10 sm:-end-10 lg:end-[6%] lg:h-[120%]" aria-hidden="true">
                <x-logo variant="emblem" />
            </div>
            <div class="container-page flex min-h-[30rem] items-center pt-16 pb-32 lg:min-h-[36rem] lg:pb-40">
                <div class="max-w-xl space-y-6">
                    <p class="font-display text-lg text-gold">{{ __('site.tagline') }}</p>
                    <h1 class="text-display-xl text-ivory">{{ __('site.home.hero_title') }}</h1>
                    <x-ui.ornament class="w-44" />
                    <p class="text-lg leading-relaxed text-ivory/80">{{ __('site.home.hero_text') }}</p>
                    @if ($storeUrl)
                        <x-ui.button :href="$storeUrl" size="lg" icon="arrow-right">{{ __('site.home.browse') }}</x-ui.button>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Categories: overlaps the hero as in the reference design --}}
    @if ($categories->isNotEmpty())
        <section aria-label="{{ __('site.home.categories') }}" class="container-page relative z-10 -mt-20 lg:-mt-24">
            <div class="rounded-xs border border-line bg-paper/95 p-3 shadow-raised backdrop-blur-sm sm:p-4">
                <ul class="-mx-1 flex snap-x snap-mandatory gap-3 overflow-x-auto px-1 pb-1 lg:grid lg:grid-cols-6 lg:overflow-visible lg:pb-0">
                    @foreach ($categories as $category)
                        <li class="w-[15rem] shrink-0 snap-start lg:w-auto">
                            <x-category-tile :name="$category->translate('name')"
                                :href="$categoryRoute ? localized_route('category', $category) : null"
                                :image="$category->responsiveImage(Category::MEDIA_IMAGE)['src'] ?? null" class="h-full" />
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- Latest pieces --}}
    @if ($latestProducts->isNotEmpty())
        <section class="container-page mt-16 lg:mt-20" aria-labelledby="latest-heading">
            <x-ui.section-heading :title="__('site.home.latest')" :href="$storeUrl" id="latest-heading" />
            <ul class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($latestProducts as $product)
                    @php($card = ProductCard::from($product))
                    <li>
                        <x-product-card :name="$card['name']" :href="$card['href']" :price="$card['price']"
                            :compare-at="$card['compareAt']" :image="$card['image']" :srcset="$card['srcset']" :badges="$card['badges']"
                            :purchasable="$card['purchasable']" :on-request="$card['onRequest']" :product-id="$card['productId']" :wishlisted="$card['wishlisted']" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Auctions / Personal Finder --}}
    @if ($promos)
        <section class="container-page mt-16 grid gap-4 md:grid-cols-2 lg:mt-20">
            @foreach ($promos as $promo)
                <x-promo-card :tone="$promo['tone']" :href="$promo['url']"
                    :title="__('site.home.'.$promo['key'].'_title')" :text="__('site.home.'.$promo['key'].'_text')"
                    :cta="__('site.home.'.$promo['key'].'_cta')" />
            @endforeach
        </section>
    @endif

    {{-- Service promises (confirmed by the owner) --}}
    <section class="container-page my-16 lg:my-20" aria-label="{{ __('site.nav.services') }}">
        <ul class="grid grid-cols-1 gap-6 rounded-xs border border-line bg-paper p-6 sm:grid-cols-2 lg:grid-cols-5 lg:gap-4 lg:p-8">
            @foreach (['delivery' => 'truck', 'pickup' => 'store', 'reservation' => 'calendar-check', 'support' => 'headset', 'returns' => 'refresh-ccw'] as $key => $icon)
                <li><x-trust-item :$icon :title="__('site.trust.'.$key.'.title')" :text="__('site.trust.'.$key.'.text')" /></li>
            @endforeach
        </ul>
    </section>
</x-layouts.store>
