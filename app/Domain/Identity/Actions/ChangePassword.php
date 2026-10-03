<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;

/**
 * Sets a new password (also for customers who signed up with an SMS code and never had one).
 * Other sessions and "remember me" tokens are revoked, so a leaked password stops working everywhere.
 */
final class ChangePassword
{
    public function __construct(private readonly RevokeSessions $revoke) {}

    public function handle(User $user, string $password, ?string $keepSessionId = null): void
    {
        $user->forceFill(['password' => $password])->save();

        $this->revoke->handle($user, $keepSessionId);
    }
}
