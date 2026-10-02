<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Cart\Actions\MergeCarts;
use App\Domain\Identity\Models\User;
use App\Http\Support\CurrentCart;
use App\Http\Support\CurrentWishlist;
use Illuminate\Auth\Events\Login;

/** Whatever a visitor collected before signing in (cart, wishlist) moves into their account. */
final class MergeGuestShoppingState
{
    public function __construct(
        private readonly CurrentCart $cart,
        private readonly CurrentWishlist $wishlist,
        private readonly MergeCarts $merge,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->cart->mergeGuestCartInto($event->user, $this->merge);
        $this->wishlist->mergeGuestListInto($event->user);
    }
}
