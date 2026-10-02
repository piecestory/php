<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Enums;

enum AuctionStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Ended = 'ended';
    case Cancelled = 'cancelled';
}
