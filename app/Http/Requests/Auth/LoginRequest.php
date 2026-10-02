<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Support\Phone\SaudiMobile;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Sign in with email or Saudi mobile number + password. */
class LoginRequest extends FormRequest
{
    private const int MAX_ATTEMPTS = 5;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /** @throws ValidationException */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [...$this->identifier(), 'password' => $this->string('password')->toString(), 'is_active' => true];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /** @return array{email: string}|array{phone: string} */
    private function identifier(): array
    {
        $login = trim($this->string('login')->toString());

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return ['email' => mb_strtolower($login)];
        }

        return ['phone' => SaudiMobile::normalize($login) ?? $login];
    }

    /** @throws ValidationException */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'login' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower(implode('|', $this->identifier())).'|'.$this->ip());
    }
}
