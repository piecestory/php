@props([
    'amount',
    'compareAt' => null,
    'size' => 'md',
])

@php
    use App\Support\Money\Money;

    $currency = Money::currency();
    $onSale = $compareAt !== null && bccomp((string) $compareAt, (string) $amount, 2) > 0;
@endphp

<p {{ $attributes->class(['flex flex-wrap items-baseline gap-x-2', $size === 'lg' ? 'text-2xl' : 'text-[0.9375rem]']) }}>
    <span class="sr-only">{{ __('ui.price') }}:</span>
    <span @class(['font-semibold', 'text-bronze-deep' => $onSale, 'text-ink' => ! $onSale])>
        <span class="numerals">{{ Money::amount((string) $amount) }}</span>
        <span class="text-[0.8em] font-medium">{{ $currency }}</span>
    </span>
    @if ($onSale)
        <del class="text-[0.85em] text-ink-soft">
            <span class="sr-only">{{ __('ui.price_before') }}:</span>
            <span class="numerals">{{ Money::amount((string) $compareAt) }}</span>
        </del>
    @endif
</p>
