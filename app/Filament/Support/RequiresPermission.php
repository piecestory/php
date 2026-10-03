<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Resource access by role permission: a section is open to staff whose role grants its permission;
 * actions a section never offers (e.g. creating orders by hand) are refused for everyone.
 */
trait RequiresPermission
{
    abstract protected static function permission(): Permission;

    /** @return list<string> abilities refused in this section, e.g. ['create', 'delete'] */
    protected static function forbiddenAbilities(): array
    {
        return [];
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof UnitEnum ? (string) ($action->value ?? $action->name) : $action;
        $user = auth()->user();

        $allowed = $user instanceof User
            && $user->can(static::permission()->value)
            && ! in_array($ability, static::forbiddenAbilities(), true);

        return $allowed ? Response::allow() : Response::deny();
    }
}
