@php use App\View\ProductCard; @endphp

<x-layouts.store :title="__('wishlist.title')" noindex>
    <div class="container-page py-10 lg:py-14">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('wishlist.title')]]" />
        <header class="mt-6">
            <h1 class="text-display-lg">{{ __('wishlist.title') }}</h1>
            <p class="mt-2 text-ink-soft">{{ __('wishlist.intro') }}</p>
            @guest
                @if ($products->isNotEmpty())
                    <p class="mt-3 text-sm">
                        <a href="{{ localized_route('login') }}" class="text-bronze hover:underline">{{ __('wishlist.guest_note') }}</a>
                    </p>
                @endif
            @endguest
        </header>

        @if ($products->isEmpty())
            <x-ui.empty-state icon="heart" :title="__('wishlist.empty')" :text="__('wishlist.empty_text')" class="mt-8 rounded-xs border border-line bg-paper">
                <x-ui.button variant="outline" :href="localized_route('store')">{{ __('cart.browse') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <ul class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    @php($card = ProductCard::from($product))
                    <li>
                        <x-product-card :name="$card['name']" :href="$card['href']" :price="$card['price']" :compare-at="$card['compareAt']"
                            :image="$card['image']" :srcset="$card['srcset']" :badges="$card['badges']" :purchasable="$card['purchasable']"
                            :on-request="$card['onRequest']" :product-id="$card['productId']" :wishlisted="$card['wishlisted']" />
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.store>
