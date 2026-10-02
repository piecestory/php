<?php

declare(strict_types=1);

namespace App\Domain\Payments\Exceptions;

use RuntimeException;

final class PaymentException extends RuntimeException
{
    public static function methodUnavailable(): self
    {
        return new self('payments.errors.method_unavailable');
    }

    public static function nothingToPay(): self
    {
        return new self('payments.errors.nothing_to_pay');
    }

    public static function providerFailed(): self
    {
        return new self('payments.errors.provider_failed');
    }

    public function translationKey(): string
    {
        return $this->getMessage();
    }
}
