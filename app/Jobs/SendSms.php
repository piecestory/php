<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Notifications\Sms\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Sends an SMS from the queue so a slow provider never delays the customer's page. */
final class SendSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
    ) {
        $this->afterCommit();
    }

    public function handle(SmsGateway $sms): void
    {
        $sms->send($this->phone, $this->message);
    }
}
