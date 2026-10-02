<?php

declare(strict_types=1);

namespace App\Domain\Payments\Enums;

enum PaymentPurpose: string
{
    case Full = 'full';
    case Deposit = 'deposit';
    case Balance = 'balance';
}
