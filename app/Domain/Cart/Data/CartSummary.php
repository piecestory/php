<?php

declare(strict_types=1);

namespace App\Domain\Cart\Data;

use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Models\CartItem;
use App\Support\Money\Vat;

/**
 * What the customer would pay now. Prices are read live from the catalogue (VAT inclusive);
 * lines whose piece is no longer for sale are listed separately and excluded from the total.
 */
final readonly class CartSummary
{
    /**
     * @param  list<CartLine>  $lines
     * @param  list<CartItem>  $unavailable
     */
    public function __construct(
        public array $lines,
        public array $unavailable,
        public string $total,
        public string $vat,
        public int $count,
    ) {}

    public static function empty(): self
    {
        return new self([], [], '0.00', '0.00', 0);
    }

    public static function for(?Cart $cart): self
    {
        if ($cart === null) {
            return self::empty();
        }

        $lines = [];
        $unavailable = [];
        $total = '0.00';
        $count = 0;

        foreach ($cart->items()->with('product.media')->oldest('id')->get() as $item) {
            if (! $item->product->isPurchasable() || $item->quantity > $item->product->stock_quantity) {
                $unavailable[] = $item;

                continue;
            }

            $line = new CartLine($item, $item->product->effectivePrice());
            $lines[] = $line;
            $total = bcadd($total, $line->lineTotal(), 2);
            $count += $item->quantity;
        }

        return new self($lines, $unavailable, $total, Vat::included($total), $count);
    }

    public function isEmpty(): bool
    {
        return $this->lines === [] && $this->unavailable === [];
    }
}
