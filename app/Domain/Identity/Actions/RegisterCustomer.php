<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use Illuminate\Auth\Events\Registered;

final class RegisterCustomer
{
    /**
     * @param  array{name: string, email?: ?string, phone?: ?string, password?: ?string}  $data  validated input; phone already normalized
     */
    public function handle(array $data, string $locale, bool $phoneVerified = false): User
    {
        $user = new User([
            'name' => $data['name'],
            'email' => isset($data['email']) ? mb_strtolower($data['email']) : null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'] ?? null,
            'locale' => $locale,
        ]);

        if ($phoneVerified) {
            $user->phone_verified_at = now();
        }

        $user->save();

        event(new Registered($user));

        return $user;
    }
}
