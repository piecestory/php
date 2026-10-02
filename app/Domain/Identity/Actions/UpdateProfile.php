<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;

/**
 * Updates the customer's own details. A changed email or mobile is no longer verified:
 * it must be confirmed again (email link / SMS code) wherever verification is required.
 */
final class UpdateProfile
{
    /** @param  array{name: string, email: ?string, phone: string, locale: string}  $data  validated; phone in E.164 */
    public function handle(User $user, array $data): User
    {
        $email = $data['email'] !== null ? mb_strtolower($data['email']) : null;

        if ($email !== $user->email) {
            $user->email_verified_at = null;
        }
        if ($data['phone'] !== $user->phone) {
            $user->phone_verified_at = null;
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'],
            'locale' => $data['locale'],
        ])->save();

        return $user;
    }
}
