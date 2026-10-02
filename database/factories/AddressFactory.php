<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Identity\Models\Address;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipient_name' => fake()->name(),
            'phone' => '+9665'.fake()->numerify('########'),
            'city' => fake()->randomElement(['جدة', 'الرياض', 'مكة المكرمة', 'الدمام']),
            'district' => fake()->randomElement(['الروضة', 'النهضة', 'الشاطئ', 'العليا']),
            'street' => fake()->streetName(),
            'building_number' => fake()->numerify('####'),
            'postal_code' => fake()->numerify('2####'),
            'is_default' => false,
        ];
    }
}
