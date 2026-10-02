<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\PersonalFinder\Models\FinderRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinderRequest>
 */
class FinderRequestFactory extends Factory
{
    protected $model = FinderRequest::class;

    public function definition(): array
    {
        return [
            'reference' => 'PF-'.fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'phone' => '+9665'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'description' => 'أبحث عن قطعة محددة.',
            'budget_min' => 1000,
            'budget_max' => 5000,
            'locale' => 'ar',
        ];
    }
}
