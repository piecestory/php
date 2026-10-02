@php
    use App\Support\Localization\LocalizedRoute;
    use App\Support\Money\Money;
    use App\Support\Phone\SaudiMobile;

    $card = 'rounded-xs border border-line bg-paper';
@endphp

<x-account.layout :title="__('account.nav.overview')" :heading="__('account.overview.greeting', ['name' => $user->name])">
    <p class="-mt-4 mb-8 text-ink-soft">{{ __('account.overview.intro') }}</p>

    @if ($awaitingPayment->isNotEmpty())
        <section aria-labelledby="awaiting" class="mb-8 space-y-3">
            <h2 id="awaiting" class="font-sans text-sm font-semibold text-bronze-deep">{{ __('account.overview.awaiting') }}</h2>
            @foreach ($awaitingPayment as $order)
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-xs border border-bronze/40 bg-bronze/5 p-5">
                    <div>
                        <p class="numerals font-medium" dir="ltr">{{ $order->number }}</p>
                        <p class="mt-1 text-sm text-ink-soft">{{ $order->type->label() }} · {{ __('account.overview.pay_until', ['date' => $order->hold_expires_at?->translatedFormat('j F، g:i a')]) }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="numerals font-semibold">{{ Money::amount($order->nextPaymentAmount()) }} {{ Money::currency() }}</span>
                        <x-ui.button :href="localized_route('order', ['order' => $order->number])" size="sm">{{ __('account.overview.pay_now') }}</x-ui.button>
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
        <section aria-labelledby="recent" class="{{ $card }}">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 id="recent" class="font-sans text-sm font-semibold">{{ __('account.overview.recent') }}</h2>
                @if ($recentOrders->isNotEmpty())
                    <a href="{{ localized_route('account.orders') }}" class="text-sm text-bronze hover:underline">{{ __('account.overview.all_orders') }}</a>
                @endif
            </div>
            @if ($recentOrders->isEmpty())
                <x-ui.empty-state icon="package-check" :title="__('account.orders.empty')" :text="__('account.orders.empty_text')">
                    <x-ui.button :href="localized_route('store')" variant="outline" size="sm">{{ __('account.orders.browse') }}</x-ui.button>
                </x-ui.empty-state>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($recentOrders as $order)
                        <x-account.order-row :$order />
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="space-y-6">
            <section aria-labelledby="default-address" class="{{ $card }} p-5 text-sm">
                <h2 id="default-address" class="font-sans text-sm font-semibold">{{ __('account.overview.default_address') }}</h2>
                @if ($defaultAddress)
                    <x-account.address-lines :address="$defaultAddress" class="mt-3" />
                    <a href="{{ localized_route('account.addresses') }}" class="mt-3 inline-block text-bronze hover:underline">{{ __('account.nav.addresses') }}</a>
                @else
                    <p class="mt-3 text-ink-soft">{{ __('account.overview.no_address') }}</p>
                    <a href="{{ localized_route('account.addresses.create') }}" class="mt-3 inline-block text-bronze hover:underline">{{ __('account.overview.add_address') }}</a>
                @endif
            </section>

            @if (LocalizedRoute::has('wishlist'))
                <section class="{{ $card }} flex items-center justify-between gap-4 p-5 text-sm">
                    <span class="flex items-center gap-2"><x-ui.icon name="heart" class="size-4 text-bronze" />{{ trans_choice('account.overview.wishlist_count', $wishlistCount) }}</span>
                    @if ($wishlistCount > 0)
                        <a href="{{ localized_route('wishlist') }}" class="shrink-0 text-bronze hover:underline">{{ __('account.overview.view_wishlist') }}</a>
                    @endif
                </section>
            @endif

            <section class="{{ $card }} p-5 text-sm">
                <p class="font-medium">{{ $user->name }}</p>
                @if ($user->phone)<p class="numerals mt-1 text-ink-soft" dir="ltr">{{ SaudiMobile::local($user->phone) }}</p>@endif
                @if ($user->email)<p class="mt-1 text-ink-soft" dir="ltr">{{ $user->email }}</p>@endif
                <a href="{{ localized_route('account.profile') }}" class="mt-3 inline-block text-bronze hover:underline">{{ __('account.nav.profile') }}</a>
            </section>
        </div>
    </div>
</x-account.layout>
