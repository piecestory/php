<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers on every web response.
 *
 * The storefront gets a strict Content-Security-Policy: scripts only from this site (Alpine's CSP build,
 * no inline scripts, no eval), so injected markup cannot run code. The staff panel (Filament/Livewire,
 * which evaluate inline expressions) gets a looser policy, still limited to this site's own origin.
 * Inline style attributes are allowed (fixed image ratios, logo masks) — they cannot execute code.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Do not advertise the PHP version.
        header_remove('X-Powered-By');
        $headers = $response->headers;
        $headers->remove('X-Powered-By');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (app()->isProduction() && $request->isSecure()) {
            // No includeSubDomains/preload: other subdomains (mail, staging) are not ours to commit.
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->policy($this->isStaffPanel($request)));
        }

        return $response;
    }

    private function isStaffPanel(Request $request): bool
    {
        return $request->is('admin', 'admin/*') || str_starts_with($request->path(), 'livewire');
    }

    private function policy(bool $staffPanel): string
    {
        // Vite's dev server (npm run dev) serves scripts and styles from its own origin.
        $dev = Vite::isRunningHot() ? ' '.$this->devServerOrigins() : '';

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
            "frame-ancestors 'none'",
        ];

        if (app()->isProduction()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    private function devServerOrigins(): string
    {
        $url = rtrim((string) file_get_contents(public_path('hot')));
        $ws = preg_replace('#^http#', 'ws', $url);

        return "{$url} {$ws}";
    }
}
