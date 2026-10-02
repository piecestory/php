@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconOnly' => false,
])

@php
    $classes = [
        'inline-flex items-center justify-center gap-2.5 font-medium whitespace-nowrap rounded-xs',
        'transition-colors duration-200 ease-elegant',
        'disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50',
        match ($variant) {
            'primary' => 'bg-bronze text-white hover:bg-bronze-deep',
            'dark' => 'bg-ink text-ivory hover:bg-night-soft',
            'outline' => 'border border-ink/80 text-ink hover:bg-ink hover:text-ivory',
            'light' => 'bg-paper text-ink hover:bg-linen',
            'ghost' => 'text-ink hover:bg-linen',
            'link' => 'text-bronze underline-offset-4 hover:text-bronze-deep hover:underline',
        },
        $variant === 'link' ? 'text-sm' : match ($size) {
            'sm' => $iconOnly ? 'size-9' : 'h-9 px-4 text-sm',
            'lg' => $iconOnly ? 'size-14' : 'h-14 px-8 text-base',
            default => $iconOnly ? 'size-11' : 'h-11 px-6 text-[0.9375rem]',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        <span>{{ $slot }}</span>
        @if ($icon)<x-ui.icon :name="$icon" class="size-[1.15em]" />@endif
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
        @if ($iconOnly)
            <x-ui.icon :name="$icon" />
            <span class="sr-only">{{ $slot }}</span>
        @else
            <span>{{ $slot }}</span>
            @if ($icon)<x-ui.icon :name="$icon" class="size-[1.15em]" />@endif
        @endif
    </button>
@endif
