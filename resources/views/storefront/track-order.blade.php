@php
    use App\Domain\Orders\Enums\OrderStatus;

    $order ??= null;
    $journey = [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];
@endphp

<x-layouts.store :title="__('orders.track.title')" :description="__('orders.track.intro')">
    <div class="container-page py-12 lg:py-16">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('orders.track.title')]]" />

        @if (! $order)
            <div class="mx-auto mt-8 max-w-md">
                <h1 class="text-center text-display-lg">{{ __('orders.track.title') }}</h1>
                <p class="mt-3 text-center text-sm text-ink-soft">{{ __('orders.track.intro') }}</p>
                <form method="POST" action="{{ localized_route('track-order.lookup') }}" class="mt-8 space-y-5 rounded-xs border border-line bg-paper p-6 sm:p-8">
                    @csrf
                    <x-form.input name="number" :label="__('orders.track.number')" :hint="__('orders.track.number_hint')" dir="ltr" autocomplete="off" required autofocus />
                    <x-form.input name="phone" type="tel" :label="__('orders.track.phone')" inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" required />
                    <x-ui.button type="submit" class="w-full">{{ __('orders.track.submit') }}</x-ui.button>
                </form>
            </div>
        @else
            <div class="mx-auto mt-8 max-w-3xl space-y-8">
                <header class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 class="text-display-lg">{{ __('orders.track.order', ['number' => '']) }}<span class="numerals">{{ $order->number }}</span></h1>
                        <p class="mt-1 text-sm text-ink-soft">{{ __('orders.track.placed') }}: {{ $order->placed_at?->translatedFormat('j F Y') }}</p>
                    </div>
                    <x-ui.badge :variant="$order->status === OrderStatus::Cancelled ? 'sold' : 'reserved'">{{ $order->status->label() }}</x-ui.badge>
                </header>

                @if (in_array($order->status, $journey, true))
                    @php($reached = array_search($order->status, $journey, true))
                    <ol class="grid grid-cols-5 gap-2" aria-label="{{ __('orders.track.current') }}">
                        @foreach ($journey as $step)
                            <li class="space-y-2 text-center" @if ($loop->index === $reached) aria-current="step" @endif>
                                <span @class(['block h-1 rounded-full', 'bg-bronze' => $loop->index <= $reached, 'bg-line' => $loop->index > $reached])></span>
                                <span @class(['block text-[0.6875rem] sm:text-xs', 'font-semibold text-ink' => $loop->index === $reached, 'text-ink-soft' => $loop->index !== $reached])>{{ $step->label() }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if ($shipment = $order->shipments->last())
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xs border border-line bg-paper p-5 text-sm">
                        <span>{{ __('orders.track.tracking') }}: <span class="numerals font-medium">{{ $shipment->carrier }} {{ $shipment->tracking_number }}</span></span>
                        @if ($shipment->tracking_url)
                            <a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener" class="text-bronze hover:underline">{{ __('orders.track.carrier_link') }}</a>
                        @endif
                    </div>
                @endif

                <section class="rounded-xs border border-line bg-paper" aria-labelledby="order-items">
                    <h2 id="order-items" class="border-b border-line px-5 py-4 font-sans text-sm font-semibold">{{ __('orders.track.items') }}</h2>
                    <ul class="divide-y divide-line">
                        @foreach ($order->items as $item)
                            <li class="flex items-center justify-between gap-4 px-5 py-4 text-sm">
                                <span>{{ $item->translate('name') }} <span class="text-ink-soft">× {{ $item->quantity }}</span></span>
                                <x-ui.price :amount="$item->line_total" />
                            </li>
                        @endforeach
                    </ul>
                    <div class="flex items-center justify-between border-t border-line px-5 py-4 font-semibold">
                        <span>{{ __('orders.track.total') }}</span>
                        <x-ui.price :amount="$order->grand_total" />
                    </div>
                </section>

                @if ($order->statusChanges->isNotEmpty())
                    <section aria-labelledby="order-history">
                        <h2 id="order-history" class="mb-4 font-sans text-sm font-semibold">{{ __('orders.track.history') }}</h2>
                        <ol class="space-y-3 border-s border-line ps-5 text-sm">
                            @foreach ($order->statusChanges as $change)
                                <li>
                                    <span class="font-medium">{{ OrderStatus::from($change->to_status)->label() }}</span>
                                    <span class="text-ink-soft">— {{ $change->created_at?->translatedFormat('j F Y، g:i a') }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <x-ui.button variant="outline" :href="localized_route('track-order')">{{ __('orders.track.search_again') }}</x-ui.button>
            </div>
        @endif
    </div>
</x-layouts.store>
