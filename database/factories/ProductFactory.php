<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $sku = 'PS-'.fake()->unique()->numerify('######');
        $nameEn = ucwords(fake()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'sku' => $sku,
            'name_ar' => 'قطعة '.$nameEn,
            'name_en' => $nameEn,
            'slug_ar' => 'قطعة-'.Str::slug($nameEn).'-'.Str::lower($sku),
            'slug_en' => Str::slug($nameEn).'-'.Str::lower($sku),
            'description_ar' => 'وصف تجريبي للقطعة.',
            'description_en' => fake()->sentence(12),
            'price' => fake()->numberBetween(5, 400) * 50,
            'stock_quantity' => 1,
            'availability' => ProductAvailability::Available,
            'condition' => fake()->randomElement(ProductCondition::cases()),
            'status' => PublicationStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PublicationStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    public function onSale(string $salePrice): static
    {
        return $this->state(fn () => ['sale_price' => $salePrice]);
    }

    public function rare(): static
    {
        return $this->state(fn () => ['is_rare' => true]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
