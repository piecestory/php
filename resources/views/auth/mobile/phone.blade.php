<x-auth-card :title="__('auth.otp.title')" :intro="__('auth.otp.intro')">
    <form method="POST" action="{{ localized_route('login.mobile.send') }}" class="space-y-5">
        @csrf
        <x-form.input name="phone" type="tel" :label="__('auth.register.phone')" autocomplete="tel" inputmode="tel"
            dir="ltr" placeholder="05XXXXXXXX" required autofocus />
        <x-ui.button type="submit" class="w-full">{{ __('auth.otp.send') }}</x-ui.button>
    </form>

    <x-slot:footer>
        <a href="{{ localized_route('login') }}" class="text-bronze hover:underline">{{ __('auth.forgot.back') }}</a>
    </x-slot:footer>
</x-auth-card>
