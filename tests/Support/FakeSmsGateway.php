<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Notifications\Sms\SmsGateway;

final class FakeSmsGateway implements SmsGateway
{
    /** @var list<array{phone: string, message: string}> */
    public array $sent = [];

    public function send(string $phone, string $message): void
    {
        $this->sent[] = ['phone' => $phone, 'message' => $message];
    }

    public function lastCode(): string
    {
        preg_match('/\b(\d{6})\b/', end($this->sent)['message'] ?? '', $m);

        return $m[1] ?? '';
    }
}
