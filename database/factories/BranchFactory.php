<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Store\Enums\BranchType;
use App\Domain\Store\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name_ar' => 'فرع '.fake()->word(),
            'name_en' => ucfirst(fake()->word()).' Branch',
            'type' => BranchType::Showroom,
            'city' => 'جدة',
            'district' => fake()->word(),
            'is_pickup_point' => true,
            'is_active' => true,
        ];
    }
}
