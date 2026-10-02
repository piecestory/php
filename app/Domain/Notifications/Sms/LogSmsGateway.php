<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Fallback driver while no SMS provider is configured: writes to the log instead of sending.
 * Message bodies (which may contain login codes) are only logged in local development.
 */
final class LogSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        Log::info('SMS not sent: no provider configured', [
            'to' => substr($phone, 0, 7).'****'.substr($phone, -2),
            'message' => app()->isLocal() ? $message : '[redacted]',
        ]);
    }
}
