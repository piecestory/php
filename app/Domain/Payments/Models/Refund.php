<?php

declare(strict_types=1);

namespace App\Domain\Payments\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Payments\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_id', 'amount', 'reason', 'status', 'provider_reference', 'processed_by'])]
class Refund extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'status' => RefundStatus::class];
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
