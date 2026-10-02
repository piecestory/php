<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class InvalidLoginCode extends RuntimeException
{
    public static function wrong(): self
    {
        return new self('auth.otp.wrong');
    }

    public static function expired(): self
    {
        return new self('auth.otp.expired');
    }

    /** Translation key describing the problem to the customer. */
    public function translationKey(): string
    {
        return $this->getMessage();
    }
}
