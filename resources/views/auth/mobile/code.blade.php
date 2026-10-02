@php($digits = \App\Domain\Identity\Actions\IssueLoginCode::LENGTH)

<x-auth-card :title="__('auth.otp.code_title')">
    <p class="mb-5 text-sm text-ink-soft">
        {{ __('auth.otp.code_intro', ['digits' => $digits]) }} <span class="numerals font-medium text-ink">{{ $phone }}</span>
    </p>
    <form method="POST" action="{{ localized_route('login.mobile.verify') }}" class="space-y-5">
        @csrf
        <x-form.input name="code" :label="__('auth.otp.code')" inputmode="numeric" autocomplete="one-time-code"
            :maxlength="$digits" pattern="[0-9]*" dir="ltr" class="text-center" required autofocus />
        <x-ui.button type="submit" class="w-full">{{ __('auth.otp.verify') }}</x-ui.button>
    </form>

    <x-slot:footer>
        <a href="{{ localized_route('login.mobile') }}" class="text-bronze hover:underline">{{ __('auth.otp.change_number') }}</a>
    </x-slot:footer>
</x-auth-card>
