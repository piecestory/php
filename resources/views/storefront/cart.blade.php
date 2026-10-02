@php
    use App\Domain\Catalog\Models\Product;
    use App\Support\Localization\LocalizedRoute;
    use App\Support\Money\Money;

    $thumb = fn (Product $product) => $product->responsiveImage(Product::MEDIA_GALLERY)['src'] ?? null;
@endphp

<x-layouts.store :title="__('cart.title')" noindex>
    <div class="container-page py-10 lg:py-14">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('cart.title')]]" />
        <h1 class="mt-6 text-display-lg">{{ __('cart.title') }}</h1>

        @if ($summary->isEmpty())
            <x-ui.empty-state icon="shopping-bag" :title="__('cart.empty')" :text="__('cart.empty_text')" class="mt-8 rounded-xs border border-line bg-paper">
                <x-ui.button :href="localized_route('store')" icon="arrow-right">{{ __('cart.browse') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-8">
                    @if ($summary->lines)
                        <ul class="divide-y divide-line border-y border-line">
                            @foreach ($summary->lines as $line)
                                @php($product = $line->product())
                                <li class="flex gap-4 py-5 sm:gap-6">
                                    <a href="{{ localized_route('product', $product) }}" class="w-24 shrink-0 sm:w-28">
                                        <x-ui.image :src="$thumb($product)" :alt="$product->translate('name')" ratio="1/1" class="rounded-xs border border-line" />
                                    </a>
                                    <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <a href="{{ localized_route('product', $product) }}" class="font-medium hover:text-bronze">{{ $product->translate('name') }}</a>
                                            <p class="mt-1 text-xs text-ink-soft"><span class="numerals">{{ $product->sku }}</span></p>
                                            @if ($product->stock_quantity === 1)
                                                <p class="mt-2 text-xs text-ink-soft">{{ __('cart.unique_piece') }}</p>
                                            @else
                                                <form method="POST" action="{{ localized_route('cart.update', ['product' => $product->id]) }}" class="mt-2 flex items-center gap-2 text-sm">
                                                    @csrf
                                                    <label for="qty-{{ $product->id }}" class="text-ink-soft">{{ __('cart.quantity') }}</label>
                                                    <input id="qty-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->stock_quantity }}"
                                                        value="{{ $line->item->quantity }}" class="form-control h-9 w-20" dir="ltr">
                                                    <x-ui.button type="submit" variant="ghost" size="sm">{{ __('ui.update') }}</x-ui.button>
                                                </form>
                                            @endif
                                        </div>
                                        <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                                            <x-ui.price :amount="$line->lineTotal()" :compare-at="$product->isOnSale() ? bcmul((string) $product->price, (string) $line->item->quantity, 2) : null" />
                                            <form method="POST" action="{{ localized_route('cart.remove', ['product' => $product->id]) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-ink-soft hover:text-danger"
                                                    aria-label="{{ __('cart.remove_item', ['name' => $product->translate('name')]) }}">
                                                    <x-ui.icon name="trash-2" class="size-4" />{{ __('cart.remove') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($summary->unavailable)
                        <section aria-labelledby="unavailable-heading" class="rounded-xs border border-warning/30 bg-warning/5 p-5">
                            <h2 id="unavailable-heading" class="font-sans text-sm font-semibold text-warning">{{ __('cart.unavailable') }}</h2>
                            <p class="mt-1 text-sm text-ink-soft">{{ __('cart.unavailable_text') }}</p>
                            <ul class="mt-4 space-y-3">
                                @foreach ($summary->unavailable as $item)
                                    <li class="flex items-center justify-between gap-4 text-sm">
                                        <span class="text-ink-soft line-through">{{ $item->product->translate('name') }}</span>
                                        <form method="POST" action="{{ localized_route('cart.remove', ['product' => $item->product_id]) }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-ink-soft underline hover:text-danger">{{ __('cart.remove') }}</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    <a href="{{ localized_route('store') }}" class="inline-flex items-center gap-1.5 text-sm text-ink-soft hover:text-bronze">
                        <x-ui.icon name="arrow-left" class="size-4" />{{ __('cart.continue') }}
                    </a>
                </div>

                <aside aria-labelledby="summary-heading" class="h-fit rounded-xs border border-line bg-paper p-6 lg:sticky lg:top-6">
                    <h2 id="summary-heading" class="text-display-sm">{{ __('cart.summary') }}</h2>
                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="font-semibold">{{ __('cart.subtotal') }}</dt>
                            <dd><x-ui.price :amount="$summary->total" size="lg" /></dd>
                        </div>
                        <div class="flex items-center justify-between text-ink-soft">
                            <dt>{{ __('cart.vat_included', ['rate' => config('store.vat_rate')]) }}</dt>
                            <dd class="numerals">{{ Money::amount($summary->vat) }} {{ Money::currency() }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 border-t border-line pt-4 text-xs leading-relaxed text-ink-soft">{{ __('cart.shipping_note') }}</p>
                    @if (LocalizedRoute::has('checkout') && $summary->lines)
                        <x-ui.button :href="localized_route('checkout')" size="lg" icon="arrow-right" class="mt-5 w-full">{{ __('cart.checkout') }}</x-ui.button>
                    @endif
                </aside>
            </div>
        @endif
    </div>
</x-layouts.store>
