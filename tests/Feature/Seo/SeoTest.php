<?php

declare(strict_types=1);

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Models\Auction;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Content\Models\Post;
use App\Support\Seo\Sitemap;
use Database\Seeders\SettingsSeeder;

beforeEach(function (): void {
    $this->seed(SettingsSeeder::class);
    Sitemap::flush();
});

/** @return list<array<string, mixed>> every JSON-LD block on the page */
function jsonLd(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

    return array_merge(...array_map(function (string $json): array {
        $data = json_decode($json, true);

        return array_is_list($data) ? $data : [$data];
    }, $m[1]));
}

it('keeps every non-production site out of search engines', function (): void {
    $this->get('/robots.txt')->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSeeText("User-agent: *\nDisallow: /", false);
});

it('lets search engines into the public pages in production, not private ones', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $robots = $this->get('/robots.txt')->assertOk()->getContent();

    expect($robots)->not->toContain("Disallow: /\n")
        ->toContain('Disallow: /checkout')
        ->toContain('Disallow: /en/account')
        ->toContain('Disallow: /admin')
        ->toContain('Sitemap: '.url('/sitemap.xml'));
});

it('lists published content in both languages with their translations', function (): void {
    $product = Product::factory()->published()->create(['slug_en' => 'brass-lamp']);
    $draft = Product::factory()->create(['slug_en' => 'secret-draft']);
    $post = Post::factory()->published()->create(['slug_en' => 'caring-for-brass']);
    Auction::factory()->create(['slug_en' => 'spring-sale']);
    Auction::factory()->create(['slug_en' => 'draft-sale', 'status' => AuctionStatus::Draft]);
    Category::factory()->create(['slug_en' => 'hidden-category', 'is_active' => false]);

    $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
    $doc = simplexml_load_string($xml);

    expect($doc)->not->toBeFalse()
        ->and($xml)->toContain('<loc>'.url('/en/product/brass-lamp').'</loc>')
        ->toContain('<loc>'.localized_route('product', $product).'</loc>')
        ->toContain('hreflang="en" href="'.url('/en/product/brass-lamp').'"')
        ->toContain(url('/en/blog/caring-for-brass'))
        ->toContain(url('/en/auctions/spring-sale'))
        ->toContain('<loc>'.url('/en/blog').'</loc>')
        ->not->toContain('secret-draft')
        ->not->toContain('draft-sale')
        ->not->toContain('hidden-category')
        ->not->toContain('/checkout')
        ->not->toContain('/cart');
    expect($post->isPublished())->toBeTrue();
});

it('omits the journal and auctions from the sitemap until they have something published', function (): void {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->not->toContain('<loc>'.url('/en/blog').'</loc>')
        ->not->toContain('<loc>'.url('/en/auctions').'</loc>')
        ->toContain('<loc>'.url('/en/store').'</loc>');
});

it('describes the store and its search on the home page', function (): void {
    $types = array_column(jsonLd($this->get('/')->assertOk()->getContent()), '@type');

    expect($types)->toContain('Organization')->toContain('WebSite');
});

it('publishes breadcrumbs as structured data on inner pages', function (): void {
    $category = Category::factory()->create(['is_active' => true]);

    $crumbs = collect(jsonLd($this->get(localized_route('category', $category))->assertOk()->getContent()))
        ->firstWhere('@type', 'BreadcrumbList');

    expect($crumbs['itemListElement'])->toHaveCount(3)
        ->and($crumbs['itemListElement'][2]['name'])->toBe($category->translate('name'))
        ->and($crumbs['itemListElement'][0]['item'])->toBe(localized_route('home'));
});

it('describes journal posts as articles published by the store', function (): void {
    $post = Post::factory()->published()->create(['title_ar' => 'العناية بالنحاس']);

    $article = collect(jsonLd($this->get(localized_route('blog.post', $post))->assertOk()->getContent()))->firstWhere('@type', 'Article');

    expect($article['headline'])->toBe('العناية بالنحاس')
        ->and($article['author']['@type'])->toBe('Organization')
        ->and($article['datePublished'])->not->toBeEmpty();
});

it('gives every page a share image, falling back to the brand card', function (): void {
    $this->get(localized_route('contact'))->assertOk()
        ->assertSee('<meta property="og:image" content="'.asset('images/og-default.jpg').'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

it('keeps sign-in pages out of search results', function (string $route): void {
    $this->get(localized_route($route))->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
})->with(['login', 'register', 'password.request']);

it('points filtered listings to the plain listing and keeps later pages their own address', function (): void {
    $this->get(localized_route('store').'?sort=price_asc')->assertOk()
        ->assertSee('<link rel="canonical" href="'.localized_route('store').'">', false);

    $this->get(localized_route('store').'?page=2&sort=price_asc')->assertOk()
        ->assertSee('<link rel="canonical" href="'.localized_route('store').'?page=2">', false)
        ->assertSee('hreflang="en" href="'.url('/en/store').'?page=2"', false);
});

it('draws the logo from the cached file instead of inlining it in every page', function (): void {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('images/brand/horizontal-ar.svg?v=')
        ->and(substr_count($html, '<mask'))->toBe(0);
});
