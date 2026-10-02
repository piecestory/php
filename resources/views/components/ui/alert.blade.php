@props(['variant' => 'info'])

<div role="{{ $variant === 'danger' ? 'alert' : 'status' }}" {{ $attributes->class([
    'flex items-start gap-3 rounded-xs border px-4 py-3 text-sm',
    match ($variant) {
        'success' => 'border-success/30 bg-success/5 text-success',
        'danger' => 'border-danger/30 bg-danger/5 text-danger',
        'warning' => 'border-warning/30 bg-warning/5 text-warning',
        default => 'border-line bg-paper text-ink-soft',
    },
]) }}>
    <x-ui.icon :name="match ($variant) { 'success' => 'check', 'danger', 'warning' => 'circle-alert', default => 'info' }" class="mt-0.5 size-4" />
    <div class="text-ink">{{ $slot }}</div>
</div>
