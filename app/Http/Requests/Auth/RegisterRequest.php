<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Email + password sign-up. The mobile number is required because orders are delivered to it;
 * email is required too while SMS is not available, as it is the only way to recover the account.
 */
class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim($this->string('email')->toString())),
            'phone' => SaudiMobile::normalize($this->string('phone')->toString()) ?? $this->input('phone'),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', new SaudiMobileNumber, Rule::unique('users', 'phone')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ];
    }
}
