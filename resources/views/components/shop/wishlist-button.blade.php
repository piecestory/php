@props(['productId', 'name', 'saved' => false, 'full' => false])

<form method="POST" action="{{ localized_route('wishlist.toggle', ['product' => $productId]) }}" data-product="{{ $productId }}"
    data-saved="{{ $saved ? 'true' : 'false' }}" x-data="shopForm" x-on:submit.prevent="submit"
    x-on:wishlist-updated.window="syncWishlist" {{ $attributes }}>
    @csrf
    @if ($full)
        <button type="submit" aria-pressed="{{ $saved ? 'true' : 'false' }}" :aria-pressed="saved" :aria-busy="busy"
            class="group inline-flex h-14 w-full items-center justify-center gap-2.5 rounded-xs border border-ink/80 px-6 font-medium text-ink transition-colors hover:bg-ink hover:text-ivory">
            <x-ui.icon name="heart" class="size-5 group-aria-pressed:fill-bronze group-aria-pressed:text-bronze group-aria-pressed:group-hover:fill-ivory group-aria-pressed:group-hover:text-ivory" />
            <span class="group-aria-pressed:hidden">{{ __('wishlist.save') }}</span>
            <span class="hidden group-aria-pressed:inline">{{ __('wishlist.saved') }}</span>
        </button>
    @else
        <button type="submit" aria-pressed="{{ $saved ? 'true' : 'false' }}" :aria-pressed="saved" :aria-busy="busy"
            aria-label="{{ __('wishlist.save') }}: {{ $name }}"
            class="group grid size-9 place-items-center rounded-full bg-paper/90 text-ink shadow-card backdrop-blur-sm transition-colors hover:text-bronze">
            <x-ui.icon name="heart" class="size-[1.1rem] group-aria-pressed:fill-bronze group-aria-pressed:text-bronze" />
        </button>
    @endif
</form>
