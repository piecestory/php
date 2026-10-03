<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Support\Facades\Vite;

/**
 * The site's Content-Security-Policy, sent both as a response header (SecurityHeaders) and as a
 * <meta http-equiv> tag at the top of every page. Hostinger's LiteSpeed replaces the header with its
 * own "upgrade-insecure-requests"; the meta copy cannot be stripped by the server, and browsers enforce
 * both. Storefront: scripts from this site only (Alpine CSP build, no inline code, no eval). Staff panel:
 * Filament/Livewire need inline scripts and eval, still same-origin only.
 */
final class ContentSecurityPolicy
{
    /** @param  bool  $meta  frame-ancestors is ignored in a meta tag (X-Frame-Options covers framing) */
    public static function for(bool $staffPanel, bool $meta = false): string
    {
        // Vite's dev server (npm run dev) serves scripts and styles from its own origin.
        $dev = Vite::isRunningHot() ? ' '.self::devServerOrigins() : '';

        $directives = [
            "default-src 'self'",
            $staffPanel ? "script-src 'self' 'unsafe-inline' 'unsafe-eval'{$dev}" : "script-src 'self'{$dev}",
            "style-src 'self' 'unsafe-inline'{$dev}",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'{$dev}",
            "media-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            // Checkout redirects to the payment provider's page: browsers apply form-action to that redirect too.
            trim("form-action 'self' ".implode(' ', (array) config('payments.checkout_origins'))),
        ];

        if (! $meta) {
            $directives[] = "frame-ancestors 'none'";
        }
        if (app()->isProduction()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    private static function devServerOrigins(): string
    {
        $url = rtrim((string) file_get_contents(public_path('hot')));
        $ws = preg_replace('#^http#', 'ws', $url);

        return "{$url} {$ws}";
    }
}
