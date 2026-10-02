<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Domain\Identity\Models\User;
use App\Rules\SaudiMobileNumber;
use App\Support\Localization\Locales;
use App\Support\Phone\SaudiMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * The customer's own details. Changing the email or mobile (the sign-in identifiers) requires the
 * current password when the account has one, so an unattended session cannot take the account over.
 */
class ProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $email = mb_strtolower(trim($this->string('email')->toString()));

        $this->merge([
            'email' => $email === '' ? null : $email,
            'phone' => SaudiMobile::normalize($this->string('phone')->toString()) ?? $this->input('phone'),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $user = $this->account();
        $identifiersChanged = $this->input('email') !== $user->email || $this->input('phone') !== $user->phone;

        return [
            'name' => ['required', 'string', 'max:100'],
            // Accounts with a password sign in with their email, so it cannot be removed.
            'email' => [Rule::requiredIf($user->password !== null), 'nullable', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', new SaudiMobileNumber, Rule::unique('users', 'phone')->ignore($user->id)],
            'locale' => ['required', Rule::in(Locales::SUPPORTED)],
            'current_password' => $identifiersChanged && $user->password !== null ? ['required', 'current_password'] : ['nullable'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return (array) trans('account.profile.fields');
    }

    /** @return array{name: string, email: ?string, phone: string, locale: string} */
    public function profile(): array
    {
        $email = $this->input('email');

        return [
            'name' => trim($this->string('name')->toString()),
            'email' => is_string($email) ? $email : null,
            'phone' => $this->string('phone')->toString(),
            'locale' => $this->string('locale')->toString(),
        ];
    }

    public function account(): User
    {
        $user = $this->user();

        return $user instanceof User ? $user : throw new LogicException('Profile requests require a signed-in customer.');
    }
}
