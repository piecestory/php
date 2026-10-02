<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Material;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Enums\PublicationStatus;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

it('maintains a normalized search index on save', function (): void {
    $product = Product::factory()->create(['name_ar' => 'ساعة فرنسية', 'name_en' => 'French Clock', 'sku' => 'PS-000123']);

    expect($product->fresh()->search_text)->toContain('ساعه فرنسيه')->toContain('french clock')->toContain('ps 000123');

    $product->update(['name_en' => 'Mantel Clock']);

    expect($product->fresh()->search_text)->toContain('mantel clock')->not->toContain('french');
});

it('uses the sale price only while the sale is active and lower than the price', function (): void {
    $product = Product::factory()->onSale('800.00')->make(['price' => '1000.00']);
    expect($product->effectivePrice())->toBe('800.00');

    $product->sale_starts_at = now()->addDay();
    expect($product->effectivePrice())->toBe('1000.00');

    $product->sale_starts_at = now()->subDays(2);
    $product->sale_ends_at = now()->subDay();
    expect($product->effectivePrice())->toBe('1000.00');

    $product->sale_ends_at = null;
    $product->sale_price = '1200.00';
    expect($product->isOnSale())->toBeFalse();
});

it('only exposes published products whose publication date has arrived', function (): void {
    $visible = Product::factory()->published()->create();
    Product::factory()->create(['status' => PublicationStatus::Draft]);
    Product::factory()->create(['status' => PublicationStatus::Published, 'published_at' => now()->addDay()]);

    expect(Product::query()->published()->pluck('id')->all())->toBe([$visible->id]);
});

it('rejects duplicate SKUs', function (): void {
    Product::factory()->create(['sku' => 'PS-DUP']);
    Product::factory()->create(['sku' => 'PS-DUP']);
})->throws(UniqueConstraintViolationException::class);

it('prevents permanently deleting a category that still has products', function (): void {
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    $category->forceDelete();
})->throws(QueryException::class);

it('links products to several materials', function (): void {
    $product = Product::factory()->create();
    $materials = collect(['bronze', 'marble'])->map(fn (string $slug) => Material::query()->create([
        'name_ar' => $slug, 'name_en' => $slug, 'slug' => $slug,
    ]));

    $product->materials()->attach($materials->pluck('id'));

    expect($product->materials()->pluck('slug')->sort()->values()->all())->toBe(['bronze', 'marble']);
});

it('reads translated attributes with an Arabic fallback', function (): void {
    $product = Product::factory()->make(['name_ar' => 'ساعة', 'name_en' => 'Clock', 'story_ar' => 'قصة', 'story_en' => null]);

    expect($product->translate('name', 'en'))->toBe('Clock')
        ->and($product->translate('name', 'ar'))->toBe('ساعة')
        ->and($product->translate('story', 'en'))->toBe('قصة')
        ->and($product->translate('name', 'fr'))->toBe('ساعة');
});
