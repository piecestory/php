<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Data\PaymentRedirect;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentPurpose;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentGateways;
use Closure;
use Throwable;

/** Opens a payment for what the order owes next (deposit, balance or full amount) with the chosen method. */
final class StartPayment
{
    public function __construct(private readonly PaymentGateways $gateways) {}

    /**
     * @param  Closure(Payment): string  $returnUrl  where the provider sends the customer back
     *
     * @throws PaymentException
     */
    public function handle(Order $order, PaymentMethod $method, Closure $returnUrl): PaymentRedirect
    {
        if (! $order->awaitsPayment()) {
            throw PaymentException::nothingToPay();
        }

        $purpose = $order->nextPaymentPurpose();

        $gateway = $this->gateways->for($method);
        if ($gateway === null || ($purpose === PaymentPurpose::Deposit && ! $method->canPayDeposit())) {
            throw PaymentException::methodUnavailable();
        }

        $payment = $order->payments()->create([
            'provider' => $gateway->code(),
            'method' => $method,
            'purpose' => $purpose,
            'amount' => $order->nextPaymentAmount(),
            'currency' => $order->currency,
            'status' => PaymentStatus::Initiated,
        ]);

        try {
            $redirect = $gateway->start($payment, $returnUrl($payment));
        } catch (Throwable $e) {
            report($e);
            $payment->forceFill(['status' => PaymentStatus::Failed, 'failure_reason' => 'provider_unreachable'])->save();

            throw PaymentException::providerFailed();
        }

        $payment->forceFill(['provider_reference' => $redirect->reference])->save();
        $order->forceFill(['payment_method' => $method])->save();

        return $redirect;
    }
}
