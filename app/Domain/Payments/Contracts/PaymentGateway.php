<?php

declare(strict_types=1);

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\Data\GatewayResult;
use App\Domain\Payments\Data\PaymentRedirect;
use App\Domain\Payments\Data\WebhookNotice;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Models\Payment;

/**
 * One payment provider (Moyasar, Tabby, Tamara, or the internal sandbox). The shop never trusts what
 * the customer's browser reports back: the outcome is always read from the provider (result) or
 * from a signed provider notification (webhook).
 */
interface PaymentGateway
{
    /** Stable code stored on payments and used in callback URLs. */
    public function code(): string;

    /** @return list<PaymentMethod> */
    public function methods(): array;

    /** Opens a payment with the provider; the customer is sent to the returned URL. */
    public function start(Payment $payment, string $returnUrl): PaymentRedirect;

    /** The provider's current view of this payment. */
    public function result(Payment $payment): GatewayResult;

    /**
     * Verifies and reads a provider notification. Returns null when the signature is invalid.
     *
     * @param  array<string, string>  $headers  lower-case header names
     */
    public function webhook(string $body, array $headers): ?WebhookNotice;

    /** Refunds part or all of a captured payment; returns the provider's refund reference. */
    public function refund(Payment $payment, string $amount): ?string;
}
