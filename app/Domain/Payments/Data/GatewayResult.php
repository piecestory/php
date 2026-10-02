<?php

declare(strict_types=1);

namespace App\Domain\Payments\Data;

use App\Domain\Payments\Enums\PaymentStatus;

/** A provider's statement about one payment: what happened and for how much. */
final readonly class GatewayResult
{
    public function __construct(
        public PaymentStatus $status,
        public string $amount,
        public string $currency = 'SAR',
        public ?string $failureReason = null,
    ) {}
}
