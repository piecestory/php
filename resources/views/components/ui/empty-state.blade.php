@props(['icon' => 'search', 'title', 'text' => null])

<div {{ $attributes->class(['flex flex-col items-center gap-3 px-6 py-16 text-center']) }}>
    <x-ui.icon :name="$icon" class="size-10 text-gold" stroke-width="1.1" />
    <h3 class="text-display-sm">{{ $title }}</h3>
    @if ($text)
        <p class="max-w-md text-sm text-ink-soft">{{ $text }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
