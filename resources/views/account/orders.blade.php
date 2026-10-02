<x-account.layout :title="__('account.nav.orders')">
    @if ($orders->isEmpty())
        <x-ui.empty-state icon="package-check" :title="__('account.orders.empty')" :text="__('account.orders.empty_text')" class="rounded-xs border border-line bg-paper">
            <x-ui.button :href="localized_route('store')" icon="arrow-right">{{ __('account.orders.browse') }}</x-ui.button>
        </x-ui.empty-state>
    @else
        <ul class="divide-y divide-line rounded-xs border border-line bg-paper">
            @foreach ($orders as $order)
                <x-account.order-row :$order />
            @endforeach
        </ul>
        <div class="mt-8">{{ $orders->links('partials.pagination') }}</div>
    @endif
</x-account.layout>
