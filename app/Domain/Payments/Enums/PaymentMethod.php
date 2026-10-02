<?php

declare(strict_types=1);

namespace App\Domain\Payments\Enums;

enum PaymentMethod: string
{
    case Mada = 'mada';
    case CreditCard = 'credit_card';
    case ApplePay = 'apple_pay';
    case Tabby = 'tabby';
    case Tamara = 'tamara';

    /** The methods the store offers, in display order (credit cards are not offered). */
    public const array OFFERED = [self::Mada, self::ApplePay, self::Tabby, self::Tamara];

    public function label(): string
    {
        return __("payments.method.{$this->value}");
    }

    /** Buy-now-pay-later plans cannot pay a deposit: it is paid by card. */
    public function canPayDeposit(): bool
    {
        return ! in_array($this, [self::Tabby, self::Tamara], true);
    }
}
