<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Data\GatewayResult;
use App\Domain\Payments\Data\PaymentRedirect;
use App\Domain\Payments\Data\WebhookNotice;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Stands in for a real provider during development and on staging: its "hosted payment page" is
 * an internal page where the tester chooses the outcome. No money moves and no card data is asked for.
 * Registered only outside production (see AppServiceProvider).
 */
final class SandboxGateway implements PaymentGateway
{
    public const string CODE = 'sandbox';

    private const int STATE_TTL_SECONDS = 86400;

    public function code(): string
    {
        return self::CODE;
    }

    public function methods(): array
    {
        return PaymentMethod::OFFERED;
    }

    public function start(Payment $payment, string $returnUrl): PaymentRedirect
    {
        $reference = 'sbx_'.Str::random(24);

        Cache::put($this->key($reference), [
            'status' => PaymentStatus::Initiated->value,
            'amount' => (string) $payment->amount,
            'currency' => $payment->currency,
            'method' => $payment->method->value,
            'return_url' => $returnUrl,
        ], self::STATE_TTL_SECONDS);

        return new PaymentRedirect(route('payments.sandbox', ['reference' => $reference]), $reference);
    }

    public function result(Payment $payment): GatewayResult
    {
        $state = $this->state((string) $payment->provider_reference);

        return new GatewayResult(
            status: PaymentStatus::tryFrom($state['status'] ?? '') ?? PaymentStatus::Failed,
            amount: $state['amount'] ?? '0.00',
            currency: $state['currency'] ?? 'SAR',
            failureReason: ($state['status'] ?? null) === PaymentStatus::Failed->value ? 'declined_in_sandbox' : null,
        );
    }

    public function webhook(string $body, array $headers): ?WebhookNotice
    {
        if (! hash_equals($this->sign($body), $headers['x-sandbox-signature'] ?? '')) {
            return null;
        }

        /** @var array{id?: string, type?: string, reference?: string, status?: string, amount?: string, currency?: string}|null $event */
        $event = json_decode($body, true);
        if (! is_array($event) || ! isset($event['id'], $event['reference'], $event['status'], $event['amount'])) {
            return null;
        }

        return new WebhookNotice(
            eventId: $event['id'],
            eventType: $event['type'] ?? 'payment.updated',
            paymentReference: $event['reference'],
            result: new GatewayResult(
                status: PaymentStatus::tryFrom($event['status']) ?? PaymentStatus::Failed,
                amount: $event['amount'],
                currency: $event['currency'] ?? 'SAR',
            ),
        );
    }

    public function refund(Payment $payment, string $amount): string
    {
        return 'sbx_rf_'.Str::random(20);
    }

    /**
     * What the sandbox payment page shows.
     *
     * @return array{status?: string, amount?: string, currency?: string, method?: string, return_url?: string}
     */
    public function state(string $reference): array
    {
        $state = Cache::get($this->key($reference));

        return is_array($state) ? $state : [];
    }

    /** The tester's choice on the sandbox page; returns where to send the customer next. */
    public function decide(string $reference, bool $approve): ?string
    {
        $state = $this->state($reference);
        if (($state['status'] ?? null) !== PaymentStatus::Initiated->value) {
            return $state['return_url'] ?? null;
        }

        $state['status'] = ($approve ? PaymentStatus::Captured : PaymentStatus::Failed)->value;
        Cache::put($this->key($reference), $state, self::STATE_TTL_SECONDS);

        return $state['return_url'] ?? null;
    }

    /** Signature a provider would put on its notifications; tests use it to send webhooks. */
    public function sign(string $body): string
    {
        $secret = (string) config('payments.sandbox.secret');

        return hash_hmac('sha256', $body, $secret !== '' ? $secret : hash('sha256', 'sandbox-webhooks|'.config('app.key')));
    }

    private function key(string $reference): string
    {
        return 'payments:sandbox:'.$reference;
    }
}
