<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Actions\ApplyPaymentToOrder;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Staff records what the customer paid at a showroom: the amount due next (deposit or balance),
 * applied exactly like an online payment (reservation secured, or order confirmed).
 */
final class RecordInStorePayment
{
    public const string PROVIDER = 'in_store';

    public function __construct(private readonly ApplyPaymentToOrder $apply) {}

    /** @throws PaymentException */
    public function handle(Order $order, int $staffId, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($order, $staffId, $note): Payment {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($locked->status, [OrderStatus::Pending, OrderStatus::Reserved], true) || bccomp($locked->balanceDue(), '0', 2) <= 0) {
                throw PaymentException::nothingToPay();
            }

            $payment = $locked->payments()->create([
                'provider' => self::PROVIDER,
                'method' => PaymentMethod::InStore,
                'purpose' => $locked->nextPaymentPurpose(),
                'amount' => $locked->nextPaymentAmount(),
                'currency' => $locked->currency,
                'status' => PaymentStatus::Captured,
                'paid_at' => now(),
                'metadata' => array_filter(['recorded_by' => $staffId, 'note' => $note]),
            ]);

            if (! $this->apply->handle($payment)) {
                throw PaymentException::nothingToPay();
            }

            return $payment;
        });
    }
}
