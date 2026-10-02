<?php

declare(strict_types=1);

namespace App\Support\Localization;

use Illuminate\Support\Facades\Route;

/**
 * Storefront routes are registered once per locale: Arabic names are plain ("home"),
 * English names are prefixed ("en.home") and live under /en.
 */
final class LocalizedRoute
{
    private const string EN_PREFIX = 'en.';

    /** @param array<string, mixed>|string|int $parameters */
    public static function url(string $name, array|string|int $parameters = [], ?string $locale = null): string
    {
        return route(self::name($name, $locale), $parameters);
    }

    public static function name(string $name, ?string $locale = null): string
    {
        return Locales::resolve($locale) === Locales::PRIMARY ? $name : self::EN_PREFIX.$name;
    }

    /** Whether a storefront page exists yet; navigation only links to pages that are built. */
    public static function has(string $name): bool
    {
        return Route::has(self::name($name));
    }

    /** The current page in another language, or that language's home page if there is no counterpart. */
    public static function switchTo(string $locale): string
    {
        $route = request()->route();
        $current = $route?->getName();

        if ($current === null) {
            return self::url('home', locale: $locale);
        }

        $base = str_starts_with($current, self::EN_PREFIX) ? substr($current, strlen(self::EN_PREFIX)) : $current;
        $target = self::name($base, $locale);

        if (! Route::has($target)) {
            return self::url('home', locale: $locale);
        }

        $query = request()->query();

        return route($target, $route->parameters()).($query ? '?'.http_build_query($query) : '');
    }
}
