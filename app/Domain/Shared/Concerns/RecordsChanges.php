<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Audit trail for records staff edit (who changed what, before → after), shown in the admin's
 * change log. Only fields that actually changed are kept; untouched saves leave no entry.
 */
trait RecordsChanges
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logExcept($this->changesNotRecorded())->logOnlyDirty()->dontLogEmptyChanges();
    }

    /** @return list<string> fields tracked elsewhere (e.g. stock, which has its own ledger) */
    protected function changesNotRecorded(): array
    {
        return [];
    }
}
