<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use Illuminate\Foundation\Http\FormRequest;

/** Contact form. A way to reply (mobile or email) is required; the hidden "website" field traps bots. */
class ContactRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'required_without:email', 'string', new SaudiMobileNumber],
            'email' => ['nullable', 'required_without:phone', 'string', 'email:rfc', 'max:190'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'website' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return (array) trans('contact.fields');
    }

    /** @return array{name: string, phone: ?string, email: ?string, subject: ?string, message: string} */
    public function contactMessage(): array
    {
        $optional = fn (string $key): ?string => ($value = trim($this->string($key)->toString())) === '' ? null : $value;
        $email = $optional('email');

        return [
            'name' => trim($this->string('name')->toString()),
            'phone' => SaudiMobile::normalize($optional('phone')),
            'email' => $email !== null ? mb_strtolower($email) : null,
            'subject' => $optional('subject'),
            'message' => trim($this->string('message')->toString()),
        ];
    }
}
