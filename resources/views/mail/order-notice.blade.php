@php($text = \App\Domain\Orders\Actions\NotifyCustomer::replacements($order))
<x-mail.layout :title="__('orders.notice.'.$notice->value.'.subject', $text)">
    <p style="margin:0 0 8px;">{{ __('orders.notice.greeting', $text) }}</p>
    <h1 style="margin:0 0 12px;font-family:Georgia,serif;font-size:22px;font-weight:normal;color:#1c1612;">{{ __('orders.notice.'.$notice->value.'.heading') }}</h1>
    <p style="margin:0;">{{ __('orders.notice.'.$notice->value.'.body', $text) }}</p>
    <p style="margin:16px 0 0;color:#5e544b;font-size:13px;">{{ __('orders.track.number') }}: <span dir="ltr">{{ $order->number }}</span></p>

    @include('mail.partials.items')

    <p style="margin:24px 0;text-align:center;">
        <a href="{{ $link }}" style="display:inline-block;background:#1c1612;color:#f7f2ea;text-decoration:none;padding:12px 28px;font-size:15px;">{{ __('orders.notice.view_order') }}</a>
    </p>
    @if ($email = app(\App\Domain\Settings\StoreSettings::class)->get('store.email'))
        <p style="margin:0;color:#5e544b;font-size:13px;">{{ __('orders.notice.questions', ['email' => $email]) }}</p>
    @endif
</x-mail.layout>
