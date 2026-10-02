{{-- Self-contained error screen: no database access, so it renders even when the database is the problem. --}}
@props(['code', 'fallback'])

@php
    // Unknown URLs never reach the locale middleware: infer the language from the path.
    if (request()->segment(1) === 'en') {
        app()->setLocale('en');
    }

    $key = trans()->has("errors.{$code}") ? (string) $code : (string) $fallback;
    $homeUrl = url(app()->getLocale() === 'en' ? '/en' : '/');
@endphp

<x-layouts.base :title="__('errors.'.$key.'.title')" class="flex flex-col bg-ivory">
    <header class="border-b border-line bg-paper">
        <div class="container-page flex h-20 items-center justify-center">
            <a href="{{ $homeUrl }}" class="h-12 text-gold-deep" aria-label="{{ __('ui.brand') }}"><x-logo /></a>
        </div>
    </header>

    <main class="container-page flex flex-1 flex-col items-center justify-center py-20 text-center">
        <span class="h-24 text-gold/70"><x-logo variant="emblem" /></span>
        <p class="mt-6 text-xs tracking-[0.3em] text-ink-soft uppercase">{{ __('errors.code', ['code' => $code]) }}</p>
        <h1 class="mt-3 text-display-lg">{{ __('errors.'.$key.'.title') }}</h1>
        <x-ui.ornament class="mt-5 w-40" />
        <p class="mt-5 max-w-md text-ink-soft">{{ __('errors.'.$key.'.text') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <x-ui.button :href="$homeUrl" icon="arrow-right">{{ __('errors.home') }}</x-ui.button>
        </div>
    </main>
</x-layouts.base>
