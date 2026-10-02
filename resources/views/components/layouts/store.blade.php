@props(['title' => null, 'description' => null, 'noindex' => false])

<x-layouts.base :$title :$description :$noindex class="flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-xs focus:bg-ink focus:px-4 focus:py-2 focus:text-ivory">
        {{ __('site.skip_to_content') }}
    </a>

    <x-site.topbar />
    <x-site.header />

    @if (session('status'))
        <div class="container-page pt-6">
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        </div>
    @endif

    <main id="main" {{ $attributes->class(['flex-1']) }}>
        {{ $slot }}
    </main>

    <x-site.footer />
</x-layouts.base>
