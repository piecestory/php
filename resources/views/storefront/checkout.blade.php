@php
    use App\Domain\Orders\Enums\OrderType;
    use App\Support\Money\Money;

    $address = $defaults['address'];
    $selectedMethod = (string) old('shipping_method', $methods->first()?->id);
    $selectedType = old('order_type', $types[0]->value);
    $selectedPayment = old('payment_method', $paymentMethods[0]->value ?? null);
    $initial = $totals["{$selectedMethod}:{$selectedType}"] ?? null;
    $hasDeposit = in_array(OrderType::DepositReservation, $types, true);
    $choice = 'flex cursor-pointer gap-3 rounded-xs border border-line bg-paper p-4 transition-colors hover:border-line-strong has-[:checked]:border-bronze has-[:checked]:bg-bronze/5 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50';
    $radio = 'mt-1 size-4 shrink-0 accent-bronze';
    $sectionTitle = 'flex items-center gap-3 font-sans text-base font-semibold';
    $step = 'grid size-7 place-items-center rounded-full bg-ink text-xs text-ivory numerals';
@endphp

<x-layouts.store :title="__('checkout.title')" noindex>
    <div class="container-page py-10 lg:py-14">
        <x-ui.breadcrumbs :items="[['label' => __('ui.home'), 'href' => localized_route('home')], ['label' => __('cart.title'), 'href' => localized_route('cart')], ['label' => __('checkout.title')]]" />
        <h1 class="mt-6 text-display-lg">{{ __('checkout.title') }}</h1>

        @if ($errors->any())
            <x-ui.alert variant="danger" class="mt-6">{{ __('checkout.errors.review') }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ localized_route('checkout.store') }}" novalidate
            x-data="checkout" x-on:change="choose"
            data-totals="{{ json_encode($totals, JSON_UNESCAPED_UNICODE) }}"
            data-pickup-methods="{{ $methods->where('requires_pickup_branch', true)->pluck('id')->implode(',') }}"
            class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_24rem]">
            @csrf

            <div class="space-y-10">
                {{-- 1. Contact --}}
                <section aria-labelledby="step-contact" class="space-y-5">
                    <h2 id="step-contact" class="{{ $sectionTitle }}"><span class="{{ $step }}">1</span>{{ __('checkout.steps.contact') }}</h2>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="name" :label="__('checkout.attributes.name')" :value="$defaults['name']" autocomplete="name" required class="sm:col-span-2" />
                        <x-form.input name="phone" type="tel" :label="__('checkout.attributes.phone')" :value="$defaults['phone']" :hint="__('checkout.phone_hint')"
                            inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" autocomplete="tel" required />
                        <x-form.input name="email" type="email" :label="__('checkout.attributes.email')" :value="$defaults['email']" :hint="__('checkout.email_hint')"
                            dir="ltr" autocomplete="email" />
                    </div>
                </section>

                {{-- 2. Pickup or delivery --}}
                <section aria-labelledby="step-fulfilment" class="space-y-5">
                    <h2 id="step-fulfilment" class="{{ $sectionTitle }}"><span class="{{ $step }}">2</span>{{ __('checkout.steps.fulfilment') }}</h2>
                    <fieldset class="grid gap-3 sm:grid-cols-2">
                        <legend class="sr-only">{{ __('checkout.steps.fulfilment') }}</legend>
                        @foreach ($methods as $method)
                            <label class="{{ $choice }}">
                                <input type="radio" name="shipping_method" value="{{ $method->id }}" class="{{ $radio }}" @checked($selectedMethod === (string) $method->id)>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-baseline justify-between gap-3 font-medium">
                                        {{ $method->translate('name') }}
                                        <span class="numerals text-sm text-ink-soft">{{ bccomp((string) $method->rate, '0', 2) === 0 ? __('checkout.free') : Money::amount((string) $method->rate).' '.Money::currency() }}</span>
                                    </span>
                                    @if ($method->free_shipping_threshold !== null && bccomp((string) $method->rate, '0', 2) > 0)
                                        <span class="mt-1 block text-sm text-success">{{ __('checkout.free_over', ['amount' => Money::amount((string) $method->free_shipping_threshold).' '.Money::currency()]) }}</span>
                                    @endif
                                    @if ($method->translate('description'))
                                        <span class="mt-1 block text-sm text-ink-soft">{{ $method->translate('description') }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                    @error('shipping_method')<p class="text-xs text-danger">{{ $message }}</p>@enderror

                    @if ($methods->contains('requires_pickup_branch', true))
                        <div x-show="isPickup">
                            <x-form.select name="pickup_branch" :label="__('checkout.pickup_branch')" :placeholder="__('checkout.choose_branch')"
                                :options="$branches->mapWithKeys(fn ($b) => [$b->id => $b->translate('name')])->all()"
                                :value="$branches->count() === 1 ? $branches->first()->id : null" required />
                        </div>
                    @endif

                    @if ($methods->contains('requires_pickup_branch', false))
                        <fieldset x-show="isDelivery" class="space-y-5 rounded-xs border border-line p-5">
                            <legend class="px-2 text-sm font-semibold">{{ __('checkout.address_title') }}</legend>
                            <p class="text-xs text-ink-soft">{{ __('checkout.address_hint') }}</p>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-form.input name="address[city]" :label="__('checkout.attributes.address.city')" :value="$address?->city" autocomplete="address-level2" />
                                <x-form.input name="address[district]" :label="__('checkout.attributes.address.district')" :value="$address?->district" autocomplete="address-level3" />
                                <x-form.input name="address[street]" :label="__('checkout.attributes.address.street')" :value="$address?->street" autocomplete="address-line1" class="sm:col-span-2" />
                                <x-form.input name="address[building_number]" :label="__('checkout.attributes.address.building_number')" :value="$address?->building_number" :hint="__('checkout.hints.building_number')" inputmode="numeric" dir="ltr" maxlength="4" />
                                <x-form.input name="address[postal_code]" :label="__('checkout.attributes.address.postal_code')" :value="$address?->postal_code" :hint="__('checkout.hints.postal_code')" inputmode="numeric" dir="ltr" maxlength="5" autocomplete="postal-code" />
                                <x-form.input name="address[additional_number]" :label="__('checkout.attributes.address.additional_number')" :value="$address?->additional_number" inputmode="numeric" dir="ltr" maxlength="4" />
                                <x-form.input name="address[short_address]" :label="__('checkout.attributes.address.short_address')" :value="$address?->short_address" :hint="__('checkout.hints.short_address')" dir="ltr" maxlength="8" />
                            </div>
                        </fieldset>
                    @endif
                </section>

                {{-- 3. Buy or reserve --}}
                <section aria-labelledby="step-type" class="space-y-5">
                    <h2 id="step-type" class="{{ $sectionTitle }}"><span class="{{ $step }}">3</span>{{ __('checkout.steps.type') }}</h2>
                    @if ($paymentMethods === [])
                        <x-ui.alert>{{ __('checkout.no_online_payment') }}</x-ui.alert>
                    @endif
                    <fieldset class="grid gap-3">
                        <legend class="sr-only">{{ __('checkout.steps.type') }}</legend>
                        @foreach ($types as $type)
                            <label class="{{ $choice }}">
                                <input type="radio" name="order_type" value="{{ $type->value }}" class="{{ $radio }}" @checked($selectedType === $type->value)>
                                <span>
                                    <span class="block font-medium">{{ __('checkout.type.'.$type->value, ['percent' => config('store.reservation.deposit_percent')]) }}</span>
                                    <span class="mt-1 block text-sm text-ink-soft">{{ __('checkout.type.'.$type->value.'_text', [
                                        'days' => config('store.reservation.hold_days'),
                                        'reminder' => config('store.reservation.reminder_days'),
                                    ]) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                    @error('order_type')<p class="text-xs text-danger">{{ $message }}</p>@enderror
                </section>

                {{-- 4. Payment method --}}
                @if ($paymentMethods !== [])
                    <section aria-labelledby="step-payment" class="space-y-5" x-show="needsPayment">
                        <h2 id="step-payment" class="{{ $sectionTitle }}"><span class="{{ $step }}">4</span>{{ __('checkout.steps.payment') }}</h2>
                        <fieldset class="grid gap-3 sm:grid-cols-2">
                            <legend class="sr-only">{{ __('checkout.steps.payment') }}</legend>
                            @foreach ($paymentMethods as $method)
                                <label class="{{ $choice }} items-center">
                                    <input type="radio" name="payment_method" value="{{ $method->value }}" class="{{ $radio }} mt-0" @checked($selectedPayment === $method->value)
                                        @unless ($method->canPayDeposit()) x-bind:disabled="isDeposit" @endunless>
                                    <span class="font-medium">{{ $method->label() }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                        @error('payment_method')<p class="text-xs text-danger">{{ $message }}</p>@enderror
                        @if ($hasDeposit)
                            <p x-show="isDeposit" class="text-xs text-ink-soft">{{ __('checkout.bnpl_deposit_note') }}</p>
                        @endif
                        <p class="flex items-start gap-2 text-xs text-ink-soft"><x-ui.icon name="shield-check" class="mt-0.5 size-3.5 shrink-0" />{{ __('checkout.secure_note') }}</p>
                    </section>
                @endif

                {{-- Note --}}
                <section aria-labelledby="step-note">
                    <h2 id="step-note" class="sr-only">{{ __('checkout.steps.note') }}</h2>
                    <x-form.textarea name="note" :label="__('checkout.steps.note')" rows="3" :placeholder="__('checkout.note_placeholder')" maxlength="1000" />
                </section>
            </div>

            {{-- Summary --}}
            <aside aria-labelledby="checkout-summary" class="h-fit rounded-xs border border-line bg-paper p-6 lg:sticky lg:top-6">
                <h2 id="checkout-summary" class="text-display-sm">{{ __('checkout.summary') }}</h2>
                <ul class="mt-5 divide-y divide-line border-y border-line">
                    @foreach ($summary->lines as $line)
                        @php($product = $line->product())
                        <li class="flex items-center gap-3 py-3">
                            <x-product.thumb :$product sizes="3.5rem" class="w-14" />
                            <span class="min-w-0 flex-1 text-sm">
                                <span class="line-clamp-2">{{ $product->translate('name') }}</span>
                                @if ($line->item->quantity > 1)<span class="text-ink-soft">× {{ $line->item->quantity }}</span>@endif
                            </span>
                            <span class="numerals shrink-0 text-sm font-medium">{{ Money::amount($line->lineTotal()) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-soft">{{ __('checkout.subtotal') }}</dt>
                        <dd class="numerals">{{ Money::amount($summary->total) }} {{ Money::currency() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-soft">{{ __('checkout.shipping') }}</dt>
                        <dd class="numerals" x-text="shippingText">{{ $initial['shipping'] ?? '' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-line pt-3 text-base font-semibold">
                        <dt>{{ __('checkout.total') }}</dt>
                        <dd class="numerals" x-text="totalText">{{ $initial['total'] ?? '' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 text-xs text-ink-soft">
                        <dt>{{ __('checkout.vat_included') }}</dt>
                        <dd class="numerals" x-text="vatText">{{ $initial['vat'] ?? '' }}</dd>
                    </div>
                    @if ($hasDeposit)
                        <div x-show="isDeposit" @if ($selectedType !== OrderType::DepositReservation->value) x-cloak @endif
                            class="flex justify-between gap-4 rounded-xs bg-bronze/10 px-3 py-2.5 font-semibold text-bronze-deep">
                            <dt>{{ __('checkout.deposit_now') }}</dt>
                            <dd class="numerals" x-text="depositText">{{ $initial['deposit'] ?? '' }}</dd>
                        </div>
                    @endif
                </dl>

                <x-ui.button type="submit" size="lg" class="mt-6 w-full">
                    <span x-show="needsPayment" @if ($selectedType === OrderType::Reservation->value) x-cloak @endif>{{ __('checkout.submit.pay') }}</span>
                    <span x-show="reservesOnly" @if ($selectedType !== OrderType::Reservation->value) x-cloak @endif>{{ __('checkout.submit.reserve') }}</span>
                </x-ui.button>
                <p class="mt-4 text-xs leading-relaxed text-ink-soft">
                    {{ __('checkout.returns_note') }}
                    @if (\App\View\Navigation::hasContent('returns-policy'))
                        <a href="{{ localized_route('returns-policy') }}" target="_blank" class="text-bronze underline">{{ __('site.nav.returns_policy') }}</a>
                    @endif
                </p>
            </aside>
        </form>
    </div>
</x-layouts.store>
