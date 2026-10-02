<?php

declare(strict_types=1);

namespace App\Domain\PersonalFinder\Enums;

enum FinderRequestStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Sourcing = 'sourcing';
    case OfferSent = 'offer_sent';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
