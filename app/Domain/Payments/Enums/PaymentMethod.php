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
}
