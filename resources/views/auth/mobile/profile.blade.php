<x-auth-card :title="__('auth.otp.profile_title')" :intro="__('auth.otp.profile_intro')">
    <form method="POST" action="{{ localized_route('login.mobile.profile.store') }}" class="space-y-5">
        @csrf
        <x-form.input name="name" :label="__('auth.register.name')" autocomplete="name" required autofocus />
        <x-form.input name="email" type="email" :label="__('auth.otp.email_optional')" autocomplete="email" dir="ltr" />
        <x-ui.button type="submit" class="w-full">{{ __('auth.otp.finish') }}</x-ui.button>
    </form>
</x-auth-card>
