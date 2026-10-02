<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;

/**
 * Cancels open orders whose hold has run out: unpaid checkouts after the payment window, and
 * reservations after the reservation + reminder period. Their pieces go back on sale.
 */
final class ExpireOrderHolds
{
    public const string REASON = 'hold_expired';

    public function __construct(private readonly CancelOrder $cancel) {}

    /** @return int how many orders were cancelled */
    public function handle(): int
    {
        $cancelled = 0;

        Order::query()
            ->whereIn('status', [OrderStatus::Pending, OrderStatus::Reserved])
            ->where('hold_expires_at', '<=', now())
            ->lazyById()
            ->each(function (Order $order) use (&$cancelled): void {
                if ($this->cancel->handle($order, self::REASON)) {
                    $cancelled++;
                }
            });

        return $cancelled;
    }
}
