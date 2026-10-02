<?php

declare(strict_types=1);

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\Exceptions\CartException;
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Models\CartItem;
use App\Domain\Catalog\Models\Product;
use Closure;

final class AddToCart
{
    /**
     * The cart is resolved only once the piece is known to be addable, so refused additions
     * never create empty guest carts.
     *
     * @param  Closure(): Cart  $cart
     *
     * @throws CartException
     */
    public function handle(Closure $cart, Product $product, int $quantity = 1): CartItem
    {
        if (! $product->isPurchasable()) {
            throw CartException::unavailable();
        }

        $cart = $cart();

        /** @var CartItem $item */
        $item = $cart->items()->firstOrNew(['product_id' => $product->id], ['quantity' => 0]);
        $newQuantity = $item->quantity + max(1, $quantity);

        if ($newQuantity > $product->stock_quantity) {
            throw CartException::quantityExceeded();
        }

        $item->quantity = $newQuantity;
        $item->save();
        $cart->touchActivity();

        return $item;
    }
}
