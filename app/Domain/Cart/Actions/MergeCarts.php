<?php

declare(strict_types=1);

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\Models\Cart;
use Illuminate\Support\Facades\DB;

/**
 * Moves a guest cart into the member's cart at sign-in. Quantities are combined up to the stock
 * available; pieces that are no longer for sale are dropped. The guest cart is deleted.
 */
final class MergeCarts
{
    public function handle(Cart $guest, Cart $member): void
    {
        if ($guest->is($member)) {
            return;
        }

        DB::transaction(function () use ($guest, $member): void {
            foreach ($guest->items()->with('product')->get() as $item) {
                $product = $item->product;
                if (! $product->isPurchasable()) {
                    continue;
                }

                $existing = $member->items()->firstOrNew(['product_id' => $product->id], ['quantity' => 0]);
                $existing->quantity = min($product->stock_quantity, $existing->quantity + $item->quantity);
                $existing->save();
            }

            $guest->delete();
        });
    }
}
