<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Data;

use App\Domain\Orders\Enums\OrderType;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Support\Money\Vat;

/** Order amounts (all VAT inclusive, decimal strings). Used for the checkout page and for the order itself. */
final readonly class OrderTotals
{
    public function __construct(
        public string $subtotal,
        public string $shipping,
        public string $grandTotal,
        public string $vat,
        public string $deposit,
    ) {}

    public static function calculate(string $subtotal, ?ShippingMethod $method, OrderType $type = OrderType::Purchase): self
    {
        $shipping = self::shippingFor($subtotal, $method);
        $grandTotal = bcadd($subtotal, $shipping, 2);
        $deposit = $type === OrderType::DepositReservation
            ? Vat::percentOf($grandTotal, (string) config('store.reservation.deposit_percent'))
            : '0.00';

        return new self($subtotal, $shipping, $grandTotal, Vat::included($grandTotal), $deposit);
    }

    public static function shippingFor(string $subtotal, ?ShippingMethod $method): string
    {
        if ($method === null) {
            return '0.00';
        }

        $threshold = $method->free_shipping_threshold;
        if ($threshold !== null && bccomp($subtotal, (string) $threshold, 2) >= 0) {
            return '0.00';
        }

        return bcadd((string) $method->rate, '0', 2);
    }
}
