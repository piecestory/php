<?php

declare(strict_types=1);

namespace App\Domain\Consignment\Enums;

/** "Sell with us" review: received → under review → approved or declined (owner's decision). */
enum ConsignmentStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Reviewing, self::Approved, self::Rejected],
            self::Reviewing => [self::Approved, self::Rejected],
            self::Approved, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return __("requests.consignment.status.{$this->value}");
    }
}
