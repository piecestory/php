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
use App\Domain\Orders\Models\Order;
use App\Domain\PersonalFinder\Models\FinderRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
    }
}
