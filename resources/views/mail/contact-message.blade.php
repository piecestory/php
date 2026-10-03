<x-mail.layout :title="__('contact.mail.subject', ['name' => $contactMessage->name])">
    <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:22px;font-weight:normal;">{{ __('contact.mail.heading') }}</h1>
    <p style="margin:0;">
        {{ __('contact.fields.name') }}: {{ $contactMessage->name }}<br>
        @if ($contactMessage->phone){{ __('contact.fields.phone') }}: <span dir="ltr">{{ \App\Support\Phone\SaudiMobile::local($contactMessage->phone) }}</span><br>@endif
        @if ($contactMessage->email){{ __('contact.fields.email') }}: <span dir="ltr">{{ $contactMessage->email }}</span><br>@endif
        @if ($contactMessage->subject){{ __('contact.fields.subject') }}: {{ $contactMessage->subject }}@endif
    </p>
    <p style="margin:16px 0 0;padding:16px;background:#f7f2ea;white-space:pre-line;">{{ $contactMessage->message }}</p>
</x-mail.layout>
