<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class StaffChangeRefused extends RuntimeException
{
    public static function ownAccess(): self
    {
        return new self('admin.staff.errors.own_access');
    }

    public static function lastAdmin(): self
    {
        return new self('admin.staff.errors.last_admin');
    }

    /** Translation key describing the problem. */
    public function translationKey(): string
    {
        return $this->getMessage();
    }
}
