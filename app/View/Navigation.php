<?php

declare(strict_types=1);

namespace App\View;

use App\Domain\Content\ContentAvailability;
use App\Domain\Content\Enums\PageKey;
use App\Support\Localization\LocalizedRoute;

/**
 * Site navigation in display order. Entries whose page is not built yet are left out,
 * so menus never link to a missing page and fill in as modules are delivered.
 */
final class Navigation
{
    private const array MAIN = ['home', 'store', 'collections', 'auctions', 'services', 'about', 'blog', 'contact'];

    private const array UTILITY = ['faq', 'track-order'];

    private const array HELP = ['faq', 'track-order', 'personal-finder', 'sell-with-us', 'contact', 'shipping-policy', 'returns-policy', 'privacy', 'terms'];

    /** @return list<array{label: string, url: string, active: bool}> */
    public static function utility(): array
    {
        return self::build(self::UTILITY);
    }

    /** @return list<array{label: string, url: string, active: bool}> */
    public static function main(): array
    {
        return self::build(self::MAIN);
    }

    /** @return list<array{label: string, url: string, active: bool}> */
    public static function help(): array
    {
        return self::build(self::HELP);
    }

    /** Content pages appear once published; the journal and auctions once something is published there. */
    public static function hasContent(string $route): bool
    {
        $content = app(ContentAvailability::class);

        return match (true) {
            PageKey::tryFrom($route) !== null => $content->hasPage($route),
            $route === 'blog' => $content->hasBlog(),
            $route === 'auctions' => $content->hasAuctions(),
            default => true,
        };
    }

    /**
     * @param  list<string>  $routes
     * @return list<array{label: string, url: string, active: bool}>
     */
    private static function build(array $routes): array
    {
        $current = request()->route()?->getName();
        $items = [];

        foreach ($routes as $route) {
            if (! LocalizedRoute::has($route) || ! self::hasContent($route)) {
                continue;
            }

            $name = LocalizedRoute::name($route);
            $items[] = [
                'label' => __('site.nav.'.str_replace('-', '_', $route)),
                'url' => route($name),
                // A section stays highlighted on its inner pages (an article, an auction).
                'active' => $current === $name || str_starts_with((string) $current, $name.'.'),
            ];
        }

        return $items;
    }
}
