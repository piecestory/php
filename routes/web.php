<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

Route::get('/', function () {
    return view('welcome');
});

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
