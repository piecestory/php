<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enums;

enum InventoryReason: string
{
    case InitialStock = 'initial_stock';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Cancellation = 'cancellation';
    case Return = 'return';
}
