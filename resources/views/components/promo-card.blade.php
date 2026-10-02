@props([
    'title',
    'text',
    'cta',
    'href',
    'image' => null,
    'tone' => 'dark',
])

@php $dark = $tone === 'dark'; @endphp

<article {{ $attributes->class([
    'relative isolate flex min-h-72 overflow-hidden rounded-xs',
    'bg-night text-ivory' => $dark,
    'bg-linen text-ink' => ! $dark,
]) }}>
    @if ($image)
        <img src="{{ $image }}" alt="" loading="lazy" decoding="async"
            class="absolute inset-y-0 end-0 -z-10 h-full w-3/5 object-cover">
        <div @class([
            'absolute inset-0 -z-10',
            'bg-linear-to-r rtl:bg-linear-to-l from-night from-35% via-night/70 to-transparent' => $dark,
            'bg-linear-to-r rtl:bg-linear-to-l from-linen from-35% via-linen/70 to-transparent' => ! $dark,
        ])></div>
    @endif

    <div class="flex max-w-[22rem] flex-col justify-center gap-4 p-7 sm:p-9">
        <h3 @class(['text-display-md', 'text-ivory' => $dark])>{{ $title }}</h3>
        <p @class(['text-sm leading-relaxed', 'text-ivory/75' => $dark, 'text-ink-soft' => ! $dark])>{{ $text }}</p>
        <div>
            <x-ui.button :href="$href" :variant="$dark ? 'primary' : 'dark'" icon="arrow-right" size="sm">{{ $cta }}</x-ui.button>
        </div>
    </div>
</article>
