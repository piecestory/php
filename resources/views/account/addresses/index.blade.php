<x-account.layout :title="__('account.nav.addresses')">
    @if ($addresses->isEmpty())
        <x-ui.empty-state icon="map-pin" :title="__('account.addresses.empty')" :text="__('account.addresses.empty_text')" class="rounded-xs border border-line bg-paper">
            <x-ui.button :href="localized_route('account.addresses.create')" icon="plus">{{ __('account.addresses.add') }}</x-ui.button>
        </x-ui.empty-state>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            @if ($canAdd)
                <x-ui.button :href="localized_route('account.addresses.create')" icon="plus" size="sm">{{ __('account.addresses.add') }}</x-ui.button>
            @else
                <p class="text-sm text-ink-soft">{{ __('account.addresses.limit_reached') }}</p>
            @endif
        </div>

        <ul class="grid gap-4 md:grid-cols-2">
            @foreach ($addresses as $address)
                <li @class(['flex flex-col rounded-xs border bg-paper p-5 text-sm', 'border-bronze/50' => $address->is_default, 'border-line' => ! $address->is_default])>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="font-sans font-semibold">{{ $address->label ?? __('account.addresses.new') }}</span>
                        @if ($address->is_default)
                            <x-ui.badge variant="reserved">{{ __('account.addresses.default') }}</x-ui.badge>
                        @endif
                    </div>
                    <x-account.address-lines :$address />
                    @if ($address->notes)
                        <p class="mt-2 text-xs text-ink-soft">{{ $address->notes }}</p>
                    @endif

                    <div class="mt-auto flex flex-wrap items-center gap-4 border-t border-line pt-4 mt-4">
                        <a href="{{ localized_route('account.addresses.edit', ['address' => $address->id]) }}" class="text-bronze hover:underline">{{ __('account.addresses.edit') }}</a>
                        @unless ($address->is_default)
                            <form method="POST" action="{{ localized_route('account.addresses.default', ['address' => $address->id]) }}">
                                @csrf
                                <button type="submit" class="text-ink-soft hover:text-ink">{{ __('account.addresses.make_default') }}</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ localized_route('account.addresses.destroy', ['address' => $address->id]) }}" class="ms-auto">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1 text-ink-soft hover:text-danger"
                                aria-label="{{ __('account.addresses.delete') }}: {{ $address->label ?? $address->recipient_name }}">
                                <x-ui.icon name="trash-2" class="size-4" />{{ __('account.addresses.delete') }}
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-account.layout>
