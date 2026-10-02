<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
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
