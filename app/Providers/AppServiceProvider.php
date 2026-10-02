<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auctions\Models\Auction;
use App\Domain\Auctions\Models\AuctionLot;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Collection;
use App\Domain\Catalog\Models\Product;
use App\Domain\Content\Models\HeroSlide;
use App\Domain\Content\Models\Post;
use App\Domain\Identity\Models\User;
use App\Domain\Notifications\Sms\LogSmsGateway;
use App\Domain\Notifications\Sms\SmsGateway;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\PaymentGateways;
use App\Domain\PersonalFinder\Models\FinderRequest;
use App\Domain\Settings\StoreSettings;
use App\Domain\Shared\Listeners\RecordImageDimensions;
use App\Http\Support\CurrentCart;
use App\Http\Support\CurrentWishlist;
use App\Infrastructure\Payments\SandboxGateway;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StoreSettings::class);

        // Per-request shopping state (reset between requests and tests).
        $this->app->scoped(CurrentCart::class);
        $this->app->scoped(CurrentWishlist::class);

        // Real providers are selected from admin settings once configured (Phase 11).
        $this->app->bind(SmsGateway::class, LogSmsGateway::class);

        // Real providers (Moyasar for mada / Apple Pay, Tabby, Tamara) are added here once their
        // accounts are ready. The internal sandbox never runs in production.
        $this->app->singleton(PaymentGateways::class, function (): PaymentGateways {
            $gateways = [];

            if (! $this->app->isProduction() && config('payments.sandbox.enabled')) {
                $gateways[] = $this->app->make(SandboxGateway::class);
            }

            return new PaymentGateways($gateways);
        });
    }

    public function boot(): void
    {
        $isProduction = $this->app->isProduction();

        // Outside production: fail loudly on N+1 queries, missing attributes and silently discarded fills.
        Model::shouldBeStrict(! $isProduction);

        Date::use(CarbonImmutable::class);

        // Polymorphic columns store these stable aliases, never PHP class names.
        Relation::enforceMorphMap([
            'user' => User::class,
            'category' => Category::class,
            'product' => Product::class,
            'collection' => Collection::class,
            'order' => Order::class,
            'finder_request' => FinderRequest::class,
            'auction' => Auction::class,
            'auction_lot' => AuctionLot::class,
            'post' => Post::class,
            'hero_slide' => HeroSlide::class,
        ]);

        // Blocks migrate:fresh / db:wipe against the live database.
        DB::prohibitDestructiveCommands($isProduction);

        if ($isProduction) {
            URL::forceHttps();
        }

        Password::defaults(fn () => $isProduction
            ? Password::min(10)->letters()->numbers()
            : Password::min(8));

        // Reset links open in the customer's own language.
        ResetPassword::createUrlUsing(fn (User $user, string $token) => localized_route(
            'password.reset',
            ['token' => $token, 'email' => $user->email],
            $user->locale,
        ));

        $this->configureRateLimiting();

        Event::listen(MediaHasBeenAddedEvent::class, RecordImageDimensions::class);
    }

    private function configureRateLimiting(): void
    {
        // Sign-up and password forms: per form and per visitor.
        RateLimiter::for('auth-forms', fn (Request $request) => Limit::perMinute(5)
            ->by($request->route()?->getName().'|'.$request->ip()));

        // Cart and wishlist changes: generous for people, a ceiling for scripts.
        RateLimiter::for('shopping', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // Placing orders and starting payments.
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Payment provider notifications.
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        // Order tracking and code checks: blocks guessing order numbers or codes.
        RateLimiter::for('lookups', fn (Request $request) => Limit::perMinute(10)
            ->by($request->route()?->getName().'|'.$request->ip()));
    }
}
