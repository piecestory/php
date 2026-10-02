{{-- Account pages: section menu (a sidebar on desktop, a scrollable row on phones) beside the content. --}}
@props(['title', 'heading' => null])

@php
    use App\Support\Localization\LocalizedRoute;

    $current = request()->route()?->getName();
    $sections = [
        ['route' => 'account', 'label' => __('account.nav.overview'), 'icon' => 'user'],
        ['route' => 'account.orders', 'label' => __('account.nav.orders'), 'icon' => 'package-check'],
        ['route' => 'account.addresses', 'label' => __('account.nav.addresses'), 'icon' => 'map-pin'],
        ['route' => 'wishlist', 'label' => __('account.nav.wishlist'), 'icon' => 'heart'],
        ['route' => 'account.profile', 'label' => __('account.nav.profile'), 'icon' => 'shield-check'],
    ];
@endphp

<x-layouts.store :$title noindex>
    <div class="container-page py-10 lg:py-14">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('account.title'), 'href' => localized_route('account')], ['label' => $title]]" />
        <h1 class="mt-6 text-display-lg">{{ $heading ?? $title }}</h1>

        <div class="mt-8 grid gap-8 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-12">
            <nav aria-label="{{ __('account.nav.label') }}" data-scroll-active class="-mx-4 overflow-x-auto px-4 lg:mx-0 lg:overflow-visible lg:px-0">
                <ul class="flex gap-2 lg:flex-col lg:gap-1">
                    @foreach ($sections as $section)
                        @continue(! LocalizedRoute::has($section['route']))
                        @php($active = $current === LocalizedRoute::name($section['route']) || str_starts_with((string) $current, LocalizedRoute::name($section['route']).'.') && $section['route'] !== 'account')
                        <li class="shrink-0">
                            <a href="{{ localized_route($section['route']) }}" @if ($active) aria-current="page" @endif
                                @class([
                                    'flex items-center gap-2.5 whitespace-nowrap rounded-xs px-3.5 py-2.5 text-sm transition-colors',
                                    'bg-ink text-ivory' => $active,
                                    'border border-line bg-paper text-ink-soft hover:text-ink lg:border-transparent lg:bg-transparent' => ! $active,
                                ])>
                                <x-ui.icon :name="$section['icon']" class="size-4" />{{ $section['label'] }}
                            </a>
                        </li>
                    @endforeach
                    <li class="shrink-0 lg:mt-3 lg:border-t lg:border-line lg:pt-3">
                        <form method="POST" action="{{ localized_route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2.5 whitespace-nowrap rounded-xs border border-line bg-paper px-3.5 py-2.5 text-sm text-ink-soft hover:text-danger lg:border-transparent lg:bg-transparent">
                                <x-ui.icon name="arrow-left" class="size-4" />{{ __('auth.logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </nav>

            <div class="min-w-0">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.store>
