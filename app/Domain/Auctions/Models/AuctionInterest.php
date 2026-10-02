<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['auction_id', 'auction_lot_id', 'user_id', 'name', 'phone', 'email'])]
class AuctionInterest extends Model
{
    /** @return BelongsTo<Auction, $this> */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /** @return BelongsTo<AuctionLot, $this> */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(AuctionLot::class, 'auction_lot_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
