<?php

declare(strict_types=1);

namespace App\Domain\Payments\Enums;

enum PaymentStatus: string
{
    case Initiated = 'initiated';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
