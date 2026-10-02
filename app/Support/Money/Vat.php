<?php

declare(strict_types=1);

namespace App\Support\Money;

/** Prices include VAT; these helpers extract or apply it on decimal strings (never floats). */
final class Vat
{
    /** The VAT contained in a VAT-inclusive amount: amount × rate / (100 + rate). */
    public static function included(string $amount): string
    {
        $rate = (string) config('store.vat_rate');

        return bcdiv(bcmul($amount, $rate, 4), bcadd('100', $rate, 2), 2);
    }

    /** percent% of an amount, rounded half up to halalas. */
    public static function percentOf(string $amount, string $percent): string
    {
        return bcadd(bcdiv(bcmul($amount, $percent, 4), '100', 4), '0.005', 2);
    }
}
