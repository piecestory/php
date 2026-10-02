@props(['icon', 'title', 'text'])

<div {{ $attributes->class(['flex items-center gap-4']) }}>
    <x-ui.icon :name="$icon" class="size-8 text-bronze" stroke-width="1.2" />
    <div>
        <p class="text-sm font-semibold text-ink">{{ $title }}</p>
        <p class="text-xs text-ink-soft">{{ $text }}</p>
    </div>
</div>
