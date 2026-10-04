<?php

declare(strict_types=1);

namespace App\Domain\Identity\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Password reset email, sent from the queue like every other email: the "forgot password" page answers
 * at once whatever the mail server is doing, and a temporary mail failure is retried instead of
 * showing the visitor an error page.
 */
final class ResetPasswordLink extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> seconds between attempts */
    public array $backoff = [60, 300];
}
