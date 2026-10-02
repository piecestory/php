{{-- Paginator view: $products->links('partials.pagination') --}}
@if ($paginator->hasPages())
    <nav aria-label="{{ __('catalog.pagination.label') }}" class="flex items-center justify-center gap-1.5 text-sm">
        @if ($paginator->onFirstPage())
            <span class="grid size-10 place-items-center rounded-xs text-ink-faint" aria-hidden="true"><x-ui.icon name="chevron-left" class="size-4" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-10 place-items-center rounded-xs border border-line bg-paper hover:border-bronze hover:text-bronze">
                <x-ui.icon name="chevron-left" class="size-4" /><span class="sr-only">{{ __('catalog.pagination.previous') }}</span>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1 text-ink-soft">{{ $element }}</span>
            @else
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span aria-current="page" class="numerals grid size-10 place-items-center rounded-xs bg-ink font-medium text-ivory">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="{{ __('catalog.pagination.page', ['page' => $page]) }}"
                            class="numerals grid size-10 place-items-center rounded-xs border border-line bg-paper hover:border-bronze hover:text-bronze">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-10 place-items-center rounded-xs border border-line bg-paper hover:border-bronze hover:text-bronze">
                <x-ui.icon name="chevron-right" class="size-4" /><span class="sr-only">{{ __('catalog.pagination.next') }}</span>
            </a>
        @else
            <span class="grid size-10 place-items-center rounded-xs text-ink-faint" aria-hidden="true"><x-ui.icon name="chevron-right" class="size-4" /></span>
        @endif
    </nav>
@endif
