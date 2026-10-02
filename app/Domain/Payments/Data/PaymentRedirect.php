<?php

declare(strict_types=1);

namespace App\Domain\Payments\Data;

final readonly class PaymentRedirect
{
    public function __construct(
        public string $url,
        public string $reference,
    ) {}
}
