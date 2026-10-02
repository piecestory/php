@props(['name', 'href', 'image' => null])

<a href="{{ $href }}" {{ $attributes->class([
    'group flex min-w-0 items-center gap-3 rounded-xs border border-line bg-paper p-3 transition-[border-color,box-shadow] duration-300',
    'hover:border-line-strong hover:shadow-raised',
]) }}>
    <x-ui.image :src="$image" alt="" ratio="1/1" fit="contain" class="w-16 shrink-0 bg-transparent sm:w-20 xl:w-16" />
    <span class="min-w-0">
        <span class="block font-display text-[1.0625rem] leading-snug text-ink [overflow-wrap:anywhere]">{{ $name }}</span>
        <span class="mt-1 inline-flex items-center gap-1 text-xs whitespace-nowrap text-ink-soft transition-colors group-hover:text-bronze">
            {{ __('ui.shop_now') }}
            <x-ui.icon name="arrow-right" class="size-3.5" />
        </span>
    </span>
</a>
