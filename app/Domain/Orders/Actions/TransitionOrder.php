<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use LogicException;

/** The only way an order changes status: the move must be allowed, and it is recorded in the history. */
final class TransitionOrder
{
    public function handle(Order $order, OrderStatus $to, ?int $userId = null, ?string $note = null): void
    {
        $from = $order->status;

        if (! $from->canTransitionTo($to)) {
            throw new LogicException("Order {$order->number} cannot move from {$from->value} to {$to->value}.");
        }

        $order->forceFill(['status' => $to])->save();
        $order->recordStatusChange($from, $to, $userId, $note);
    }
}
