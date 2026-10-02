<?php

declare(strict_types=1);

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\Exceptions\CartException;
use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\Product;

/** Sets a line's quantity; zero removes the line. Removing is always allowed, increasing only for available pieces. */
final class UpdateCartItem
{
    /** @throws CartException */
    public function handle(Cart $cart, Product $product, int $quantity): void
    {
        $item = $cart->items()->where('product_id', $product->id)->first();
        if ($item === null) {
            return;
        }

        if ($quantity <= 0) {
            $item->delete();
        } else {
            if ($quantity > $item->quantity && ! $product->isPurchasable()) {
                throw CartException::unavailable();
            }
            if ($quantity > $product->stock_quantity) {
                throw CartException::quantityExceeded();
            }
            $item->update(['quantity' => $quantity]);
        }

        $cart->touchActivity();
    }
}
