<?php

declare(strict_types=1);

namespace App\Domain\Orders\Enums;

enum OrderPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
}
