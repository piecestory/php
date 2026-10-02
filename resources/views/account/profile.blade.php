@php
    use App\Support\Phone\SaudiMobile;
    use Illuminate\Support\Str;

    $hasPassword = $user->password !== null;
    $card = 'space-y-5 rounded-xs border border-line bg-paper p-6 sm:p-8';
@endphp

<x-account.layout :title="__('account.nav.profile')">
    <div class="grid max-w-5xl gap-8 xl:grid-cols-2">
        <section aria-labelledby="profile-heading">
            <form method="POST" action="{{ localized_route('account.profile.update') }}" novalidate class="{{ $card }}">
                @csrf
                <h2 id="profile-heading" class="font-sans text-base font-semibold">{{ __('account.profile.title') }}</h2>
                <x-form.input name="name" :label="Str::ucfirst(__('account.profile.fields.name'))" :value="$user->name" autocomplete="name" required />
                <x-form.input name="email" type="email" :label="Str::ucfirst(__('account.profile.fields.email'))" :value="$user->email" dir="ltr" autocomplete="email" :required="$hasPassword" />
                <x-form.input name="phone" type="tel" :label="Str::ucfirst(__('account.profile.fields.phone'))" :value="$user->phone ? SaudiMobile::local($user->phone) : null"
                    inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" autocomplete="tel" required />
                <x-form.select name="locale" :label="Str::ucfirst(__('account.profile.fields.locale'))" :options="__('account.profile.locales')" :value="$user->locale" required />
                @if ($hasPassword)
                    <x-form.input name="current_password" type="password" :label="Str::ucfirst(__('account.profile.fields.current_password'))"
                        :hint="__('account.profile.identity_note')" autocomplete="current-password" />
                @endif
                <x-ui.button type="submit">{{ __('account.profile.save') }}</x-ui.button>
            </form>
        </section>

        <section aria-labelledby="password-heading">
            <form method="POST" action="{{ localized_route('account.password.update') }}" novalidate class="{{ $card }}">
                @csrf
                <h2 id="password-heading" class="font-sans text-base font-semibold">{{ __($hasPassword ? 'account.password.title' : 'account.password.set_title') }}</h2>
                @unless ($hasPassword)
                    <p class="text-sm text-ink-soft">{{ __('account.password.set_intro') }}</p>
                @endunless
                @if ($hasPassword)
                    {{-- Own field name: the profile form beside it has a current-password field too (ids and errors must not clash). --}}
                    <x-form.input name="password_current" type="password" :label="Str::ucfirst(__('account.password.fields.password_current'))" autocomplete="current-password" required />
                @endif
                <x-form.input name="password" type="password" :label="Str::ucfirst(__('account.password.fields.password'))" autocomplete="new-password" required />
                <x-form.input name="password_confirmation" type="password" :label="Str::ucfirst(__('account.password.fields.password_confirmation'))" autocomplete="new-password" required />
                <x-ui.button type="submit" variant="outline">{{ __('account.password.save') }}</x-ui.button>
            </form>
        </section>
    </div>
</x-account.layout>
