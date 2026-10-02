@props(['title', 'intro' => null])

<x-layouts.store :$title>
    <div class="container-page flex justify-center py-12 sm:py-20">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <span class="inline-flex h-16 text-gold-deep"><x-logo variant="emblem" /></span>
                <h1 class="mt-4 text-display-lg">{{ $title }}</h1>
                @if ($intro)
                    <p class="mt-2 text-sm text-ink-soft">{{ $intro }}</p>
                @endif
            </div>
            <div {{ $attributes->class(['rounded-xs border border-line bg-paper p-6 shadow-card sm:p-8']) }}>
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="mt-6 text-center text-sm text-ink-soft">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</x-layouts.store>
