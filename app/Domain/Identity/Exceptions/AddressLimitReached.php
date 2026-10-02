<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class AddressLimitReached extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('account.addresses.limit');
    }

    /** Translation key describing the problem to the customer. */
    public function translationKey(): string
    {
        return $this->getMessage();
    }
}
