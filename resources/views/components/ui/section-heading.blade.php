@props([
    'title',
    'eyebrow' => null,
    'href' => null,
    'linkLabel' => null,
    'as' => 'h2',
    'id' => null,
])

<div {{ $attributes->class(['flex items-end justify-between gap-6']) }}>
    <div>
        @if ($eyebrow)
            <p class="mb-2 text-xs font-medium tracking-[0.18em] text-bronze uppercase">{{ $eyebrow }}</p>
        @endif
        <{{ $as }} @if ($id) id="{{ $id }}" @endif class="text-display-md">{{ $title }}</{{ $as }}>
    </div>
    @if ($href)
        <a href="{{ $href }}"
            class="group inline-flex shrink-0 items-center gap-1.5 text-sm font-medium text-ink-soft transition-colors hover:text-bronze">
            {{ $linkLabel ?? __('ui.view_all') }}
            <x-ui.icon name="arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5" />
        </a>
    @endif
</div>
