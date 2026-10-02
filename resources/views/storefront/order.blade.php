@php
    use App\Domain\Orders\Enums\OrderPaymentStatus;
    use App\Domain\Orders\Enums\OrderStatus;
    use App\Domain\Orders\Enums\OrderType;
    use App\Support\Money\Money;

    $money = fn (string $amount) => Money::amount($amount).' '.Money::currency();
    $when = fn ($at) => $at?->translatedFormat('j F، g:i a');
    $paid = bccomp((string) $order->amount_paid, '0', 2) > 0;
    $balance = $order->balanceDue();
    $payingDeposit = $order->type === OrderType::DepositReservation && $order->status === OrderStatus::Pending;
    $storeEmail = app(\App\Domain\Settings\StoreSettings::class)->get('store.email');

    [$tone, $message] = match (true) {
        $order->status === OrderStatus::Cancelled => ['warning', __($order->payment_status === OrderPaymentStatus::Refunded ? 'orders.page.cancelled_refunded' : 'orders.page.cancelled')],
        $order->status === OrderStatus::Reserved => ['info', __('orders.page.reserved', ['until' => $when($order->reserved_until), 'deadline' => $when($order->hold_expires_at)])],
        $order->status === OrderStatus::Pending && $payingDeposit => ['info', __('orders.page.awaiting_deposit', ['time' => $order->hold_expires_at?->translatedFormat('g:i a')])],
        $order->status === OrderStatus::Pending => ['info', __('orders.page.awaiting_payment', ['time' => $order->hold_expires_at?->translatedFormat('g:i a')])],
        default => ['success', __('orders.page.confirmed')],
    };
@endphp

<x-layouts.store :title="__('orders.page.title', ['number' => $order->number])" noindex>
    <div class="container-page py-10 lg:py-14">
        <div class="mx-auto max-w-4xl">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm text-ink-soft">{{ $order->type->label() }}</p>
                    <h1 class="mt-1 text-display-lg">{{ __('orders.page.title', ['number' => '']) }}<span class="numerals" dir="ltr">{{ $order->number }}</span></h1>
                    <p class="mt-1 text-sm text-ink-soft">{{ __('orders.page.placed') }}: {{ $when($order->placed_at) }}</p>
                </div>
                <x-ui.badge :variant="$order->status === OrderStatus::Cancelled ? 'sold' : 'reserved'">{{ $order->status->label() }}</x-ui.badge>
            </header>

            <x-ui.alert :variant="$tone" class="mt-6">{{ $message }}</x-ui.alert>

            <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="space-y-8">
                    @if ($paymentMethods !== [])
                        <section aria-labelledby="order-pay" class="rounded-xs border border-bronze/40 bg-paper p-6">
                            <h2 id="order-pay" class="font-sans text-base font-semibold">{{ __('orders.page.pay_title') }}</h2>
                            <form method="POST" action="{{ localized_route('order.pay', ['order' => $order->number]) }}" class="mt-4 space-y-4">
                                @csrf
                                <input type="hidden" name="key" value="{{ $key }}">
                                <fieldset class="grid gap-3 sm:grid-cols-2">
                                    <legend class="sr-only">{{ __('checkout.steps.payment') }}</legend>
                                    @foreach ($paymentMethods as $method)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xs border border-line p-4 has-[:checked]:border-bronze has-[:checked]:bg-bronze/5">
                                            <input type="radio" name="payment_method" value="{{ $method->value }}" class="size-4 accent-bronze" @checked($loop->first)>
                                            <span class="font-medium">{{ $method->label() }}</span>
                                        </label>
                                    @endforeach
                                </fieldset>
                                @error('payment_method')<p class="text-xs text-danger">{{ $message }}</p>@enderror
                                <x-ui.button type="submit" size="lg" class="w-full sm:w-auto">{{ __('orders.page.pay_button', ['amount' => $money($order->nextPaymentAmount())]) }}</x-ui.button>
                            </form>
                            @if ($order->type->isReservation())
                                <p class="mt-3 text-xs text-ink-soft">{{ __('orders.page.pay_in_store') }}</p>
                            @endif
                        </section>
                    @elseif ($order->status === OrderStatus::Reserved)
                        <p class="text-sm text-ink-soft">{{ __('orders.page.pay_in_store') }}</p>
                    @endif

                    <section aria-labelledby="order-items" class="rounded-xs border border-line bg-paper">
                        <h2 id="order-items" class="border-b border-line px-5 py-4 font-sans text-sm font-semibold">{{ __('orders.page.items') }}</h2>
                        <ul class="divide-y divide-line">
                            @foreach ($order->items as $item)
                                <li class="flex items-center gap-4 px-5 py-4">
                                    <x-product.thumb :product="$item->product" class="w-16" />
                                    <span class="min-w-0 flex-1 text-sm">
                                        <span class="block">{{ $item->translate('name') }}</span>
                                        <span class="numerals text-xs text-ink-soft">{{ $item->sku }}@if ($item->quantity > 1) · × {{ $item->quantity }}@endif</span>
                                    </span>
                                    <span class="numerals shrink-0 text-sm font-medium">{{ $money((string) $item->line_total) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                </div>

                <aside class="space-y-6">
                    <dl class="space-y-2.5 rounded-xs border border-line bg-paper p-5 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('orders.page.subtotal') }}</dt><dd class="numerals">{{ $money((string) $order->subtotal) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('orders.page.shipping') }}</dt><dd class="numerals">{{ bccomp((string) $order->shipping_total, '0', 2) === 0 ? __('checkout.free') : $money((string) $order->shipping_total) }}</dd></div>
                        <div class="flex justify-between gap-4 border-t border-line pt-3 text-base font-semibold"><dt>{{ __('orders.page.total') }}</dt><dd class="numerals">{{ $money((string) $order->grand_total) }}</dd></div>
                        <div class="flex justify-between gap-4 text-xs text-ink-soft"><dt>{{ __('orders.page.vat') }}</dt><dd class="numerals">{{ $money((string) $order->tax_total) }}</dd></div>
                        @if ($order->type === OrderType::DepositReservation)
                            <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('orders.page.deposit') }}</dt><dd class="numerals">{{ $money((string) $order->deposit_total) }}</dd></div>
                        @endif
                        @if ($paid)
                            <div class="flex justify-between gap-4 text-success"><dt>{{ __('orders.page.paid') }}</dt><dd class="numerals">{{ $money((string) $order->amount_paid) }}</dd></div>
                        @endif
                        @if ($order->status !== OrderStatus::Cancelled && bccomp($balance, '0', 2) > 0)
                            <div class="flex justify-between gap-4 font-semibold"><dt>{{ __('orders.page.balance') }}</dt><dd class="numerals">{{ $money($balance) }}</dd></div>
                        @endif
                    </dl>

                    <section aria-labelledby="order-fulfilment" class="rounded-xs border border-line bg-paper p-5 text-sm">
                        <h2 id="order-fulfilment" class="font-sans text-sm font-semibold">{{ __('orders.page.fulfilment') }}</h2>
                        @if ($order->pickupBranch)
                            <p class="mt-2 flex items-start gap-2"><x-ui.icon name="store" class="mt-0.5 size-4 shrink-0 text-bronze" />{{ __('orders.page.pickup_from', ['branch' => $order->pickupBranch->translate('name')]) }}</p>
                            @if ($order->pickupBranch->map_url)
                                <a href="{{ $order->pickupBranch->map_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-bronze hover:underline">{{ __('site.footer.directions') }}</a>
                            @endif
                        @else
                            <p class="mt-2 flex items-start gap-2"><x-ui.icon name="truck" class="mt-0.5 size-4 shrink-0 text-bronze" />
                                <span>{{ __('orders.page.delivery_to') }}: {{ $order->ship_street }}، {{ $order->ship_district }}، {{ $order->ship_city }}
                                    <span class="numerals" dir="ltr">{{ $order->ship_postal_code }}</span></span>
                            </p>
                        @endif
                    </section>

                    <p class="text-xs leading-relaxed text-ink-soft">{{ __('orders.page.save_link') }}</p>
                    @if ($storeEmail)
                        <p class="text-xs text-ink-soft">{{ __('orders.page.questions', ['email' => '']) }}<a href="mailto:{{ $storeEmail }}" class="text-bronze hover:underline" dir="ltr">{{ $storeEmail }}</a></p>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-layouts.store>
