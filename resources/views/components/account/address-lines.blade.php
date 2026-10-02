{{-- A saved address as the customer reads it. --}}
@props(['address'])

<address {{ $attributes->class(['not-italic leading-relaxed text-ink-soft']) }}>
    <span class="block font-medium text-ink">{{ $address->recipient_name }}</span>
    <span class="block">{{ collect([$address->street, $address->district, $address->city])->filter()->implode('، ') }}</span>
    <span class="numerals block" dir="ltr">
        {{ collect([$address->building_number, $address->postal_code, $address->additional_number])->filter()->implode(' · ') }}
        @if ($address->short_address) — {{ $address->short_address }} @endif
    </span>
    <span class="numerals block" dir="ltr">{{ \App\Support\Phone\SaudiMobile::local($address->phone) }}</span>
</address>
