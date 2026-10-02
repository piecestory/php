<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Sms;

/**
 * Sends a text message to a Saudi mobile number (E.164). Providers (Unifonic, Taqnyat, Msegat, ...)
 * are added as implementations and selected from admin settings; nothing else changes.
 */
interface SmsGateway
{
    public function send(string $phone, string $message): void;
}
