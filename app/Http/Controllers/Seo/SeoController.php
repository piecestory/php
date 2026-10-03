<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Support\Localization\Locales;
use App\Support\Seo\Sitemap;
use Illuminate\Http\Response;

/** robots.txt and sitemap.xml, generated so they always carry this site's own address. */
class SeoController extends Controller
{
    /** Private or per-visitor pages: nothing for search engines there. */
    private const array PRIVATE_PATHS = [
        '/cart', '/checkout', '/wishlist', '/account', '/orders/', '/payments/', '/login', '/register',
        '/forgot-password', '/reset-password', '/search',
    ];

    public function robots(): Response
    {
        // Anything but production (staging, local) must stay out of search results entirely.
        if (! app()->isProduction()) {
            return $this->text("User-agent: *\nDisallow: /\n");
        }

        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /livewire'];
        foreach (Locales::SUPPORTED as $locale) {
            $prefix = $locale === Locales::PRIMARY ? '' : '/'.$locale;
            foreach (self::PRIVATE_PATHS as $path) {
                $lines[] = 'Disallow: '.$prefix.$path;
            }
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return $this->text(implode("\n", $lines)."\n");
    }

    public function sitemap(): Response
    {
        return response(Sitemap::xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function text(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
