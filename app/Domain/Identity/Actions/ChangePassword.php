<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sets a new password (also for customers who signed up with an SMS code and never had one).
 * "Remember me" tokens and other open sessions are revoked, so a leaked password stops working everywhere.
 */
final class ChangePassword
{
    public function handle(User $user, string $password, ?string $keepSessionId = null): void
    {
        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->when($keepSessionId !== null, fn ($query) => $query->where('id', '!=', $keepSessionId))
                ->delete();
        }
    }
}
