@php
    use App\Domain\Catalog\Enums\ProductAvailability;
    use App\View\ProductCard;
    use App\View\ProductGallery;

    $name = (string) $product->translate('name');
    $images = ProductGallery::images($product);
    $onRequest = $product->availability === ProductAvailability::OnRequest;
    $description = $product->translate('description');
    $story = $product->translate('story');
    $dimensions = array_filter([$product->width_cm, $product->height_cm, $product->depth_cm], fn ($v) => $v !== null);

    $specs = array_filter([
        __('product.spec.category') => $product->category?->translate('name'),
        __('product.spec.era') => $product->era?->translate('name'),
        __('product.spec.origin') => $product->origin?->translate('name'),
        __('product.spec.materials') => $product->materials->map->translate('name')->join(app()->getLocale() === 'ar' ? '، ' : ', '),
        __('product.spec.condition') => $product->condition ? __('catalog.condition.'.$product->condition->value) : null,
        __('product.spec.dimensions') => $dimensions ? implode(' × ', array_map(fn ($v) => rtrim(rtrim((string) $v, '0'), '.'), $dimensions)).' '.__('product.spec.cm') : null,
        __('product.spec.weight') => $product->weight_kg ? rtrim(rtrim((string) $product->weight_kg, '0'), '.').' '.__('product.spec.kg') : null,
        __('product.sku') => $product->sku,
    ], fn ($v) => $v !== null && $v !== '');

    $crumbs = [
        ['label' => __('ui.home'), 'href' => localized_route('home')],
        ['label' => __('catalog.store.title'), 'href' => localized_route('store')],
    ];
    if ($product->category) {
        $crumbs[] = ['label' => $product->category->translate('name'), 'href' => localized_route('category', $product->category)];
    }
    $crumbs[] = ['label' => $name];
@endphp

<x-layouts.store :title="$product->translate('meta_title') ?: $name"
    :description="$product->translate('meta_description') ?: \Illuminate\Support\Str::limit(strip_tags((string) $description), 160)"
    :image="$images[0]['large'] ?? null" og-type="product">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <div class="container-page py-8 lg:py-12">
        <x-ui.breadcrumbs :items="$crumbs" />

        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-16">
            <x-product.gallery :$images :$name />

            <div class="lg:pt-4">
                @php($card = ProductCard::from($product))
                @if ($card['badges'])
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach ($card['badges'] as $variant => $label)
                            <x-ui.badge :$variant>{{ $label }}</x-ui.badge>
                        @endforeach
                    </div>
                @endif

                <h1 class="text-display-lg">{{ $name }}</h1>
                <p class="mt-2 text-sm text-ink-soft">{{ __('product.sku') }}: <span class="numerals">{{ $product->sku }}</span></p>

                <x-ui.ornament class="mt-6 w-40" />

                <div class="mt-6">
                    @if ($onRequest)
                        <p class="font-display text-display-sm text-bronze-deep">{{ __('product.availability.on_request') }}</p>
                    @else
                        <x-ui.price :amount="$card['price']" :compare-at="$card['compareAt']" size="lg" />
                        <p class="mt-1 text-xs text-ink-soft">{{ __('product.vat_included') }}</p>
                    @endif
                </div>

                <p @class([
                    'mt-5 inline-flex items-center gap-2 text-sm font-medium',
                    'text-success' => $card['purchasable'],
                    'text-ink-soft' => ! $card['purchasable'],
                ])>
                    <span @class(['size-2 rounded-full', 'bg-success' => $card['purchasable'], 'bg-ink-faint' => ! $card['purchasable']])></span>
                    {{ __('product.availability.'.($product->availability === ProductAvailability::Available && ! $card['purchasable'] ? 'sold' : $product->availability->value)) }}
                </p>

                @if ($description)
                    <div class="mt-6 leading-relaxed whitespace-pre-line text-ink-soft">{{ $description }}</div>
                @endif

                <ul class="mt-8 space-y-3 border-t border-line pt-6 text-sm text-ink-soft">
                    <li class="flex items-start gap-3"><x-ui.icon name="truck" class="mt-0.5 size-5 text-bronze" />{{ __('product.services.delivery') }}</li>
                    <li class="flex items-start gap-3"><x-ui.icon name="refresh-ccw" class="mt-0.5 size-5 text-bronze" />{{ __('product.services.returns') }}</li>
                    <li class="flex items-start gap-3"><x-ui.icon name="headset" class="mt-0.5 size-5 text-bronze" />{{ __('product.services.support') }}</li>
                </ul>
            </div>
        </div>

        <div class="mt-16 grid gap-12 lg:mt-20 lg:grid-cols-2 lg:gap-16">
            @if ($story)
                <section aria-labelledby="story-heading">
                    <h2 id="story-heading" class="text-display-md">{{ __('product.story') }}</h2>
                    <x-ui.ornament class="mt-3 w-32" />
                    <div class="mt-5 leading-loose whitespace-pre-line text-ink-soft">{{ $story }}</div>
                </section>
            @endif

            <section aria-labelledby="specs-heading" @class(['lg:col-span-2' => ! $story])>
                <h2 id="specs-heading" class="text-display-md">{{ __('product.specifications') }}</h2>
                <dl class="mt-5 divide-y divide-line border-y border-line">
                    @foreach ($specs as $label => $value)
                        <div class="grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-4 py-3.5 text-sm">
                            <dt class="text-ink-soft">{{ $label }}</dt>
                            <dd class="font-medium text-ink">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-16 lg:mt-20" aria-labelledby="related-heading">
                <x-ui.section-heading :title="__('product.related')" id="related-heading"
                    :href="$product->category ? localized_route('category', $product->category) : null" />
                <ul class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($related as $item)
                        @php($c = ProductCard::from($item))
                        <li>
                            <x-product-card :name="$c['name']" :href="$c['href']" :price="$c['price']" :compare-at="$c['compareAt']"
                                :image="$c['image']" :srcset="$c['srcset']" :badges="$c['badges']" :purchasable="$c['purchasable']" :on-request="$c['onRequest']" />
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.store>
