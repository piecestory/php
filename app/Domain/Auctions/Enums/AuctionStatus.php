<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Enums;

/**
 * Stored publication state. Staff use Draft, Scheduled ("published") and Cancelled; whether a published
 * auction is upcoming, live or over comes from its dates (Auction::phase). Live and Ended are kept for
 * compatibility and behave like Scheduled.
 */
enum AuctionStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    /** @return list<self> what staff can choose */
    public static function editable(): array
    {
        return [self::Draft, self::Scheduled, self::Cancelled];
    }

    public function isPublic(): bool
    {
        return in_array($this, [self::Scheduled, self::Live, self::Ended], true);
    }

    public function label(): string
    {
        return __("auctions.status.{$this->value}");
    }
}
