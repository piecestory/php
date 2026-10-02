<?php

declare(strict_types=1);

use App\Support\Localization\Locales;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

// Storefront: Arabic at the root, English under /en (route names prefixed "en.").
foreach (Locales::SUPPORTED as $locale) {
    $group = Route::middleware("locale:{$locale}");

    if ($locale !== Locales::PRIMARY) {
        $group->prefix($locale)->name("{$locale}.");
    }

    $group->group(base_path('routes/storefront.php'));
}

// Design-system reference for visual QA. Registered only in the local environment.
if (app()->isLocal()) {
    Route::get('/_design', function () {
        $english = request()->query('lang') === 'en';
        app()->setLocale($english ? 'en' : 'ar');

        // Sample validation error, shared the same way Laravel shares real errors with every view.
        view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag([
            'email' => [$english ? 'Please enter a valid email address.' : 'يرجى إدخال بريد إلكتروني صحيح.'],
        ])));

        return view('dev.design-system');
    });
}
