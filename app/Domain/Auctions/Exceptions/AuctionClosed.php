<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Exceptions;

use RuntimeException;

/** Interest can no longer be registered: the auction has ended, was cancelled or is not published. */
final class AuctionClosed extends RuntimeException {}
