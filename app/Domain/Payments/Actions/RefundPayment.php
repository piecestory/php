<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\RefundStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Refund;
use App\Domain\Payments\PaymentGateways;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Asks the provider to return money and records the outcome. A failed attempt is kept as a
 * "failed" refund for the team to follow up; it never blocks the cancellation that triggered it.
 */
final class RefundPayment
{
    public function __construct(private readonly PaymentGateways $gateways) {}

    public function handle(Payment $payment, string $amount, string $reason, ?int $userId = null): Refund
    {
        // Money taken at a showroom is returned there by staff: recorded as pending until they do.
        if ($payment->provider === RecordInStorePayment::PROVIDER) {
            return $payment->refunds()->create([
                'amount' => $amount,
                'reason' => $reason,
                'status' => RefundStatus::Pending,
                'processed_by' => $userId,
            ]);
        }

        $reference = null;

        try {
            $reference = $this->gateways->named($payment->provider)?->refund($payment, $amount);
        } catch (Throwable $e) {
            report($e);
        }

        $refund = $payment->refunds()->create([
            'amount' => $amount,
            'reason' => $reason,
            'status' => $reference !== null ? RefundStatus::Completed : RefundStatus::Failed,
            'provider_reference' => $reference,
            'processed_by' => $userId,
        ]);

        if ($reference === null) {
            Log::error('Payment refund failed; needs manual follow-up', ['payment_id' => $payment->id, 'refund_id' => $refund->id]);
        } elseif (bccomp($amount, (string) $payment->amount, 2) >= 0) {
            $payment->forceFill(['status' => PaymentStatus::Refunded])->save();
        }

        return $refund;
    }
}
