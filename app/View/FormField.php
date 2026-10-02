<?php

declare(strict_types=1);

namespace App\View;

/** Naming rules shared by form controls and their field wrapper. */
final class FormField
{
    /** "address[city]" → "field-address-city" */
    public static function id(string $name): string
    {
        return 'field-'.trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-');
    }

    /** "address[city]" → "address.city" (the key used by the validator's error bag) */
    public static function errorKey(string $name): string
    {
        return str_replace(['[', ']'], ['.', ''], $name);
    }
}
