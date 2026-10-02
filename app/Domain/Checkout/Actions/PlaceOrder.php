<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Actions;

use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Data\CheckoutDetails;
use App\Domain\Checkout\Data\OrderTotals;
use App\Domain\Checkout\Exceptions\CheckoutException;
use App\Domain\Inventory\Actions\StockLedger;
use App\Domain\Orders\Actions\NotifyCustomer;
use App\Domain\Orders\Actions\TransitionOrder;
use App\Domain\Orders\Enums\OrderNotice;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns the cart into an order in one transaction: re-checks every piece under a row lock (two
 * customers can never buy the same unique piece), snapshots names and prices, takes the pieces out
 * of stock for the hold period and empties the cart.
 *
 *  - Purchase / deposit reservation: held for the payment window (15 minutes), then payment.
 *  - Reservation without deposit: reserved at once for 4 days + 3 reminder days.
 */
final class PlaceOrder
{
    public function __construct(
        private readonly StockLedger $stock,
        private readonly TransitionOrder $transition,
        private readonly NotifyCustomer $notify,
    ) {}

    /** @throws CheckoutException */
    public function handle(Cart $cart, CheckoutDetails $details): Order
    {
        $order = DB::transaction(function () use ($cart, $details): Order {
            $items = $cart->items()->get();
            if ($items->isEmpty()) {
                throw CheckoutException::emptyCart();
            }

            // Locked in id order so concurrent checkouts cannot deadlock each other.
            $products = Product::query()
                ->whereKey($items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if ($product === null || ! $product->isPurchasable() || $item->quantity > $product->stock_quantity) {
                    throw CheckoutException::unavailable((string) ($product?->translate('name') ?? ''));
                }
            }

            if ($details->type->isReservation()) {
                $this->guardReservationLimit($details->phone);
            }

            $subtotal = '0.00';
            foreach ($items as $item) {
                $subtotal = bcadd($subtotal, bcmul($products[$item->product_id]->effectivePrice(), (string) $item->quantity, 2), 2);
            }
            $totals = OrderTotals::calculate($subtotal, $details->shippingMethod, $details->type);

            [$holdUntil, $reservedUntil] = $this->holdPeriod($details->type);

            $order = Order::query()->create([
                'number' => 'TMP-'.Str::random(16),
                'type' => $details->type,
                'access_token' => Str::random(40),
                'user_id' => $details->userId,
                'customer_name' => $details->name,
                'phone' => $details->phone,
                'email' => $details->email,
                'locale' => $details->locale,
                'currency' => 'SAR',
                'subtotal' => $totals->subtotal,
                'shipping_total' => $totals->shipping,
                'tax_total' => $totals->vat,
                'grand_total' => $totals->grandTotal,
                'deposit_total' => $totals->deposit,
                'shipping_method_id' => $details->shippingMethod->id,
                'pickup_branch_id' => $details->pickupBranch?->id,
                ...$this->addressSnapshot($details),
                'customer_note' => $details->note,
                'placed_at' => now(),
                'hold_expires_at' => $holdUntil,
                'reserved_until' => $reservedUntil,
            ]);

            // Readable, sequential number: PS-2026-000123
            $order->forceFill(['number' => sprintf('PS-%s-%06d', now()->format('Y'), $order->id)])->save();
            $order->recordStatusChange(null, OrderStatus::Pending);

            foreach ($items as $item) {
                $product = $products[$item->product_id];
                $unitPrice = $product->effectivePrice();

                $order->items()->create([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name_ar' => $product->name_ar,
                    'name_en' => $product->name_en,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'line_total' => bcmul($unitPrice, (string) $item->quantity, 2),
                ]);

                $this->stock->hold($order, $product, $item->quantity, $holdUntil);
            }

            if ($details->type === OrderType::Reservation) {
                $this->transition->handle($order, OrderStatus::Reserved, note: 'reserved_without_deposit');
                $this->notify->handle($order, OrderNotice::Reserved);
            }

            $cart->items()->delete();

            return $order;
        });

        return $order;
    }

    /** @return array{0: \Carbon\CarbonImmutable, 1: ?\Carbon\CarbonImmutable} [hold until, reserved until] */
    private function holdPeriod(OrderType $type): array
    {
        if ($type === OrderType::Reservation) {
            $reservedUntil = now()->addDays((int) config('store.reservation.hold_days'));

            return [$reservedUntil->addDays((int) config('store.reservation.reminder_days')), $reservedUntil];
        }

        // Purchases and deposit reservations: the payment window. A paid deposit extends it.
        return [now()->addMinutes((int) config('store.payment_hold_minutes')), null];
    }

    private function guardReservationLimit(string $phone): void
    {
        $limit = (int) config('store.reservation.max_open_per_phone');

        $open = Order::query()
            ->where('phone', $phone)
            ->whereIn('type', [OrderType::Reservation, OrderType::DepositReservation])
            ->whereIn('status', [OrderStatus::Pending, OrderStatus::Reserved])
            ->where('hold_expires_at', '>', now())
            ->count();

        if ($open >= $limit) {
            throw CheckoutException::tooManyReservations($limit);
        }
    }

    /** @return array<string, ?string> */
    private function addressSnapshot(CheckoutDetails $details): array
    {
        $address = $details->address;

        return [
            'ship_recipient_name' => $details->name,
            'ship_phone' => $details->phone,
            'ship_city' => $address['city'] ?? null,
            'ship_district' => $address['district'] ?? null,
            'ship_street' => $address['street'] ?? null,
            'ship_building_number' => $address['building_number'] ?? null,
            'ship_postal_code' => $address['postal_code'] ?? null,
            'ship_additional_number' => $address['additional_number'] ?? null,
            'ship_short_address' => $address['short_address'] ?? null,
        ];
    }
}
