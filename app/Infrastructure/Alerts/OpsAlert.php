<?php

declare(strict_types=1);

namespace App\Infrastructure\Alerts;

use App\Domain\Settings\StoreSettings;
use App\Mail\OpsAlertMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the team when something needs attention on the live site (a failed backup, a server error).
 * At most one email per subject every 30 minutes, so a burst of errors is one message, not hundreds.
 * Only the error's type, short message and address are sent — no stack traces, passwords or payment data.
 */
final class OpsAlert
{
    private const int THROTTLE_SECONDS = 1800;

    public static function send(string $subject, string $details): void
    {
        $to = config('app.alert_email') ?: app(StoreSettings::class)->get('store.email');
        if (! $to || ! Cache::add('ops-alert:'.hash('xxh3', $subject), true, self::THROTTLE_SECONDS)) {
            return;
        }

        try {
            Mail::to($to)->send(new OpsAlertMail($subject, mb_substr($details, 0, 2000)));
        } catch (Throwable $e) {
            // Mail itself may be what is broken: never let the alert hide the original problem.
            Log::warning('Ops alert could not be emailed', ['subject' => $subject, 'reason' => $e->getMessage()]);
        }
    }

    public static function forException(Throwable $e): void
    {
        $where = app()->runningInConsole() ? 'console' : request()->method().' '.request()->path();

        self::send(
            'Server error: '.class_basename($e),
            class_basename($e).': '.mb_substr($e->getMessage(), 0, 300)."\n{$where}\n".now()->toDateTimeString(),
        );
    }
}
