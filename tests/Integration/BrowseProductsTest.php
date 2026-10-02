<?php

declare(strict_types=1);

use App\Domain\Catalog\Data\CatalogFilters;
use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Enums\ProductSort;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Collection;
use App\Domain\Catalog\Models\Era;
use App\Domain\Catalog\Models\Material;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Queries\BrowseProducts;

function browse(CatalogFilters $filters): array
{
    return app(BrowseProducts::class)->query($filters)->pluck('name_ar')->all();
}

it('finds Arabic words regardless of the definite article, hamza or taa marbuta', function (string $query): void {
    Product::factory()->published()->create(['name_ar' => 'الساعة الفرنسية المذهبة']);
    Product::factory()->published()->create(['name_ar' => 'كرسي خشبي']);

    expect(browse(new CatalogFilters(search: $query)))->toBe(['الساعة الفرنسية المذهبة']);
})->with(['ساعة', 'الساعه', 'ساعة فرنسية', 'مذهب', 'الساع']);

it('finds pieces by English name and by SKU', function (): void {
    Product::factory()->published()->create(['name_ar' => 'نجفة', 'name_en' => 'Crystal chandelier', 'sku' => 'PS-778899']);
    Product::factory()->published()->create(['name_ar' => 'أخرى', 'name_en' => 'Other']);

    expect(browse(new CatalogFilters(search: 'crystal')))->toBe(['نجفة'])
        ->and(browse(new CatalogFilters(search: 'PS-778899')))->toBe(['نجفة']);
});

it('never shows drafts, scheduled or deleted pieces', function (): void {
    Product::factory()->published()->create(['name_ar' => 'منشورة']);
    Product::factory()->create(['name_ar' => 'مسودة']);
    Product::factory()->published()->create(['name_ar' => 'مجدولة', 'published_at' => now()->addDay()]);
    Product::factory()->published()->create(['name_ar' => 'محذوفة'])->delete();

    expect(browse(new CatalogFilters))->toBe(['منشورة']);
});

it('filters and sorts on the price the customer actually pays', function (): void {
    Product::factory()->published()->create(['name_ar' => 'أ', 'price' => 1000]);
    Product::factory()->published()->onSale('400.00')->create(['name_ar' => 'ب', 'price' => 2000]);
    Product::factory()->published()->create(['name_ar' => 'ج', 'price' => 3000, 'sale_price' => 500, 'sale_ends_at' => now()->subDay()]);

    expect(browse(new CatalogFilters(sort: ProductSort::PriceAsc)))->toBe(['ب', 'أ', 'ج'])
        ->and(browse(new CatalogFilters(priceMax: '1000.00', sort: ProductSort::PriceAsc)))->toBe(['ب', 'أ'])
        ->and(browse(new CatalogFilters(priceMin: '2500.00')))->toBe(['ج']);
});

it('combines attribute filters', function (): void {
    $era = Era::query()->create(['name_ar' => 'ق19', 'name_en' => '19th', 'slug' => '19th']);
    $bronze = Material::query()->create(['name_ar' => 'برونز', 'name_en' => 'Bronze', 'slug' => 'bronze']);

    $match = Product::factory()->published()->rare()->create(['name_ar' => 'مطابقة', 'era_id' => $era->id, 'condition' => ProductCondition::Excellent]);
    $match->materials()->attach($bronze);
    Product::factory()->published()->create(['name_ar' => 'حقبة أخرى', 'condition' => ProductCondition::Excellent]);
    Product::factory()->published()->create(['name_ar' => 'مباعة', 'era_id' => $era->id, 'availability' => ProductAvailability::Sold]);

    expect(browse(new CatalogFilters(eraIds: [$era->id], materialIds: [$bronze->id], conditions: [ProductCondition::Excellent], availableOnly: true, rareOnly: true)))
        ->toBe(['مطابقة']);
});

it('includes sub-categories in a category listing and filters by collection', function (): void {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    Product::factory()->published()->for($parent)->create(['name_ar' => 'في الأب']);
    Product::factory()->published()->for($child)->create(['name_ar' => 'في الابن']);
    $other = Product::factory()->published()->create(['name_ar' => 'خارجها']);

    $collection = Collection::query()->create(['name_ar' => 'م', 'name_en' => 'C', 'slug_ar' => 'م', 'slug_en' => 'c']);
    $collection->products()->attach($other);

    expect(browse(new CatalogFilters(categoryId: $parent->id)))->toEqualCanonicalizing(['في الأب', 'في الابن'])
        ->and(browse(new CatalogFilters(collectionId: $collection->id)))->toBe(['خارجها']);
});
