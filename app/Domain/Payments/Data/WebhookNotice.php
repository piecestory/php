<?php

declare(strict_types=1);

namespace App\Domain\Payments\Data;

final readonly class WebhookNotice
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public string $paymentReference,
        public GatewayResult $result,
    ) {}
}
