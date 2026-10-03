<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use LogicException;

/** The staff member using the panel (always signed in: the panel's auth middleware guarantees it). */
final class Staff
{
    public static function user(): User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : throw new LogicException('Admin actions require a signed-in staff member.');
    }

    public static function id(): int
    {
        return self::user()->id;
    }

    public static function can(Permission $permission): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can($permission->value);
    }
}
