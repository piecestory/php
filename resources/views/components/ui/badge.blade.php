@props(['variant' => 'neutral'])

<span {{ $attributes->class([
    'inline-flex items-center rounded-xs px-2 py-0.5 text-[0.6875rem] font-medium leading-5 tracking-wide',
    match ($variant) {
        'rare' => 'bg-night text-gold',
        'sale' => 'bg-bronze text-white',
        'sold' => 'bg-ink/80 text-ivory',
        'reserved' => 'border border-bronze/40 bg-paper text-bronze-deep',
        default => 'bg-linen text-ink-soft',
    },
]) }}>{{ $slot }}</span>
