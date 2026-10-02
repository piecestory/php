@php($text = \App\Domain\Orders\Actions\NotifyCustomer::replacements($order))
<x-mail.layout :title="__('orders.alert.'.$notice->value, $text)">
    <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:22px;font-weight:normal;">{{ __('orders.alert.'.$notice->value, $text) }}</h1>
    <p style="margin:0;">
        {{ __('orders.alert.customer') }}: {{ $order->customer_name }} — <span dir="ltr">{{ \App\Support\Phone\SaudiMobile::local($order->phone) }}</span><br>
        {{ __('orders.alert.type') }}: {{ $order->type->label() }} ({{ $order->payment_status->label() }})<br>
        {{ __('orders.alert.fulfilment') }}:
        @if ($order->pickupBranch)
            {{ __('orders.page.pickup_from', ['branch' => $order->pickupBranch->translate('name')]) }}
        @else
            {{ __('orders.page.delivery_to') }} {{ $order->ship_city }}، {{ $order->ship_district }}
        @endif
        @if ($order->reserved_until)
            <br>{{ __('orders.page.reserved', $text) }}
        @endif
    </p>
    @if ($order->customer_note)
        <p style="margin:12px 0 0;padding:12px;background:#f7f2ea;">{{ $order->customer_note }}</p>
    @endif

    @include('mail.partials.items')
</x-mail.layout>
