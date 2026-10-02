<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns;

use App\Domain\Shared\Models\StatusChange;
use BackedEnum;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Gives a model an audit trail of status transitions in the shared `status_changes` table.
 */
trait HasStatusHistory
{
    /** @return MorphMany<StatusChange, $this> */
    public function statusChanges(): MorphMany
    {
        return $this->morphMany(StatusChange::class, 'subject')->latest('id');
    }

    public function recordStatusChange(?BackedEnum $from, BackedEnum $to, ?int $changedBy = null, ?string $note = null): StatusChange
    {
        return $this->statusChanges()->create([
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);
    }
}
