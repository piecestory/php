@props(['productId', 'name', 'full' => false])

<form method="POST" action="{{ localized_route('cart.add', ['product' => $productId]) }}" data-product="{{ $productId }}"
    x-data="shopForm" x-on:submit.prevent="submit" {{ $attributes }}>
    @csrf
    @if ($full)
        <x-ui.button type="submit" size="lg" icon="shopping-bag" class="w-full" ::aria-busy="busy">{{ __('cart.add') }}</x-ui.button>
    @else
        <button type="submit" :aria-busy="busy" aria-label="{{ __('cart.add') }}: {{ $name }}"
            class="grid size-9 place-items-center rounded-full bg-paper/90 text-ink shadow-card backdrop-blur-sm transition-colors hover:bg-ink hover:text-ivory aria-busy:opacity-60">
            <x-ui.icon name="shopping-bag" class="size-[1.1rem]" />
        </button>
    @endif
</form>
