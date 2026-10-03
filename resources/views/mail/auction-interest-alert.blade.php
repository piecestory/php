@php $auctionTitle = $interest->auction->translate('title'); @endphp
<x-mail.layout :title="__('auctions.interest.alert', ['auction' => $auctionTitle])">
    <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:22px;font-weight:normal;">{{ __('auctions.interest.alert', ['auction' => $auctionTitle]) }}</h1>
    <p style="margin:0;">
        {{ __('auctions.fields.name') }}: {{ $interest->name }} — <span dir="ltr">{{ \App\Support\Phone\SaudiMobile::local($interest->phone) }}</span><br>
        @if ($interest->lot)
            {{ __('auctions.fields.lot') }}: {{ __('auctions.lot_number', ['number' => $interest->lot->lot_number]) }} — {{ $interest->lot->translate('title') }}<br>
        @endif
        {{ __('auctions.interest.alert_hint') }}
    </p>
</x-mail.layout>
