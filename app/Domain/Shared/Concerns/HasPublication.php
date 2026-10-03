<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns;

use App\Domain\Shared\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * For models with `status` (PublicationStatus) and `published_at`: visible to the public only when
 * published and the publication date has arrived (allows scheduling).
 */
trait HasPublication
{
    /** Publishing without a date means "now"; a future date schedules the publication. */
    protected static function bootHasPublication(): void
    {
        static::saving(function (self $model): void {
            if ($model->getAttribute('status') === PublicationStatus::Published && $model->getAttribute('published_at') === null) {
                $model->setAttribute('published_at', now());
            }
        });
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), PublicationStatus::Published)
            ->whereNotNull($this->qualifyColumn('published_at'))
            ->where($this->qualifyColumn('published_at'), '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === PublicationStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
