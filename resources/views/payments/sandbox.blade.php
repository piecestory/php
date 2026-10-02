@php
    use App\Domain\Payments\Enums\PaymentMethod;
    use App\Support\Money\Money;

    $method = PaymentMethod::tryFrom($state['method'] ?? '');
@endphp

<x-layouts.store :title="__('payments.sandbox.title')" noindex>
    <div class="container-page py-16">
        <div class="mx-auto max-w-md rounded-xs border-2 border-dashed border-warning/50 bg-paper p-8 text-center">
            <x-ui.badge variant="reserved">SANDBOX</x-ui.badge>
            <h1 class="mt-4 text-display-md">{{ __('payments.sandbox.title') }}</h1>
            <p class="mt-2 text-sm text-ink-soft">{{ __('payments.sandbox.notice') }}</p>

            <dl class="mt-6 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-ink-soft">{{ __('payments.sandbox.amount') }}</dt><dd class="numerals font-semibold">{{ Money::amount($state['amount'] ?? '0') }} {{ Money::currency() }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">{{ __('payments.sandbox.method') }}</dt><dd>{{ $method?->label() }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('payments.sandbox.decide', ['reference' => $reference]) }}" class="mt-8 grid gap-3">
                @csrf
                <x-ui.button type="submit" name="outcome" value="approve" size="lg">{{ __('payments.sandbox.approve') }}</x-ui.button>
                <x-ui.button type="submit" name="outcome" value="decline" variant="outline">{{ __('payments.sandbox.decline') }}</x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.store>
