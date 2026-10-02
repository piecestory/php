{{-- One order in the customer's lists. The order page opens without a key for its signed-in owner. --}}
@props(['order'])

@php
    use App\Domain\Orders\Enums\OrderStatus;
@endphp

<li {{ $attributes->class(['flex flex-wrap items-center gap-x-6 gap-y-2 px-5 py-4 text-sm']) }}>
    <a href="{{ localized_route('order', ['order' => $order->number]) }}" class="numerals font-medium hover:text-bronze" dir="ltr">{{ $order->number }}</a>
    <span class="text-ink-soft">{{ $order->placed_at?->translatedFormat('j F Y') }}</span>
    <span class="text-ink-soft">{{ trans_choice('account.orders.items', $order->items_count ?? $order->items()->count()) }}</span>
    <x-ui.badge :variant="match ($order->status) { OrderStatus::Cancelled, OrderStatus::Refunded => 'sold', OrderStatus::Pending, OrderStatus::Reserved => 'reserved', default => 'neutral' }">
        {{ $order->status->label() }}
    </x-ui.badge>
    <span class="ms-auto flex items-center gap-4">
        <x-ui.price :amount="$order->grand_total" />
        <a href="{{ localized_route('order', ['order' => $order->number]) }}" class="inline-flex items-center gap-1 text-bronze hover:underline">
            {{ __('account.orders.view') }}<x-ui.icon name="arrow-right" class="size-3.5" />
        </a>
    </span>
</li>
