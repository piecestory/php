<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $nameEn = ucwords(fake()->unique()->words(2, true));

        return [
            'name_ar' => 'تصنيف '.$nameEn,
            'name_en' => $nameEn,
            'slug_ar' => 'تصنيف-'.Str::slug($nameEn),
            'slug_en' => Str::slug($nameEn),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
