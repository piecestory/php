<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => '+9665'.fake()->unique()->numerify('########'),
            'phone_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'locale' => 'ar',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null, 'phone_verified_at' => null]);
    }

    /** Signed up with mobile OTP only: no email, no password. */
    public function phoneOnly(): static
    {
        return $this->state(fn () => ['email' => null, 'email_verified_at' => null, 'password' => null]);
    }
}
