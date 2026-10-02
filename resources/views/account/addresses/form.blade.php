@php
    $editing = $address->exists;
    $title = __($editing ? 'account.addresses.edit' : 'account.addresses.new');
    $action = $editing
        ? localized_route('account.addresses.update', ['address' => $address->id])
        : localized_route('account.addresses.store');
@endphp

<x-account.layout :$title>
    <form method="POST" action="{{ $action }}" novalidate class="max-w-2xl space-y-6 rounded-xs border border-line bg-paper p-6 sm:p-8">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="label" :label="__('account.addresses.fields.label')" :value="$address->label" :placeholder="__('account.addresses.label_placeholder')" maxlength="50" class="sm:col-span-2" />
            <x-form.input name="recipient_name" :label="__('account.addresses.fields.recipient_name')" :value="$address->recipient_name" autocomplete="name" required />
            <x-form.input name="phone" type="tel" :label="__('account.addresses.fields.phone')"
                :value="$address->phone ? \App\Support\Phone\SaudiMobile::local($address->phone) : null"
                inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" autocomplete="tel" required />
        </div>

        <fieldset class="space-y-5">
            <legend class="font-sans text-sm font-semibold">{{ __('checkout.address_title') }}</legend>
            <p class="text-xs text-ink-soft">{{ __('checkout.address_hint') }}</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="city" :label="__('checkout.attributes.address.city')" :value="$address->city" autocomplete="address-level2" required />
                <x-form.input name="district" :label="__('checkout.attributes.address.district')" :value="$address->district" autocomplete="address-level3" required />
                <x-form.input name="street" :label="__('checkout.attributes.address.street')" :value="$address->street" autocomplete="address-line1" required class="sm:col-span-2" />
                <x-form.input name="building_number" :label="__('checkout.attributes.address.building_number')" :value="$address->building_number" :hint="__('checkout.hints.building_number')" inputmode="numeric" dir="ltr" maxlength="4" required />
                <x-form.input name="postal_code" :label="__('checkout.attributes.address.postal_code')" :value="$address->postal_code" :hint="__('checkout.hints.postal_code')" inputmode="numeric" dir="ltr" maxlength="5" autocomplete="postal-code" required />
                <x-form.input name="additional_number" :label="__('checkout.attributes.address.additional_number')" :value="$address->additional_number" inputmode="numeric" dir="ltr" maxlength="4" />
                <x-form.input name="short_address" :label="__('checkout.attributes.address.short_address')" :value="$address->short_address" :hint="__('checkout.hints.short_address')" dir="ltr" maxlength="8" />
            </div>
        </fieldset>

        <x-form.textarea name="notes" :label="__('account.addresses.fields.notes')" :value="$address->notes" rows="2" maxlength="500" />

        @unless ($address->is_default)
            <x-form.checkbox name="is_default" :label="__('account.addresses.fields.is_default')" />
        @endunless

        <div class="flex flex-wrap items-center gap-4">
            <x-ui.button type="submit">{{ __('account.addresses.save') }}</x-ui.button>
            <a href="{{ localized_route('account.addresses') }}" class="text-sm text-ink-soft hover:text-ink">{{ __('account.addresses.cancel') }}</a>
        </div>
    </form>
</x-account.layout>
