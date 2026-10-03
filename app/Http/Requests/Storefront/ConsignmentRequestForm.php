<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Http\Requests\Support\PhotoUploads;
use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use App\Support\Text\Digits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** "Sell with us": the piece, the owner details and 1–8 photos; the owner confirms the piece is theirs. */
class ConsignmentRequestForm extends FormRequest
{
    public const int MAX_PHOTOS = 8;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'asking_price' => $this->filled('asking_price') ? Digits::toLatin((string) $this->input('asking_price')) : null,
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', new SaudiMobileNumber],
            'email' => ['nullable', 'string', 'email:rfc', 'max:190'],
            'city' => ['required', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'asking_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            ...PhotoUploads::rules('photos', 1, self::MAX_PHOTOS),
            'ownership' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return (array) trans('requests.fields');
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        $optional = fn (string $key): ?string => ($value = trim($this->string($key)->toString())) === '' ? null : $value;
        $email = $optional('email');

        return [
            'name' => trim($this->string('name')->toString()),
            'phone' => (string) SaudiMobile::normalize($this->string('phone')->toString()),
            'email' => $email !== null ? mb_strtolower($email) : null,
            'city' => trim($this->string('city')->toString()),
            'category_id' => $this->filled('category_id') ? $this->integer('category_id') : null,
            'title' => trim($this->string('title')->toString()),
            'description' => trim($this->string('description')->toString()),
            'asking_price' => $optional('asking_price'),
            'locale' => app()->getLocale(),
        ];
    }
}
