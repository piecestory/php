<?php

declare(strict_types=1);

use App\Domain\Cart\Models\Cart;
use Illuminate\Support\Facades\Schedule;

/*
| Scheduled work. On Hostinger a single cron entry runs `php artisan schedule:run` every minute.
*/

// Background jobs (image renditions, emails): drained each minute, since shared hosting has no queue daemon.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping();

// Abandoned guest carts.
Schedule::command('model:prune', ['--model' => [Cart::class]])->daily();
