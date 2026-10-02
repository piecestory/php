<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Inventory\Actions\StockLedger;
use App\Domain\Orders\Enums\OrderNotice;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Actions\RefundPayment;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\RefundStatus;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cancels an order: its pieces go back on sale and any money taken (e.g. a reservation deposit)
 * is refunded in full. Customers with a running reservation are told it was cancelled.
 */
final class CancelOrder
{
    public function __construct(
        private readonly TransitionOrder $transition,
        private readonly StockLedger $stock,
        private readonly RefundPayment $refund,
        private readonly NotifyCustomer $notify,
    ) {}

    /** @return bool false when the order was already closed */
    public function handle(Order $order, string $reason, ?int $userId = null): bool
    {
        /** @var array{0: Order, 1: bool, 2: Collection<int, Payment>}|null $cancelled */
        $cancelled = DB::transaction(function () use ($order, $reason, $userId): ?array {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $locked->status->canTransitionTo(OrderStatus::Cancelled)) {
                return null;
            }

            $wasReserved = $locked->status === OrderStatus::Reserved;

            if (in_array($locked->status, [OrderStatus::Pending, OrderStatus::Reserved], true)) {
                $this->stock->releaseHolds($locked, $userId);
            } else {
                $this->stock->returnSoldItems($locked, $userId);
            }

            $locked->forceFill(['hold_expires_at' => null])->save();
            $this->transition->handle($locked, OrderStatus::Cancelled, $userId, $reason);

            return [$locked, $wasReserved, $locked->payments()->where('status', PaymentStatus::Captured)->get()];
        });

        if ($cancelled === null) {
            return false;
        }

        [$order, $wasReserved, $captured] = $cancelled;

        if ($captured->isNotEmpty()) {
            $refunds = $captured->map(fn (Payment $payment) => $this->refund->handle($payment, (string) $payment->amount, $reason, $userId));

            if ($refunds->every(fn ($refund) => $refund->status === RefundStatus::Completed)) {
                $order->forceFill(['payment_status' => OrderPaymentStatus::Refunded])->save();
            }
        }

        if ($wasReserved) {
            $this->notify->handle($order, OrderNotice::ReservationCancelled);
        }

        return true;
    }
}
