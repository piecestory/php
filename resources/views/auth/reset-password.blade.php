<x-auth-card :title="__('auth.reset.title')">
    <form method="POST" action="{{ localized_route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" :label="__('auth.register.email')" :value="$email" autocomplete="email" dir="ltr" required />
        <x-form.input name="password" type="password" :label="__('auth.register.password')" autocomplete="new-password" required autofocus />
        <x-form.input name="password_confirmation" type="password" :label="__('auth.register.password_confirmation')" autocomplete="new-password" required />
        <x-ui.button type="submit" class="w-full">{{ __('auth.reset.submit') }}</x-ui.button>
    </form>
</x-auth-card>
