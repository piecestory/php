<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Enums\InventoryReason;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\StockReservation;
use App\Domain\Orders\Models\Order;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Every stock change for orders goes through here and is written to the movement ledger.
 *
 * Placing an order takes the pieces out of stock at once (a "hold"), so nobody else can buy them
 * while the customer pays or while a reservation runs. Payment turns the hold into a sale (stock
 * stays as it is); cancellation or expiry puts the pieces back. Call inside a transaction.
 */
final class StockLedger
{
    /** The product must already be locked for update by the caller. */
    public function hold(Order $order, Product $product, int $quantity, DateTimeInterface $until): void
    {
        $product->decrement('stock_quantity', $quantity);

        $this->record($product, -$quantity, InventoryReason::Hold, $order);

        StockReservation::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'expires_at' => $until,
        ]);
    }

    public function extendHolds(Order $order, DateTimeInterface $until): void
    {
        $order->stockReservations()->update(['expires_at' => $until]);
    }

    /** The order was paid: the held pieces are now sold. */
    public function convertHoldsToSale(Order $order): void
    {
        $order->stockReservations()->delete();
    }

    /** The order was cancelled or expired before payment: held pieces return to the shop. */
    public function releaseHolds(Order $order, ?int $userId = null): void
    {
        foreach ($order->stockReservations()->get() as $reservation) {
            $this->putBack($reservation->product_id, $reservation->quantity, InventoryReason::HoldReleased, $order, $userId);
            $reservation->delete();
        }
    }

    /** A paid order was cancelled: its pieces return to the shop. */
    public function returnSoldItems(Order $order, ?int $userId = null): void
    {
        foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
            $this->putBack((int) $item->product_id, $item->quantity, InventoryReason::Cancellation, $order, $userId);
        }
    }

    private function putBack(int $productId, int $quantity, InventoryReason $reason, Order $order, ?int $userId): void
    {
        $product = Product::withTrashed()->lockForUpdate()->find($productId);
        if ($product === null) {
            return;
        }

        $product->increment('stock_quantity', $quantity);
        $this->record($product, $quantity, $reason, $order, $userId);
    }

    /**
     * Staff sets the quantity on hand (initial stock, count correction, piece found or damaged).
     * Held pieces are already out of stock, so this is the quantity free to sell.
     */
    public function setQuantity(Product $product, int $quantity, InventoryReason $reason, ?int $userId, ?string $note = null): void
    {
        DB::transaction(function () use ($product, $quantity, $reason, $userId, $note): void {
            $locked = Product::withTrashed()->lockForUpdate()->findOrFail($product->id);
            $change = $quantity - $locked->stock_quantity;
            if ($change === 0) {
                return;
            }

            $locked->forceFill(['stock_quantity' => $quantity])->save();
            $this->record($locked, $change, $reason, null, $userId, $note);
        });

        $product->refresh();
    }

    private function record(Product $product, int $change, InventoryReason $reason, ?Order $order, ?int $userId = null, ?string $note = null): void
    {
        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'quantity_change' => $change,
            'stock_after' => $product->stock_quantity,
            'reason' => $reason,
            'reference_type' => $order?->getMorphClass(),
            'reference_id' => $order?->id,
            'user_id' => $userId,
            'note' => $note ?? $order?->number,
        ]);
    }
}
