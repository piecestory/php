<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Inventory\Actions\StockLedger;
use App\Domain\Orders\Enums\OrderNotice;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentPurpose;
use App\Domain\Payments\Models\Payment;

/**
 * What a captured payment does to its order:
 *  - deposit → the pieces are reserved for the reservation period (4 days + 3 reminder days);
 *  - full / balance → the order is confirmed and the held pieces become sold.
 * Runs inside the payment's transaction. Returns false when the order can no longer take this
 * payment (cancelled, expired or already paid); the caller then refunds it.
 */
final class ApplyPaymentToOrder
{
    public function __construct(
        private readonly TransitionOrder $transition,
        private readonly StockLedger $stock,
        private readonly NotifyCustomer $notify,
    ) {}

    public function handle(Payment $payment): bool
    {
        $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

        $open = in_array($order->status, [OrderStatus::Pending, OrderStatus::Reserved], true);
        $isDeposit = $payment->purpose === PaymentPurpose::Deposit;
        $expectsDeposit = $order->type === OrderType::DepositReservation && $order->status === OrderStatus::Pending;

        if (! $open || $isDeposit !== $expectsDeposit || bccomp((string) $payment->amount, $order->nextPaymentAmount(), 2) !== 0) {
            return false;
        }

        $order->forceFill([
            'amount_paid' => bcadd((string) $order->amount_paid, (string) $payment->amount, 2),
            'payment_method' => $payment->method,
        ]);

        if ($isDeposit) {
            $reservedUntil = now()->addDays((int) config('store.reservation.hold_days'));
            $holdUntil = $reservedUntil->addDays((int) config('store.reservation.reminder_days'));

            $order->forceFill([
                'payment_status' => OrderPaymentStatus::DepositPaid,
                'reserved_until' => $reservedUntil,
                'hold_expires_at' => $holdUntil,
            ])->save();
            $this->stock->extendHolds($order, $holdUntil);
            $this->transition->handle($order, OrderStatus::Reserved, note: 'deposit_paid');
            $this->notify->handle($order, OrderNotice::Reserved);

            return true;
        }

        $order->forceFill(['payment_status' => OrderPaymentStatus::Paid, 'hold_expires_at' => null])->save();
        $this->stock->convertHoldsToSale($order);
        $this->transition->handle($order, OrderStatus::Confirmed, note: 'paid');
        $this->notify->handle($order, OrderNotice::Confirmed);

        return true;
    }
}
