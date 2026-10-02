<?php

declare(strict_types=1);

namespace App\Support\Localization;

/**
 * Route binding and URL generation use the slug of the current language (slug_ar / slug_en),
 * so /store/نجف-وإضاءة and /en/store/chandeliers-lighting resolve to the same record.
 */
trait HasLocalizedSlug
{
    public function getRouteKeyName(): string
    {
        return 'slug_'.Locales::resolve();
    }
}
