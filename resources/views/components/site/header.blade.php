@inject('cart', \App\Http\Support\CurrentCart::class)
@inject('wishlist', \App\Http\Support\CurrentWishlist::class)
@php
    use App\View\Navigation;

    $menu = Navigation::main();
    $help = Navigation::utility();
    // Wishlist lives in the mobile menu: the phone header keeps only search, cart and account.
    $extra = \App\Support\Localization\LocalizedRoute::has('wishlist')
        ? [['label' => __('wishlist.title'), 'url' => localized_route('wishlist'), 'active' => request()->routeIs('wishlist', 'en.wishlist')]]
        : [];
    $accountUrl = auth()->check() && \App\Support\Localization\LocalizedRoute::has('account')
        ? localized_route('account')
        : (auth()->guest() ? localized_route('login') : null);
@endphp

{{-- No backdrop-filter / transform / filter on the header: they make it the containing block of the
     fixed mobile menu below, which then opens squeezed into the header's 72px instead of the screen. --}}
<header x-data="disclosure" x-on:keydown.escape.window="close" class="relative z-30 border-b border-line bg-paper">
    <div class="container-page flex h-[4.5rem] items-center gap-4 lg:h-24">
        {{-- Mobile: menu button --}}
        <button type="button" x-on:click="toggle" :aria-expanded="open" aria-controls="mobile-menu"
            class="-ms-2 grid size-11 place-items-center rounded-xs text-ink hover:bg-linen lg:hidden">
            <x-ui.icon name="menu" class="size-6" />
            <span class="sr-only">{{ __('site.nav.open_menu') }}</span>
        </button>

        <a href="{{ localized_route('home') }}" class="me-auto h-11 text-gold-deep lg:me-0 lg:h-14" aria-label="{{ __('ui.brand') }} — {{ __('site.nav.home') }}">
            <span class="flex h-full lg:hidden"><x-logo variant="compact" /></span>
            <span class="hidden h-full lg:flex"><x-logo /></span>
        </a>

        <nav aria-label="{{ __('site.nav.label') }}" class="hidden flex-1 justify-center lg:flex">
            <ul class="flex items-center gap-7 text-[0.9375rem]">
                @foreach ($menu as $item)
                    <li>
                        <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                            @class([
                                'relative py-2 transition-colors hover:text-bronze',
                                'text-ink after:absolute after:inset-x-0 after:-bottom-0.5 after:h-px after:bg-bronze' => $item['active'],
                                'text-ink-soft' => ! $item['active'],
                            ])>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if (\App\Support\Localization\LocalizedRoute::has('search'))
            <form method="GET" action="{{ localized_route('search') }}" role="search" class="relative hidden w-56 xl:block xl:w-64">
                <label for="header-search" class="sr-only">{{ __('catalog.search.label') }}</label>
                <input id="header-search" type="search" name="q" value="{{ request()->routeIs('search', 'en.search') ? request('q') : '' }}"
                    placeholder="{{ __('catalog.search.placeholder') }}" maxlength="100" class="form-control h-11 bg-ivory pe-11">
                <button type="submit" class="absolute inset-y-0 end-0 grid w-11 place-items-center text-ink-soft hover:text-bronze">
                    <x-ui.icon name="search" class="size-5" /><span class="sr-only">{{ __('catalog.search.submit') }}</span>
                </button>
            </form>
        @endif

        <div class="flex items-center gap-1">
            @if (\App\Support\Localization\LocalizedRoute::has('search'))
                <a href="{{ localized_route('search') }}" class="grid size-11 place-items-center rounded-xs text-ink hover:bg-linen hover:text-bronze xl:hidden">
                    <x-ui.icon name="search" class="size-[1.35rem]" /><span class="sr-only">{{ __('catalog.search.label') }}</span>
                </a>
            @endif
            @if (\App\Support\Localization\LocalizedRoute::has('wishlist'))
                <a href="{{ localized_route('wishlist') }}" x-data="counter" data-count="{{ $wishlist->count() }}" x-on:wishlist-updated.window="updateWishlist"
                    class="relative hidden size-11 place-items-center rounded-xs text-ink transition-colors hover:bg-linen hover:text-bronze sm:grid">
                    <x-ui.icon name="heart" class="size-[1.35rem]" />
                    <span x-show="hasItems" x-text="count" @if ($wishlist->count() === 0) x-cloak @endif
                        class="numerals absolute end-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-bronze px-1 text-[0.625rem] font-semibold text-white">{{ $wishlist->count() }}</span>
                    <span class="sr-only">{{ __('wishlist.title') }}</span>
                </a>
            @endif
            @if (\App\Support\Localization\LocalizedRoute::has('cart'))
                @php($cartSummary = $cart->summary())
                <a href="{{ localized_route('cart') }}" x-data="counter" data-count="{{ $cartSummary->count }}"
                    data-total="{{ \App\Support\Money\Money::amount($cartSummary->total) }} {{ \App\Support\Money\Money::currency() }}"
                    x-on:cart-updated.window="updateCart"
                    class="relative flex h-11 items-center gap-2 rounded-xs px-2 text-ink transition-colors hover:bg-linen hover:text-bronze">
                    <span class="relative">
                        <x-ui.icon name="shopping-bag" class="size-[1.35rem]" />
                        <span x-show="hasItems" x-text="count" @if ($cartSummary->count === 0) x-cloak @endif
                            class="numerals absolute -end-2 -top-2 grid h-4 min-w-4 place-items-center rounded-full bg-ink px-1 text-[0.625rem] font-semibold text-ivory">{{ $cartSummary->count }}</span>
                    </span>
                    <span x-text="total" class="numerals hidden text-sm font-medium lg:inline">{{ \App\Support\Money\Money::amount($cartSummary->total) }} {{ \App\Support\Money\Money::currency() }}</span>
                    <span class="sr-only">{{ __('cart.title') }}</span>
                </a>
            @endif
            @if ($accountUrl)
                <a href="{{ $accountUrl }}" class="grid size-11 place-items-center rounded-xs text-ink transition-colors hover:bg-linen hover:text-bronze">
                    <x-ui.icon name="user" class="size-[1.35rem]" />
                    <span class="sr-only">{{ auth()->check() ? __('site.nav.account') : __('site.nav.login') }}</span>
                </a>
            @endif
            @auth
                <form method="POST" action="{{ localized_route('logout') }}" class="hidden lg:block">
                    @csrf
                    <button type="submit" class="px-2 text-sm text-ink-soft hover:text-bronze">{{ __('auth.logout') }}</button>
                </form>
            @endauth
        </div>
    </div>

    {{-- Mobile menu --}}
    <div id="mobile-menu" x-show="open" x-cloak class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('site.nav.label') }}">
        <div class="absolute inset-0 bg-night/50" x-on:click="close"></div>
        <nav x-show="open" x-transition:enter="transition duration-300 ease-elegant"
            x-transition:enter-start="ltr:-translate-x-full rtl:translate-x-full" x-transition:enter-end="translate-x-0"
            class="absolute inset-y-0 start-0 flex w-[min(20rem,85vw)] flex-col bg-paper shadow-raised">
            <div class="flex h-[4.5rem] items-center justify-between border-b border-line px-4">
                <span class="h-10 text-gold-deep"><x-logo variant="compact" /></span>
                <button type="button" x-on:click="close" class="grid size-11 place-items-center rounded-xs hover:bg-linen">
                    <x-ui.icon name="x" class="size-6" />
                    <span class="sr-only">{{ __('site.nav.close_menu') }}</span>
                </button>
            </div>
            <ul class="flex-1 overflow-y-auto px-4 py-3">
                @foreach ([...$menu, ...$help, ...$extra] as $item)
                    <li class="border-b border-line/70 last:border-0">
                        <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                            @class(['flex items-center justify-between py-3.5 text-base', 'text-bronze' => $item['active']])>
                            {{ $item['label'] }}
                            <x-ui.icon name="chevron-right" class="size-4 text-ink-faint" />
                        </a>
                    </li>
                @endforeach
            </ul>
            @auth
                <form method="POST" action="{{ localized_route('logout') }}" class="border-t border-line p-4">
                    @csrf
                    <x-ui.button type="submit" variant="outline" class="w-full">{{ __('auth.logout') }}</x-ui.button>
                </form>
            @endauth
        </nav>
    </div>
</header>
