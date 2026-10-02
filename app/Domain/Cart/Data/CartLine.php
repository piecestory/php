<?php

declare(strict_types=1);

namespace App\Domain\Cart\Data;

use App\Domain\Cart\Models\CartItem;
use App\Domain\Catalog\Models\Product;

final readonly class CartLine
{
    public function __construct(
        public CartItem $item,
        public string $unitPrice,
    ) {}

    public function product(): Product
    {
        return $this->item->product;
    }

    public function lineTotal(): string
    {
        return bcmul($this->unitPrice, (string) $this->item->quantity, 2);
    }
}
