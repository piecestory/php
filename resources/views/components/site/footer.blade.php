@php
    $categories = $categories();
    $showrooms = $showrooms();
    $help = \App\View\Navigation::help();
@endphp

<footer class="mt-auto bg-night text-ivory/75">
    <div class="container-page grid gap-12 py-16 sm:grid-cols-2 lg:flex lg:gap-20">
        <div class="space-y-5 lg:me-auto lg:max-w-sm">
            <a href="{{ localized_route('home') }}" class="inline-flex h-24 text-gold" aria-label="{{ __('ui.brand') }}">
                <x-logo variant="emblem" />
            </a>
            <p class="font-display text-display-sm text-ivory">{{ __('site.tagline') }}</p>
            <p class="text-sm">{{ __('site.specialties') }}</p>
        </div>

        @if ($categories->isNotEmpty())
            <nav aria-labelledby="footer-shop">
                <h2 id="footer-shop" class="mb-5 font-sans text-sm font-semibold tracking-wide text-gold">{{ __('site.footer.shop') }}</h2>
                <ul class="space-y-3 text-sm">
                    @foreach ($categories as $category)
                        <li><a href="{{ localized_route('category', $category->translate('slug')) }}" class="hover:text-gold">{{ $category->translate('name') }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($help)
            <nav aria-labelledby="footer-help">
                <h2 id="footer-help" class="mb-5 font-sans text-sm font-semibold tracking-wide text-gold">{{ __('site.footer.help') }}</h2>
                <ul class="space-y-3 text-sm">
                    @foreach ($help as $item)
                        <li><a href="{{ $item['url'] }}" class="hover:text-gold">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <section aria-labelledby="footer-visit">
            <h2 id="footer-visit" class="mb-5 font-sans text-sm font-semibold tracking-wide text-gold">{{ __('site.footer.visit') }}</h2>
            <ul class="space-y-4 text-sm">
                @foreach ($showrooms as $branch)
                    <li class="flex items-start gap-3">
                        <x-ui.icon name="map-pin" class="mt-0.5 size-4 text-gold" />
                        <span>
                            <span class="block text-ivory">{{ $branch->translate('name') }}</span>
                            @if ($address = $branch->translate('address') ?? (app()->getLocale() === 'ar' ? "{$branch->district}، {$branch->city}" : null))
                                <span>{{ $address }}</span>
                            @endif
                            @if ($branch->map_url)
                                <a href="{{ $branch->map_url }}" target="_blank" rel="noopener" class="ms-2 underline hover:text-gold">{{ __('site.footer.directions') }}</a>
                            @endif
                        </span>
                    </li>
                @endforeach
                <li class="flex items-start gap-3">
                    <x-ui.icon name="clock" class="mt-0.5 size-4 text-gold" />
                    <span>{{ __('site.footer.hours') }}</span>
                </li>
            </ul>
        </section>
    </div>

    <div class="border-t border-ivory/10">
        <div class="container-page flex flex-col gap-2 py-6 text-xs sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ __('ui.brand') }}. {{ __('site.footer.rights') }}</p>
            <p lang="en" dir="ltr" class="tracking-[0.25em]">PIECE &amp; STORY</p>
        </div>
    </div>
</footer>
