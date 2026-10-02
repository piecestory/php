<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Localization\Locales;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The locale comes from the URL (Arabic at /, English under /en), never from session state. */
class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale = Locales::PRIMARY): Response
    {
        $locale = Locales::resolve($locale);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
