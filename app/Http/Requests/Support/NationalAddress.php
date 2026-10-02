<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Support\Text\Digits;

/**
 * Saudi National Address fields, shared by checkout and the account address book:
 * the same normalisation (Arabic digits, upper-case short code) and the same rules.
 */
final class NationalAddress
{
    private const array DIGIT_FIELDS = ['building_number', 'postal_code', 'additional_number'];

    /**
     * @param  array<mixed>  $address
     * @return array<mixed>
     */
    public static function normalize(array $address): array
    {
        foreach (self::DIGIT_FIELDS as $field) {
            if (is_string($address[$field] ?? null)) {
                $address[$field] = trim(Digits::toLatin($address[$field]));
            }
        }

        if (is_string($address['short_address'] ?? null)) {
            $address['short_address'] = mb_strtoupper(trim(Digits::toLatin($address['short_address'])));
        }

        return $address;
    }

    /**
     * @param  string  $prefix  e.g. "address." when the fields are nested in the form
     * @return array<string, list<string>>
     */
    public static function rules(string $prefix = ''): array
    {
        return [
            "{$prefix}city" => ['required', 'string', 'max:100'],
            "{$prefix}district" => ['required', 'string', 'max:100'],
            "{$prefix}street" => ['required', 'string', 'max:150'],
            "{$prefix}building_number" => ['required', 'digits:4'],
            "{$prefix}postal_code" => ['required', 'digits:5'],
            "{$prefix}additional_number" => ['nullable', 'digits:4'],
            "{$prefix}short_address" => ['nullable', 'regex:/^[A-Z]{4}\d{4}$/'],
        ];
    }
}
