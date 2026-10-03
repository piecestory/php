<x-account.layout :title="__('account.nav.requests')">
    <div class="mb-6 flex flex-wrap gap-3">
        <x-ui.button :href="localized_route('personal-finder')" size="sm" icon="plus">{{ __('account.requests.new_finder') }}</x-ui.button>
        <x-ui.button :href="localized_route('sell-with-us')" size="sm" variant="outline" icon="plus">{{ __('account.requests.new_consignment') }}</x-ui.button>
    </div>

    @if ($finderRequests->isEmpty() && $consignments->isEmpty())
        <x-ui.empty-state icon="search" :title="__('account.requests.empty')" :text="__('account.requests.empty_text')" class="rounded-xs border border-line bg-paper" />
    @endif

    @foreach ([['finder', $finderRequests], ['consignment', $consignments]] as [$kind, $items])
        @if ($items->isNotEmpty())
            <section class="mb-8" aria-labelledby="requests-{{ $kind }}">
                <h2 id="requests-{{ $kind }}" class="mb-3 font-sans text-sm font-semibold">{{ __("account.requests.{$kind}") }}</h2>
                <ul class="divide-y divide-line rounded-xs border border-line bg-paper">
                    @foreach ($items as $item)
                        <li class="flex flex-wrap items-center gap-x-6 gap-y-2 px-5 py-4 text-sm">
                            <span class="numerals font-medium" dir="ltr">{{ $item->reference }}</span>
                            <span class="min-w-0 flex-1 truncate text-ink-soft">{{ $kind === 'consignment' ? $item->title : \Illuminate\Support\Str::limit($item->description, 80) }}</span>
                            <span class="text-ink-soft">{{ $item->created_at?->translatedFormat('j F Y') }}</span>
                            <x-ui.badge :variant="in_array($item->status->value, ['new', 'reviewing', 'sourcing'], true) ? 'reserved' : 'neutral'">{{ $item->status->label() }}</x-ui.badge>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach
</x-account.layout>
