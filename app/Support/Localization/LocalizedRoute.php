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

    /**
     * URL of a storefront route. Models passed as parameters contribute the slug of the
     * requested language, so the URL is generated with that language active.
     */
    public static function url(string $name, mixed $parameters = [], ?string $locale = null): string
    {
        $locale = Locales::resolve($locale);
        $routeName = self::name($name, $locale);
        $current = app()->getLocale();

        if ($current === $locale) {
            return route($routeName, $parameters);
        }

        app()->setLocale($locale);
        try {
            return route($routeName, $parameters);
        } finally {
            app()->setLocale($current);
        }
    }

    public static function name(string $name, ?string $locale = null): string
    {
        return Locales::resolve($locale) === Locales::PRIMARY ? $name : self::EN_PREFIX.$name;
    }

    /**
     * Admin-entered links: an internal path ("/store") gets the current language prefix,
     * full URLs to other sites are returned unchanged.
     */
    public static function path(string $url): string
    {
        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return $url;
        }

        return url(Locales::resolve() === Locales::PRIMARY ? $url : '/en'.rtrim($url, '/'));
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

        if (! Route::has(self::name($base, $locale))) {
            return self::url('home', locale: $locale);
        }

        $query = request()->query();

        return self::url($base, $route->parameters(), $locale).($query ? '?'.http_build_query($query) : '');
    }
}
