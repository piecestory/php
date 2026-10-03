<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Domain\Identity\Models\User;
use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use Illuminate\Foundation\Http\FormRequest;

/** "Notify me / I want to take part": contact details and an optional lot. No account needed. */
class AuctionInterestForm extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', new SaudiMobileNumber],
            'email' => ['nullable', 'string', 'email:rfc', 'max:190'],
            'lot' => ['nullable', 'integer'],
            'website' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return (array) trans('auctions.fields');
    }

    /** @return array{name: string, phone: string, email: ?string, user_id: ?int} */
    public function contact(): array
    {
        $email = trim($this->string('email')->toString());
        $user = $this->user();

        return [
            'name' => trim($this->string('name')->toString()),
            'phone' => (string) SaudiMobile::normalize($this->string('phone')->toString()),
            'email' => $email !== '' ? mb_strtolower($email) : null,
            'user_id' => $user instanceof User ? $user->id : null,
        ];
    }
}
