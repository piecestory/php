<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\IssueLoginCode;
use App\Domain\Identity\Actions\RegisterCustomer;
use App\Domain\Identity\Actions\VerifyLoginCode;
use App\Domain\Identity\Exceptions\InvalidLoginCode;
use App\Domain\Identity\Models\User;
use App\Domain\Settings\StoreSettings;
use App\Http\Controllers\Controller;
use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Passwordless sign-in with a code sent by SMS. Unavailable (404) until an SMS provider is
 * configured and enabled from the admin panel. New numbers complete a one-field profile.
 */
class MobileLoginController extends Controller
{
    private const string SESSION_PHONE = 'mobile_login.phone';

    private const string SESSION_VERIFIED = 'mobile_login.verified_phone';

    public function __construct(StoreSettings $settings)
    {
        abort_unless($settings->enabled('sms.enabled'), 404);
    }

    public function phoneForm(): View
    {
        return view('auth.mobile.phone');
    }

    public function sendCode(Request $request, IssueLoginCode $issue): RedirectResponse
    {
        $request->validate(['phone' => ['required', 'string', new SaudiMobileNumber]]);
        $phone = (string) SaudiMobile::normalize($request->string('phone')->toString());

        $this->throttle("otp-phone:{$phone}", 3, 600, 'phone');
        $this->throttle('otp-ip:'.$request->ip(), 10, 3600, 'phone');

        $issue->handle($phone, $request->ip());
        $request->session()->put(self::SESSION_PHONE, $phone);

        return redirect(localized_route('login.mobile.code'));
    }

    public function codeForm(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_PHONE);

        if (! is_string($phone)) {
            return redirect(localized_route('login.mobile'));
        }

        return view('auth.mobile.code', ['phone' => SaudiMobile::local($phone)]);
    }

    public function verify(Request $request, VerifyLoginCode $verify): RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_PHONE);
        if (! is_string($phone)) {
            return redirect(localized_route('login.mobile'));
        }

        $request->validate(['code' => ['required', 'digits:'.IssueLoginCode::LENGTH]]);

        try {
            $verify->handle($phone, $request->string('code')->toString());
        } catch (InvalidLoginCode $e) {
            throw ValidationException::withMessages(['code' => __($e->translationKey())]);
        }

        $request->session()->forget(self::SESSION_PHONE);
        $user = User::query()->where('phone', $phone)->first();

        if ($user === null) {
            $request->session()->put(self::SESSION_VERIFIED, $phone);

            return redirect(localized_route('login.mobile.profile'));
        }

        abort_unless($user->is_active, 403);
        if ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return $this->signIn($request, $user);
    }

    public function profileForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::SESSION_VERIFIED)) {
            return redirect(localized_route('login.mobile'));
        }

        return view('auth.mobile.profile');
    }

    public function storeProfile(Request $request, RegisterCustomer $register): RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_VERIFIED);
        if (! is_string($phone)) {
            return redirect(localized_route('login.mobile'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email:rfc', 'max:255', 'unique:users,email'],
        ]);

        $user = $register->handle([...$data, 'phone' => $phone], app()->getLocale(), phoneVerified: true);
        $request->session()->forget(self::SESSION_VERIFIED);

        return $this->signIn($request, $user);
    }

    private function signIn(Request $request, User $user): RedirectResponse
    {
        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(localized_route('home'));
    }

    /** @throws ValidationException */
    private function throttle(string $key, int $maxAttempts, int $decaySeconds, string $field): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                $field => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
