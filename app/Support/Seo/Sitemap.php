<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Domain\Auctions\Models\Auction;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Collection;
use App\Domain\Catalog\Models\Product;
use App\Domain\Content\ContentAvailability;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Post;
use App\Support\Localization\Locales;
use App\Support\Localization\LocalizedRoute;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * sitemap.xml: every public page in both languages, each entry listing its translation (hreflang),
 * so search engines index the Arabic and English pages as one piece of content. Private pages
 * (cart, checkout, account, orders) and filtered listings are never included.
 * Cached for an hour; new pieces appear within that time.
 */
final class Sitemap
{
    private const string KEY = 'seo:sitemap';

    private const int TTL_SECONDS = 3600;

    /** Public pages without a record behind them. Content-dependent ones are checked below. */
    private const array FIXED = ['home', 'store', 'collections', 'faq', 'contact', 'personal-finder', 'sell-with-us'];

    public static function xml(): string
    {
        return Cache::remember(self::KEY, self::TTL_SECONDS, fn (): string => view('seo.sitemap', [
            'entries' => (new self)->entries(),
            'locales' => Locales::SUPPORTED,
            'default' => Locales::PRIMARY,
        ])->render());
    }

    public static function flush(): void
    {
        Cache::forget(self::KEY);
    }

    /** @return list<array{urls: array<string, string>, lastmod: ?CarbonInterface}> */
    public function entries(): array
    {
        $content = app(ContentAvailability::class);
        $entries = [];

        $fixed = self::FIXED;
        if ($content->hasBlog()) {
            $fixed[] = 'blog';
        }
        if ($content->hasAuctions()) {
            $fixed[] = 'auctions';
        }
        foreach ($fixed as $route) {
            if (LocalizedRoute::has($route)) {
                $entries[] = $this->entry($route);
            }
        }

        foreach (Page::query()->where('is_published', true)->get(['key', 'updated_at']) as $page) {
            $entries[] = $this->entry((string) $page->key, lastmod: $page->updated_at);
        }

        $this->addRecords($entries, 'category', Category::query()->where('is_active', true));
        $this->addRecords($entries, 'collection', Collection::query()->where('is_active', true));
        $this->addRecords($entries, 'product', Product::query()->published());
        $this->addRecords($entries, 'blog.post', Post::query()->published());
        $this->addRecords($entries, 'auctions.show', Auction::query()->visible());

        return $entries;
    }

    /**
     * @param  list<array{urls: array<string, string>, lastmod: ?CarbonInterface}>  $entries
     * @param  \Illuminate\Database\Eloquent\Builder<covariant Model>  $query
     */
    private function addRecords(array &$entries, string $route, $query): void
    {
        $query->select(['id', 'slug_ar', 'slug_en', 'updated_at'])->orderBy('id')->chunk(500, function ($records) use (&$entries, $route): void {
            foreach ($records as $record) {
                $entries[] = $this->entry($route, $record, $record->getAttribute('updated_at'));
            }
        });
    }

    /** @return array{urls: array<string, string>, lastmod: ?CarbonInterface} */
    private function entry(string $route, ?Model $record = null, ?CarbonInterface $lastmod = null): array
    {
        $urls = [];
        foreach (Locales::SUPPORTED as $locale) {
            $urls[$locale] = LocalizedRoute::url($route, $record ? [$record] : [], $locale);
        }

        return ['urls' => $urls, 'lastmod' => $lastmod];
    }
}
