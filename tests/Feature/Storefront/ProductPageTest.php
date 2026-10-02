<?php

declare(strict_types=1);

use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Material;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Enums\PublicationStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

function productUrl(Product $product, string $locale = 'ar'): string
{
    return $locale === 'ar' ? '/product/'.rawurlencode($product->slug_ar) : '/en/product/'.$product->slug_en;
}

it('shows a published piece with its story, specifications and VAT note', function (): void {
    $product = Product::factory()->published()->create([
        'name_ar' => 'ساعة رف فرنسية', 'slug_ar' => 'ساعة-رف', 'story_ar' => 'جاءت هذه الساعة من باريس.',
        'condition' => ProductCondition::Restored, 'width_cm' => 40, 'height_cm' => 55.5, 'price' => 2950,
    ]);
    $product->materials()->attach(Material::query()->create(['name_ar' => 'برونز', 'name_en' => 'Bronze', 'slug' => 'bronze']));

    $this->get(productUrl($product))
        ->assertOk()
        ->assertSee('<h1 class="text-display-lg">ساعة رف فرنسية</h1>', escape: false)
        ->assertSee('جاءت هذه الساعة من باريس.')
        ->assertSee('برونز')
        ->assertSee('مرمّمة')
        ->assertSee('40 × 55.5 سم')
        ->assertSee('2,950')
        ->assertSee('السعر شامل ضريبة القيمة المضافة');
});

it('serves the English page on the English slug and links both languages', function (): void {
    $product = Product::factory()->published()->create(['slug_ar' => 'نجفة', 'slug_en' => 'chandelier', 'name_en' => 'Chandelier']);

    $this->get('/en/product/chandelier')->assertOk()->assertSee('Chandelier')
        ->assertSee('hreflang="ar" href="'.url('/product/'.rawurlencode('نجفة')).'"', escape: false);
    $this->get('/en/product/'.rawurlencode('نجفة'))->assertNotFound();
});

it('hides drafts, scheduled and deleted pieces', function (array $state): void {
    $product = Product::factory()->create($state);

    $this->get(productUrl($product))->assertNotFound();
})->with([
    'draft' => [['status' => PublicationStatus::Draft]],
    'scheduled' => [['status' => PublicationStatus::Published, 'published_at' => now()->addDay()]],
]);

it('returns 404 for a deleted piece', function (): void {
    $product = Product::factory()->published()->create();
    $product->delete();

    $this->get(productUrl($product))->assertNotFound();
});

it('describes the offer for search engines with the price actually charged', function (): void {
    $product = Product::factory()->published()->onSale('800.00')->create(['price' => 1000, 'sku' => 'PS-1']);

    $html = $this->get(productUrl($product))->assertOk()->getContent();
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    [$schema, $breadcrumbs] = json_decode($m[1], true);

    expect($schema)->toMatchArray(['@type' => 'Product', 'sku' => 'PS-1'])
        ->and($schema['offers'])->toMatchArray(['price' => '800.00', 'priceCurrency' => 'SAR', 'availability' => 'https://schema.org/InStock'])
        ->and($breadcrumbs['@type'])->toBe('BreadcrumbList');
});

it('marks sold pieces as sold out and hides the price for price-on-request pieces', function (): void {
    $sold = Product::factory()->published()->create(['availability' => ProductAvailability::Sold, 'stock_quantity' => 0]);
    $onRequest = Product::factory()->published()->create(['availability' => ProductAvailability::OnRequest, 'price' => 99999]);

    $this->get(productUrl($sold))->assertSee('مُباعة')->assertSee('https://schema.org/SoldOut', escape: false);
    $this->get(productUrl($onRequest))->assertSee('السعر عند الطلب')->assertDontSee('99,999');
});

it('escapes structured data so content cannot break out of the script tag', function (): void {
    $product = Product::factory()->published()->create(['name_ar' => '</script><script>alert(1)</script>']);

    $this->get(productUrl($product))->assertOk()->assertDontSee('</script><script>alert(1)', escape: false);
});

it('shows a gallery with every photo in order and a share image', function (): void {
    Queue::fake();
    $product = Product::factory()->published()->create();
    foreach (['a.jpg', 'b.jpg'] as $name) {
        $product->addMedia(UploadedFile::fake()->image($name, 800, 1000))->toMediaCollection(Product::MEDIA_GALLERY);
    }

    $this->get(productUrl($product))
        ->assertOk()
        ->assertSeeInOrder(['a.jpg', 'b.jpg'])
        ->assertSee('<meta property="og:image"', escape: false)
        ->assertSee('<meta property="og:type" content="product">', escape: false);
});

it('suggests other published pieces from the same category only', function (): void {
    $category = Category::factory()->create();
    $product = Product::factory()->published()->for($category)->create();
    Product::factory()->published()->for($category)->create(['name_ar' => 'قطعة مشابهة']);
    Product::factory()->for($category)->create(['name_ar' => 'مسودة مشابهة']);
    Product::factory()->published()->create(['name_ar' => 'تصنيف آخر']);

    $this->get(productUrl($product))->assertSee('قطعة مشابهة')->assertDontSee('مسودة مشابهة')->assertDontSee('تصنيف آخر');
});

it('links product cards in listings to the product page', function (): void {
    $product = Product::factory()->published()->create(['slug_en' => 'gilt-clock']);

    $this->get('/en/store')->assertSee(url('/en/product/gilt-clock'));
});
