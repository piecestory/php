<?php

declare(strict_types=1);

namespace App\Domain\PersonalFinder\Enums;

/**
 * Personal Finder journey: received → reviewing → sourcing → offer sent → closed. An offer the customer
 * turns down goes back to sourcing; a request can be cancelled at any open stage.
 */
enum FinderRequestStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Sourcing = 'sourcing';
    case OfferSent = 'offer_sent';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Reviewing, self::Sourcing, self::Cancelled],
            self::Reviewing => [self::Sourcing, self::OfferSent, self::Closed, self::Cancelled],
            self::Sourcing => [self::OfferSent, self::Closed, self::Cancelled],
            self::OfferSent => [self::Sourcing, self::Closed, self::Cancelled],
            self::Closed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return __("requests.finder.status.{$this->value}");
    }
}
