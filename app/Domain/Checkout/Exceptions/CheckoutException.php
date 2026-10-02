<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Exceptions;

use RuntimeException;

final class CheckoutException extends RuntimeException
{
    /** @param  array<string, string>  $replace */
    private function __construct(string $key, public readonly array $replace = [])
    {
        parent::__construct($key);
    }

    public static function emptyCart(): self
    {
        return new self('checkout.errors.empty_cart');
    }

    public static function unavailable(string $name): self
    {
        return new self('checkout.errors.unavailable', ['name' => $name]);
    }

    public static function tooManyReservations(int $limit): self
    {
        return new self('checkout.errors.too_many_reservations', ['limit' => (string) $limit]);
    }

    public function translationKey(): string
    {
        return $this->getMessage();
    }
}
