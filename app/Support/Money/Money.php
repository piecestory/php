<?php

declare(strict_types=1);

namespace App\Support\Money;

/** Formats SAR amounts for display. Amounts are decimal strings and never pass through floats. */
final class Money
{
    /** "1850.00" → "1,850"; "1850.5" → "1,850.50"; "-20" → "-20" */
    public static function amount(string $amount): string
    {
        $normalized = bcadd($amount, '0', 2);
        $negative = str_starts_with($normalized, '-');
        [$integer, $fraction] = explode('.', ltrim($normalized, '-'));

        $grouped = strrev(implode(',', str_split(strrev($integer), 3)));

        return ($negative ? '-' : '').$grouped.($fraction === '00' ? '' : '.'.$fraction);
    }

    public static function currency(?string $locale = null): string
    {
        return __('ui.currency', locale: $locale);
    }
}
