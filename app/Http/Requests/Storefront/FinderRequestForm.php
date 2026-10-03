<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Http\Requests\Support\PhotoUploads;
use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use App\Support\Text\Digits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Personal Finder: describe the piece wanted, an optional budget and up to 6 reference photos. No account needed. */
class FinderRequestForm extends FormRequest
{
    public const int MAX_PHOTOS = 6;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'budget_min' => $this->filled('budget_min') ? Digits::toLatin((string) $this->input('budget_min')) : null,
            'budget_max' => $this->filled('budget_max') ? Digits::toLatin((string) $this->input('budget_max')) : null,
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', new SaudiMobileNumber],
            'email' => ['nullable', 'string', 'email:rfc', 'max:190'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'budget_min' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:99999999', 'gte:budget_min'],
            'preferences' => ['nullable', 'string', 'max:1000'],
            ...PhotoUploads::rules('photos', 0, self::MAX_PHOTOS),
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
            'category_id' => $this->filled('category_id') ? $this->integer('category_id') : null,
            'description' => trim($this->string('description')->toString()),
            'budget_min' => $optional('budget_min'),
            'budget_max' => $optional('budget_max'),
            'preferences' => $optional('preferences'),
            'locale' => app()->getLocale(),
        ];
    }
}
