<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Actions\ApplyPaymentToOrder;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Data\GatewayResult;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Records what the provider says happened to a payment. Safe to call any number of times for the
 * same payment (customer return + webhook + retries): a settled payment is never applied twice.
 * Money that arrives for an order that can no longer take it (expired, already paid) is refunded.
 */
final class SettlePayment
{
    public function __construct(
        private readonly ApplyPaymentToOrder $apply,
        private readonly RefundPayment $refund,
    ) {}

    public function handle(Payment $payment, GatewayResult $result): Payment
    {
        $refundDue = DB::transaction(function () use ($payment, $result): bool {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($locked->status !== PaymentStatus::Initiated && $locked->status !== PaymentStatus::Authorized) {
                return false;
            }

            if ($result->status === PaymentStatus::Captured) {
                if (bccomp($result->amount, (string) $locked->amount, 2) !== 0 || $result->currency !== $locked->currency) {
                    Log::warning('Payment amount mismatch; not applied', ['payment_id' => $locked->id]);
                    $locked->forceFill(['status' => PaymentStatus::Failed, 'failure_reason' => 'amount_mismatch'])->save();

                    return false;
                }

                $locked->forceFill(['status' => PaymentStatus::Captured, 'paid_at' => now()])->save();

                return ! $this->apply->handle($locked);
            }

            if ($result->status === PaymentStatus::Failed || $result->status === PaymentStatus::Cancelled) {
                $locked->forceFill(['status' => $result->status, 'failure_reason' => $result->failureReason])->save();

                $order = $locked->order;
                if ($order->status === OrderStatus::Pending && $order->payment_status === OrderPaymentStatus::Unpaid) {
                    $order->forceFill(['payment_status' => OrderPaymentStatus::Failed])->save();
                }
            }

            return false;
        });

        $payment->refresh();

        if ($refundDue) {
            $this->refund->handle($payment, (string) $payment->amount, 'order_no_longer_payable');
        }

        return $payment;
    }
}
