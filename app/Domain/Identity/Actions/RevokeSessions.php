<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Signs a user out everywhere (optionally except the current session) and voids "remember me" cookies. */
final class RevokeSessions
{
    public function handle(User $user, ?string $exceptSessionId = null): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->when($exceptSessionId !== null, fn ($query) => $query->where('id', '!=', $exceptSessionId))
                ->delete();
        }
    }
}
