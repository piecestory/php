{{-- Confirmation after sending a Personal Finder / "sell with us" request. --}}
@props(['reference', 'kind'])

<div {{ $attributes->class(['rounded-xs border border-success/30 bg-success/5 p-8 text-center']) }} role="status">
    <x-ui.icon name="check" class="mx-auto size-10 text-success" />
    <h2 class="mt-4 text-display-md">{{ __("requests.{$kind}.submitted_title") }}</h2>
    <p class="mt-3 text-ink-soft">{{ __("requests.{$kind}.submitted_text") }}</p>
    <p class="mt-6 text-sm">{{ __('requests.reference') }}: <span class="numerals text-lg font-semibold" dir="ltr">{{ $reference }}</span></p>
    <p class="mt-2 text-xs text-ink-soft">{{ __('requests.reference_hint') }}</p>
    <x-ui.button :href="localized_route('store')" variant="outline" class="mt-8">{{ __('cart.browse') }}</x-ui.button>
</div>
