<?php

declare(strict_types=1);

namespace App\Domain\Orders\Enums;

/** Messages sent to the customer about their order (email when given, SMS always). */
enum OrderNotice: string
{
    /** Paid in full: the order is confirmed. */
    case Confirmed = 'confirmed';
    /** The pieces are reserved (with or without a deposit). */
    case Reserved = 'reserved';
    /** The reservation period is over: pay before the final date. */
    case ReservationReminder = 'reservation_reminder';
    /** Not paid in time: the reservation was cancelled (any deposit is refunded). */
    case ReservationCancelled = 'reservation_cancelled';

    /** The store team is told about new confirmed orders and reservations. */
    public function alertsStore(): bool
    {
        return in_array($this, [self::Confirmed, self::Reserved], true);
    }
}
