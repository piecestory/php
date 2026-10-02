@props(['title' => null, 'description' => null, 'noindex' => false, 'image' => null, 'ogType' => 'website'])

<x-layouts.base :$title :$description :$noindex :$image :og-type="$ogType" class="flex flex-col">
    @isset($head)
        <x-slot:head>{{ $head }}</x-slot:head>
    @endisset

    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-xs focus:bg-ink focus:px-4 focus:py-2 focus:text-ivory">
        {{ __('site.skip_to_content') }}
    </a>

    <x-site.topbar />
    <x-site.header />

    @if (session('status') || session('error'))
        <div class="container-page pt-6">
            <x-ui.alert :variant="session('error') ? 'danger' : 'success'">{{ session('error') ?? session('status') }}</x-ui.alert>
        </div>
    @endif

    {{-- Confirmation after background cart / wishlist actions --}}
    <div x-data="toast" x-on:notify.window="show" class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4" aria-live="polite">
        <div x-show="visible" x-cloak x-transition.opacity.duration.200ms role="status"
            class="pointer-events-auto flex max-w-md items-center gap-3 rounded-xs bg-ink px-5 py-3.5 text-sm text-ivory shadow-raised">
            <span x-text="message"></span>
            <a href="{{ \App\Support\Localization\LocalizedRoute::has('cart') ? localized_route('cart') : '#' }}" x-show="offerCart"
                class="shrink-0 font-medium text-gold underline-offset-4 hover:underline">{{ __('cart.view_cart') }}</a>
            <button type="button" x-on:click="hide" class="shrink-0 text-ivory/60 hover:text-ivory">
                <x-ui.icon name="x" class="size-4" /><span class="sr-only">{{ __('site.nav.close_menu') }}</span>
            </button>
        </div>
    </div>

    <main id="main" {{ $attributes->class(['flex-1']) }}>
        {{ $slot }}
    </main>

    <x-site.footer />
</x-layouts.base>
