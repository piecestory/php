<?php

declare(strict_types=1);

use App\Domain\Content\Enums\PageKey;
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MobileLoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ConsignmentController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\FaqController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\PersonalFinderController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\TrackOrderController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

/*
| Storefront routes. Included once per locale by routes/web.php:
| Arabic at "/" with plain names, English at "/en" with names prefixed "en.".
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/faq', FaqController::class)->name('faq');

// Fixed content pages (about, services, policies), edited in the admin.
foreach (PageKey::cases() as $pageKey) {
    Route::get($pageKey->path(), PageController::class)->defaults('pageKey', $pageKey->value)->name($pageKey->value);
}

Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.post');

// Customer requests to the team (no account needed): find a piece for me / sell my piece.
Route::get('/personal-finder', [PersonalFinderController::class, 'show'])->name('personal-finder');
Route::post('/personal-finder', [PersonalFinderController::class, 'store'])->middleware('throttle:service-requests')->name('personal-finder.store');
Route::get('/sell-with-us', [ConsignmentController::class, 'show'])->name('sell-with-us');
Route::post('/sell-with-us', [ConsignmentController::class, 'store'])->middleware('throttle:service-requests')->name('sell-with-us.store');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

Route::controller(CatalogController::class)->group(function (): void {
    Route::get('/store', 'store')->name('store');
    Route::get('/store/{category}', 'category')->name('category');
    Route::get('/search', 'search')->name('search');
    Route::get('/collections', 'collections')->name('collections');
    Route::get('/collections/{collection}', 'collection')->name('collection');
});

Route::get('/product/{product}', ProductController::class)->name('product');

Route::get('/cart', [CartController::class, 'show'])->name('cart');
Route::get('/wishlist', [WishlistController::class, 'show'])->name('wishlist');

Route::middleware('throttle:shopping')->group(function (): void {
    Route::post('/cart/items/{product:id}', [CartController::class, 'add'])->name('cart.add');
    // Lines may point to pieces removed from the catalogue since; they must still be removable.
    Route::post('/cart/items/{product:id}/quantity', [CartController::class, 'update'])->withTrashed()->name('cart.update');
    Route::post('/cart/items/{product:id}/remove', [CartController::class, 'remove'])->withTrashed()->name('cart.remove');
    Route::post('/wishlist/{product:id}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
});

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');

// Order page: opened with the order's private key (or by its signed-in owner).
Route::get('/orders/{order:number}', [OrderController::class, 'show'])->name('order');
Route::post('/orders/{order:number}/pay', [OrderController::class, 'pay'])->middleware('throttle:checkout')->name('order.pay');
Route::get('/payments/{payment}/return', [PaymentController::class, 'return'])->name('payments.return');

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

// My account. Records are always read through the signed-in customer, so ids of others are 404s.
Route::middleware('auth')->prefix('account')->name('account')->group(function (): void {
    Route::get('/', [AccountController::class, 'overview'])->name('');
    Route::get('/orders', [AccountController::class, 'orders'])->name('.orders');
    Route::get('/requests', [AccountController::class, 'requests'])->name('.requests');

    Route::get('/addresses', [AddressController::class, 'index'])->name('.addresses');
    Route::get('/addresses/new', [AddressController::class, 'create'])->name('.addresses.create');
    Route::post('/addresses', [AddressController::class, 'store'])->name('.addresses.store');
    Route::get('/addresses/{address}/edit', [AddressController::class, 'edit'])->whereNumber('address')->name('.addresses.edit');
    Route::post('/addresses/{address}', [AddressController::class, 'update'])->whereNumber('address')->name('.addresses.update');
    Route::post('/addresses/{address}/default', [AddressController::class, 'makeDefault'])->whereNumber('address')->name('.addresses.default');
    Route::post('/addresses/{address}/delete', [AddressController::class, 'destroy'])->whereNumber('address')->name('.addresses.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('.profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('.profile.update');
    Route::post('/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:auth-forms')->name('.password.update');
});
