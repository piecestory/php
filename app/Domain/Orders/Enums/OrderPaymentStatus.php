<?php

declare(strict_types=1);

namespace App\Domain\Orders\Enums;

enum OrderPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case DepositPaid = 'deposit_paid';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function label(): string
    {
        return __("orders.payment_status.{$this->value}");
    }
}
