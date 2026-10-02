<?php

declare(strict_types=1);

use App\Domain\Cart\Models\Cart;
use App\Domain\Orders\Actions\ExpireOrderHolds;
use App\Domain\Orders\Actions\SendReservationReminders;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| Scheduled work. On Hostinger a single cron entry runs `php artisan schedule:run` every minute.
*/

Artisan::command('orders:expire-holds', function (ExpireOrderHolds $expire): void {
    $this->info("Cancelled {$expire->handle()} expired order(s).");
})->purpose('Cancel unpaid orders and reservations whose hold has run out; their pieces go back on sale');

Artisan::command('orders:remind-reservations', function (SendReservationReminders $remind): void {
    $this->info("Sent {$remind->handle()} reminder(s).");
})->purpose('Remind customers whose reservation period is over to complete payment');

// Background jobs (image renditions, emails, SMS): drained each minute, since shared hosting has no queue daemon.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping();

// Pieces held by unpaid checkouts return to the shop within a minute of the payment window closing.
Schedule::command('orders:expire-holds')->everyMinute()->withoutOverlapping();

// Daily payment reminder, sent early in working hours (12 pm – 12 am).
Schedule::command('orders:remind-reservations')->dailyAt('13:00');

// Abandoned guest carts.
Schedule::command('model:prune', ['--model' => [Cart::class]])->daily();
