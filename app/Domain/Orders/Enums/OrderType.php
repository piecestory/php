<?php

declare(strict_types=1);

namespace App\Domain\Orders\Enums;

enum OrderType: string
{
    /** Pay now in full. */
    case Purchase = 'purchase';
    /** Hold the pieces for a few days without paying; pay before the hold ends. */
    case Reservation = 'reservation';
    /** Same hold, secured by a refundable deposit paid now. */
    case DepositReservation = 'deposit_reservation';

    public function label(): string
    {
        return __("orders.type.{$this->value}");
    }

    public function isReservation(): bool
    {
        return $this !== self::Purchase;
    }
}
