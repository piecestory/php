<x-auth-card :title="__('auth.forgot.title')" :intro="__('auth.forgot.intro')">
    <form method="POST" action="{{ localized_route('password.email') }}" class="space-y-5">
        @csrf
        <x-form.input name="email" type="email" :label="__('auth.register.email')" autocomplete="email" dir="ltr" required autofocus />
        <x-ui.button type="submit" class="w-full">{{ __('auth.forgot.submit') }}</x-ui.button>
    </form>

    <x-slot:footer>
        <a href="{{ localized_route('login') }}" class="text-bronze hover:underline">{{ __('auth.forgot.back') }}</a>
    </x-slot:footer>
</x-auth-card>
