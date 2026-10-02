<?php

declare(strict_types=1);

namespace App\Support\Localization;

final class Locales
{
    public const string PRIMARY = 'ar';

    public const array SUPPORTED = ['ar', 'en'];

    public static function resolve(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return in_array($locale, self::SUPPORTED, true) ? $locale : self::PRIMARY;
    }

    public static function isRtl(string $locale): bool
    {
        return $locale === 'ar';
    }
}
