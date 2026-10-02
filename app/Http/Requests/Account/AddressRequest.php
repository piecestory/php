<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\Support\NationalAddress;
use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            ...NationalAddress::normalize($this->all()),
            'phone' => SaudiMobile::normalize($this->string('phone')->toString()) ?? $this->input('phone'),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', new SaudiMobileNumber],
            ...NationalAddress::rules(),
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return (array) trans('checkout.attributes.address') + (array) trans('account.addresses.fields');
    }

    /**
     * @return array{label: ?string, recipient_name: string, phone: string, city: string, district: string, street: string, building_number: string, postal_code: string, additional_number: ?string, short_address: ?string, notes: ?string}
     */
    public function address(): array
    {
        $optional = fn (string $key): ?string => ($value = trim($this->string($key)->toString())) === '' ? null : $value;

        return [
            'label' => $optional('label'),
            'recipient_name' => trim($this->string('recipient_name')->toString()),
            'phone' => $this->string('phone')->toString(),
            'city' => trim($this->string('city')->toString()),
            'district' => trim($this->string('district')->toString()),
            'street' => trim($this->string('street')->toString()),
            'building_number' => $this->string('building_number')->toString(),
            'postal_code' => $this->string('postal_code')->toString(),
            'additional_number' => $optional('additional_number'),
            'short_address' => $optional('short_address'),
            'notes' => $optional('notes'),
        ];
    }
}
