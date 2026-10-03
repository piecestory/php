<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Support\ContentSecurityPolicy;
use Closure;
use Illuminate\Http\Request;
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
            $headers->set('Content-Security-Policy', ContentSecurityPolicy::for(self::isStaffPanel($request)));
        }

        return $response;
    }

    public static function isStaffPanel(Request $request): bool
    {
        return $request->is('admin', 'admin/*') || str_starts_with($request->path(), 'livewire');
    }
}
