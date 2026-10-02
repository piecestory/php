<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Collection;
use App\Domain\Catalog\Models\Product;
use App\Support\Localization\LocalizedRoute;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\DB;

it('lists published pieces in the store with a result count', function (): void {
    Product::factory()->published()->count(3)->create();
    Product::factory()->create(['name_ar' => 'مسودة مخفية']);

    $this->get('/store')->assertOk()->assertSee('3 قطع')->assertDontSee('مسودة مخفية');
});

it('serves category pages on the slug of each language and links them to each other', function (): void {
    $category = Category::factory()->create(['name_ar' => 'نجف وإضاءة', 'slug_ar' => 'نجف-وإضاءة', 'slug_en' => 'lighting']);
    Product::factory()->published()->for($category)->create(['name_en' => 'Crystal chandelier']);

    $this->get('/store/'.rawurlencode('نجف-وإضاءة'))->assertOk()->assertSee('نجف وإضاءة')
        ->assertSee('hreflang="en" href="'.url('/en/store/lighting').'"', escape: false);
    $this->get('/en/store/lighting')->assertOk()->assertSee('Crystal chandelier');
});

it('returns 404 for inactive categories and for a slug in the wrong language', function (): void {
    Category::factory()->create(['slug_ar' => 'مخفي', 'slug_en' => 'hidden', 'is_active' => false]);
    Category::factory()->create(['slug_ar' => 'تحف', 'slug_en' => 'antiques']);

    $this->get('/store/'.rawurlencode('مخفي'))->assertNotFound();
    $this->get('/en/store/'.rawurlencode('تحف'))->assertNotFound();
});

it('keeps search results out of search engines', function (): void {
    $this->get('/search?q=ساعة')->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', escape: false);
    $this->get('/store')->assertDontSee('noindex');
});

it('ignores invalid filter values instead of failing', function (): void {
    Product::factory()->published()->create();

    $this->get('/store?sort=hack&min=abc&era[]=x&condition[]=broken&rare=maybe')->assertOk()->assertSee('قطعة واحدة');
});

it('paginates and keeps the filters in page links', function (): void {
    Product::factory()->published()->count(30)->create();

    $this->get('/store?available=1')->assertOk()->assertSee('page=2', escape: false)->assertSee('available=1&amp;page=2', escape: false);
    $this->get('/store?available=1&page=2')->assertOk()->assertSee('30 قطعة');
});

it('lists active collections and shows their pieces', function (): void {
    $collection = Collection::query()->create(['name_ar' => 'صالون', 'name_en' => 'Salon', 'slug_ar' => 'صالون', 'slug_en' => 'salon', 'is_active' => true]);
    Collection::query()->create(['name_ar' => 'مخفية', 'name_en' => 'Hidden', 'slug_ar' => 'مخفية', 'slug_en' => 'hidden', 'is_active' => false]);
    $collection->products()->attach(Product::factory()->published()->create(['name_ar' => 'كرسي الصالون']));

    $this->get('/collections')->assertOk()->assertSee('صالون')->assertDontSee('مخفية');
    $this->get('/en/collections/salon')->assertOk()->assertSee('Salon');
    $this->get('/collections/'.rawurlencode('صالون'))->assertOk()->assertSee('كرسي الصالون');
});

it('renders a listing with a constant number of queries', function (int $count): void {
    Product::factory()->published()->count($count)->create();

    DB::enableQueryLog();
    $this->get('/store')->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(14);
})->with([2, 24]);

it('prefixes internal admin links with the visitor language', function (): void {
    app()->setLocale('en');
    expect(LocalizedRoute::path('/store'))->toBe(url('/en/store'))
        ->and(LocalizedRoute::path('/'))->toBe(url('/en'))
        ->and(LocalizedRoute::path('https://example.com/x'))->toBe('https://example.com/x');

    app()->setLocale('ar');
    expect(LocalizedRoute::path('/store'))->toBe(url('/store'));
});

it('seeds clearly marked sample pieces and removes only them', function (): void {
    $real = Product::factory()->published()->create(['sku' => 'PS-REAL-1']);

    $this->seed(DemoCatalogSeeder::class);
    expect(Product::query()->where('sku', 'like', 'DEMO-%')->count())->toBe(24)
        ->and(Product::query()->where('sku', 'like', 'DEMO-%')->first()->description_ar)->toContain('تجريبية');

    $this->artisan('catalog:remove-demo', ['--force' => true])->assertSuccessful();

    expect(Product::withTrashed()->where('sku', 'like', 'DEMO-%')->count())->toBe(0)
        ->and($real->fresh())->not->toBeNull()
        ->and(Collection::query()->count())->toBe(0);
});

it('refuses to seed sample pieces in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    (new DemoCatalogSeeder)->run();
})->throws(RuntimeException::class);
