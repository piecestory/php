<?php

declare(strict_types=1);

namespace App\Domain\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Idempotency log: a provider event is processed at most once (unique provider + event_id). */
#[Fillable(['provider', 'event_id', 'event_type', 'payload_hash', 'processed_at'])]
class PaymentWebhookEvent extends Model
{
    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
