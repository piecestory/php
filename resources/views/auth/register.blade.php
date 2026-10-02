<x-auth-card :title="__('auth.register.title')" :intro="__('auth.register.intro')">
    <form method="POST" action="{{ localized_route('register.store') }}" class="space-y-5">
        @csrf
        <x-form.input name="name" :label="__('auth.register.name')" autocomplete="name" required autofocus />
        <x-form.input name="email" type="email" :label="__('auth.register.email')" autocomplete="email" dir="ltr" required />
        <x-form.input name="phone" type="tel" :label="__('auth.register.phone')" :hint="__('auth.register.phone_hint')"
            autocomplete="tel" inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" required />
        <x-form.input name="password" type="password" :label="__('auth.register.password')" autocomplete="new-password" required />
        <x-form.input name="password_confirmation" type="password" :label="__('auth.register.password_confirmation')" autocomplete="new-password" required />
        <x-form.checkbox name="terms" :label="__('auth.register.terms')" required />
        <x-ui.button type="submit" class="w-full">{{ __('auth.register.submit') }}</x-ui.button>
    </form>

    <x-slot:footer>
        {{ __('auth.register.have_account') }}
        <a href="{{ localized_route('login') }}" class="font-medium text-bronze hover:underline">{{ __('auth.login.submit') }}</a>
    </x-slot:footer>
</x-auth-card>
