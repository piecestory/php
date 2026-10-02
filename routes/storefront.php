<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MobileLoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\FaqController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\TrackOrderController;
use Illuminate\Support\Facades\Route;

/*
| Storefront routes. Included once per locale by routes/web.php:
| Arabic at "/" with plain names, English at "/en" with names prefixed "en.".
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/faq', FaqController::class)->name('faq');

Route::controller(CatalogController::class)->group(function (): void {
    Route::get('/store', 'store')->name('store');
    Route::get('/store/{category}', 'category')->name('category');
    Route::get('/search', 'search')->name('search');
    Route::get('/collections', 'collections')->name('collections');
    Route::get('/collections/{collection}', 'collection')->name('collection');
});

Route::get('/product/{product}', ProductController::class)->name('product');

Route::get('/track-order', [TrackOrderController::class, 'show'])->name('track-order');
Route::post('/track-order', [TrackOrderController::class, 'lookup'])
    ->middleware('throttle:lookups')
    ->name('track-order.lookup');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:auth-forms')
        ->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:auth-forms')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:auth-forms')
        ->name('password.update');

    Route::prefix('login/mobile')->name('login.mobile')->group(function (): void {
        Route::get('/', [MobileLoginController::class, 'phoneForm'])->name('');
        Route::post('/', [MobileLoginController::class, 'sendCode'])->name('.send');
        Route::get('/code', [MobileLoginController::class, 'codeForm'])->name('.code');
        Route::post('/code', [MobileLoginController::class, 'verify'])
            ->middleware('throttle:lookups')
            ->name('.verify');
        Route::get('/profile', [MobileLoginController::class, 'profileForm'])->name('.profile');
        Route::post('/profile', [MobileLoginController::class, 'storeProfile'])->name('.profile.store');
    });
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
