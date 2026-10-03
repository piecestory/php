<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['locale' => SetLocale::class]);
        // The language must be known before route models are resolved by their localized slug.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetLocale::class);

        // Provider notifications are authenticated by their signature, not a session token.
        $middleware->validateCsrfTokens(except: ['payments/*/webhook']);

        // Every response, including the staff panel (which has its own middleware stack) and error pages.
        $middleware->append(SecurityHeaders::class);

        $middleware->redirectGuestsTo(fn () => localized_route('login'));
        $middleware->redirectUsersTo(fn () => localized_route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
