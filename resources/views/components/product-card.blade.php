@props([
    'name',
    'href' => null,
    'price',
    'compareAt' => null,
    'image' => null,
    'srcset' => null,
    'badges' => [],
    'wishlisted' => false,
    'purchasable' => true,
    'onRequest' => false,
    'productId' => null,
    'eager' => false, // first cards of a listing: they are the largest thing on a phone's first screen
])

<article {{ $attributes->class(['group relative flex flex-col']) }}>
    <div class="relative overflow-hidden rounded-xs border border-line bg-paper">
        @if ($href)<a href="{{ $href }}" tabindex="-1" aria-hidden="true">@endif
            <x-ui.image :src="$image" :srcset="$srcset" sizes="(min-width: 1024px) 18vw, (min-width: 640px) 30vw, 46vw"
                :alt="$name" ratio="4/5" :$eager
                class="transition-transform duration-500 ease-elegant group-hover:scale-[1.03]" />
        @if ($href)</a>@endif

        @if ($badges)
            <div class="absolute start-2.5 top-2.5 flex flex-wrap gap-1.5">
                @foreach ($badges as $variant => $label)
                    <x-ui.badge :variant="$variant">{{ $label }}</x-ui.badge>
                @endforeach
            </div>
        @endif

        @if ($productId)
            <x-shop.wishlist-button :product-id="$productId" :$name :saved="$wishlisted" class="absolute end-2.5 top-2.5" />
            @if ($purchasable)
                <x-shop.add-to-cart :product-id="$productId" :$name class="absolute start-2.5 bottom-2.5" />
            @endif
        @endif
    </div>

    <div class="mt-3 space-y-1 text-center">
        <h3 class="font-sans text-[0.9375rem] leading-snug font-medium">
            @if ($href)
                <a href="{{ $href }}" class="line-clamp-2 hover:text-bronze">{{ $name }}</a>
            @else
                <span class="line-clamp-2">{{ $name }}</span>
            @endif
        </h3>
        @if ($onRequest)
            <p class="text-sm font-medium text-bronze-deep">{{ __('ui.badge.on_request') }}</p>
        @else
            <x-ui.price :amount="$price" :compare-at="$compareAt" class="justify-center" />
        @endif
    </div>
</article>
