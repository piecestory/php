<x-mail.layout :title="__('requests.'.$serviceRequest->requestKind().'.alert', ['reference' => $serviceRequest->requestReference()])">
    <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:22px;font-weight:normal;">{{ __('requests.'.$serviceRequest->requestKind().'.alert', ['reference' => $serviceRequest->requestReference()]) }}</h1>
    <p style="margin:0;">
        {{ __('requests.fields.name') }}: {{ $serviceRequest->contactName() }} — <span dir="ltr">{{ \App\Support\Phone\SaudiMobile::local($serviceRequest->contactPhone()) }}</span><br>
        {{ __('requests.alert_hint') }}
    </p>
    <p style="margin:16px 0 0;padding:16px;background:#f7f2ea;white-space:pre-line;">{{ $serviceRequest->getAttribute('description') }}</p>
</x-mail.layout>
