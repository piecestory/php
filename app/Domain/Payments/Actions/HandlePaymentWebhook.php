<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\PaymentWebhookEvent;
use App\Domain\Payments\PaymentGateways;

/**
 * Provider notifications. Each event is handled once: repeats of an already processed event
 * (providers resend until they get a 200) are acknowledged without doing anything again.
 */
final class HandlePaymentWebhook
{
    public function __construct(
        private readonly PaymentGateways $gateways,
        private readonly SettlePayment $settle,
    ) {}

    /**
     * @param  array<string, string>  $headers  lower-case header names
     * @return bool false when the notification is not authentic (unknown provider or bad signature)
     */
    public function handle(string $provider, string $body, array $headers): bool
    {
        $notice = $this->gateways->named($provider)?->webhook($body, $headers);
        if ($notice === null) {
            return false;
        }

        $event = PaymentWebhookEvent::query()->firstOrCreate(
            ['provider' => $provider, 'event_id' => $notice->eventId],
            ['event_type' => $notice->eventType, 'payload_hash' => hash('sha256', $body)],
        );

        if ($event->processed_at !== null) {
            return true;
        }

        $payment = Payment::query()
            ->where('provider', $provider)
            ->where('provider_reference', $notice->paymentReference)
            ->first();

        if ($payment !== null) {
            $this->settle->handle($payment, $notice->result);
        }

        $event->forceFill(['processed_at' => now()])->save();

        return true;
    }
}
