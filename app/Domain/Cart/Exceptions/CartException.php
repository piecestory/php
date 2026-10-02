<?php

declare(strict_types=1);

namespace App\Domain\Cart\Exceptions;

use RuntimeException;

/** A cart change that cannot be made; the message is a translation key shown to the customer. */
final class CartException extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self('cart.errors.unavailable');
    }

    public static function quantityExceeded(): self
    {
        return new self('cart.errors.quantity_exceeded');
    }

    public function translationKey(): string
    {
        return $this->getMessage();
    }
}
