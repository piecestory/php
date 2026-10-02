<x-auth-card :title="__('auth.login.title')" :intro="__('auth.login.intro')">
    <form method="POST" action="{{ localized_route('login.store') }}" class="space-y-5">
        @csrf
        <x-form.input name="login" :label="__('auth.login.identifier')" autocomplete="username" required autofocus />
        <x-form.input name="password" type="password" :label="__('auth.login.password')" autocomplete="current-password" required />
        <div class="flex items-center justify-between gap-4">
            <x-form.checkbox name="remember" :label="__('auth.login.remember')" />
            <a href="{{ localized_route('password.request') }}" class="shrink-0 text-sm text-bronze hover:underline">{{ __('auth.login.forgot') }}</a>
        </div>
        <x-ui.button type="submit" class="w-full">{{ __('auth.login.submit') }}</x-ui.button>
    </form>

    @if ($mobileLoginEnabled)
        <div class="my-6 flex items-center gap-3 text-xs text-ink-soft">
            <span class="h-px flex-1 bg-line"></span>{{ __('auth.or') }}<span class="h-px flex-1 bg-line"></span>
        </div>
        <x-ui.button variant="outline" :href="localized_route('login.mobile')" icon="phone" class="w-full">{{ __('auth.login.with_mobile') }}</x-ui.button>
    @endif

    <x-slot:footer>
        {{ __('auth.login.no_account') }}
        <a href="{{ localized_route('register') }}" class="font-medium text-bronze hover:underline">{{ __('auth.login.create_account') }}</a>
    </x-slot:footer>
</x-auth-card>
