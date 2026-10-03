<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Auctions\Models\Auction;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Post;
use Illuminate\Support\Facades\Cache;

/**
 * Which content exists for visitors right now (published pages, at least one published article or
 * auction), so menus never link to an empty or unpublished page. Cached; flushed whenever one changes.
 * Scheduled articles appear within the cache lifetime.
 */
final class ContentAvailability
{
    private const string KEY = 'content:availability';

    private const int TTL_SECONDS = 600;

    /** @var array{pages: list<string>, blog: bool, auctions: bool}|null */
    private ?array $state = null;

    public function hasPage(string $key): bool
    {
        return in_array($key, $this->state()['pages'], true);
    }

    public function hasBlog(): bool
    {
        return $this->state()['blog'];
    }

    public function hasAuctions(): bool
    {
        return $this->state()['auctions'];
    }

    public static function flush(): void
    {
        Cache::forget(self::KEY);
        app(self::class)->state = null;
    }

    /** @return array{pages: list<string>, blog: bool, auctions: bool} */
    private function state(): array
    {
        return $this->state ??= Cache::remember(self::KEY, self::TTL_SECONDS, fn (): array => [
            'pages' => array_values(Page::query()->where('is_published', true)->pluck('key')->map(fn ($key) => (string) $key)->all()),
            'blog' => Post::query()->published()->exists(),
            'auctions' => Auction::query()->visible()->exists(),
        ]);
    }
}
