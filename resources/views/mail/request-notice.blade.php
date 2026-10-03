<x-mail.layout :title="__($key.'.subject', ['reference' => $serviceRequest->requestReference()])">
    <p style="margin:0 0 8px;">{{ __('orders.notice.greeting', ['name' => $serviceRequest->contactName()]) }}</p>
    <h1 style="margin:0 0 12px;font-family:Georgia,serif;font-size:22px;font-weight:normal;color:#1c1612;">{{ __($key.'.heading') }}</h1>
    <p style="margin:0;">{{ __($key.'.body') }}</p>
    @if ($staffMessage)
        <p style="margin:16px 0 0;padding:16px;background:#f7f2ea;white-space:pre-line;">{{ $staffMessage }}</p>
    @endif
    <p style="margin:16px 0 0;color:#5e544b;font-size:13px;">{{ __('requests.reference') }}: <span dir="ltr">{{ $serviceRequest->requestReference() }}</span></p>
    @if ($email = app(\App\Domain\Settings\StoreSettings::class)->get('store.email'))
        <p style="margin:16px 0 0;color:#5e544b;font-size:13px;">{{ __('orders.notice.questions', ['email' => $email]) }}</p>
    @endif
</x-mail.layout>
