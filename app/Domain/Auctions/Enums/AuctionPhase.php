<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Enums;

/** Where a published auction stands, derived from its dates (staff only publish, cancel or keep a draft). */
enum AuctionPhase: string
{
    case Upcoming = 'upcoming';
    case Live = 'live';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("auctions.phase.{$this->value}");
    }

    /** Visitors can register interest until the auction ends. */
    public function acceptsInterest(): bool
    {
        return $this === self::Upcoming || $this === self::Live;
    }
}
