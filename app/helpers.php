<?php

declare(strict_types=1);

use App\Support\Localization\LocalizedRoute;

if (! function_exists('localized_route')) {
    /** URL of a storefront route in the current (or given) language; accepts models as parameters. */
    function localized_route(string $name, mixed $parameters = [], ?string $locale = null): string
    {
        return LocalizedRoute::url($name, $parameters, $locale);
    }
}
