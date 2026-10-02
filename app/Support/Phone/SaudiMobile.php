<?php

declare(strict_types=1);

namespace App\Support\Phone;

/**
 * Saudi mobile numbers in every common written form normalize to E.164 (+9665XXXXXXXX):
 * 05XXXXXXXX, 5XXXXXXXX, 9665XXXXXXXX, +9665XXXXXXXX, 009665XXXXXXXX, with spaces, dashes
 * or Arabic-Indic digits.
 */
final class SaudiMobile
{
    private const array ARABIC_DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', strtr($input, self::ARABIC_DIGITS));
        $digits = (string) preg_replace('/^(00966|966|0)/', '', $digits);

        return preg_match('/^5\d{8}$/', $digits) === 1 ? '+966'.$digits : null;
    }

    /** +9665XXXXXXXX → 05XXXXXXXX (how customers recognise their own number) */
    public static function local(string $e164): string
    {
        return '0'.substr($e164, 4);
    }
}
