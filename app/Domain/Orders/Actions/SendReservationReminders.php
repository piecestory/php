<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderNotice;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;

/**
 * Once the reservation period is over, the customer is reminded once a day to pay, until the
 * final date (after which ExpireOrderHolds cancels the reservation).
 */
final class SendReservationReminders
{
    /** Runs daily; a little under 24h keeps the daily rhythm even if a run starts a few minutes late. */
    private const int MIN_HOURS_BETWEEN = 20;

    public function __construct(private readonly NotifyCustomer $notify) {}

    /** @return int how many reminders were sent */
    public function handle(): int
    {
        $sent = 0;

        Order::query()
            ->where('status', OrderStatus::Reserved)
            ->where('reserved_until', '<=', now())
            ->where('hold_expires_at', '>', now())
            ->where(fn ($query) => $query
                ->whereNull('reminded_at')
                ->orWhere('reminded_at', '<=', now()->subHours(self::MIN_HOURS_BETWEEN)))
            ->lazyById()
            ->each(function (Order $order) use (&$sent): void {
                $order->forceFill(['reminded_at' => now()])->save();
                $this->notify->handle($order, OrderNotice::ReservationReminder);
                $sent++;
            });

        return $sent;
    }
}
