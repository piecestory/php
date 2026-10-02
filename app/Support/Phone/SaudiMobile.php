<?php

declare(strict_types=1);

namespace App\Support\Phone;

use App\Support\Text\Digits;

/**
 * Saudi mobile numbers in every common written form normalize to E.164 (+9665XXXXXXXX):
 * 05XXXXXXXX, 5XXXXXXXX, 9665XXXXXXXX, +9665XXXXXXXX, 009665XXXXXXXX, with spaces, dashes
 * or Arabic-Indic digits.
 */
final class SaudiMobile
{
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', Digits::toLatin($input));
        $digits = (string) preg_replace('/^(00966|966|0)/', '', $digits);

        return preg_match('/^5\d{8}$/', $digits) === 1 ? '+966'.$digits : null;
    }

    /** +9665XXXXXXXX → 05XXXXXXXX (how customers recognise their own number) */
    public static function local(string $e164): string
    {
        return '0'.substr($e164, 4);
    }
}
