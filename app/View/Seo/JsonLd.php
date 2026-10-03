<?php

declare(strict_types=1);

namespace App\View\Seo;

use App\Domain\Settings\StoreSettings;

/** Shared pieces of schema.org structured data, and safe encoding for <script type="application/ld+json">. */
final class JsonLd
{
    /** @param  array<mixed>  $data */
    public static function encode(array $data): string
    {
        // HEX_TAG/HEX_AMP: text typed by staff can never close the script element.
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /**
     * @param  list<array{name: string, item: string}>  $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumbList(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $crumb, int $i) => ['@type' => 'ListItem', 'position' => $i + 1, ...$crumb],
                $crumbs,
                array_keys($crumbs),
            ),
        ];
    }

    /** @return list<array<string, mixed>> home page: the store, and the site with its search box (sitelinks search) */
    public static function home(): array
    {
        return [
            ['@context' => 'https://schema.org', ...self::organization()],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => __('ui.brand'),
                'url' => localized_route('home'),
                'inLanguage' => app()->getLocale(),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => localized_route('search').'?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> the store as publisher/seller */
    public static function organization(): array
    {
        return array_filter([
            '@type' => 'Organization',
            'name' => __('ui.brand'),
            'url' => localized_route('home'),
            'logo' => asset('apple-touch-icon.png'),
            'email' => app(StoreSettings::class)->get('store.email'),
        ], fn (mixed $value) => $value !== null);
    }
}
