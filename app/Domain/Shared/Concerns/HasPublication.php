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
