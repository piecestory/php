{{-- $items: list of ['label' => string, 'href' => ?string]; the last item is the current page. --}}
@props(['items'])

<nav aria-label="{{ __('ui.breadcrumbs') }}" {{ $attributes->class(['text-xs text-ink-soft']) }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->first)
                    <x-ui.icon name="chevron-right" class="size-3 text-ink-faint" />
                @endif
                @if (! $loop->last && ($item['href'] ?? null))
                    <a href="{{ $item['href'] }}" class="hover:text-bronze">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="text-ink">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
