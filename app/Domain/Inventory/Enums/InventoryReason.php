<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enums;

enum InventoryReason: string
{
    case InitialStock = 'initial_stock';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    /** Taken out of stock while an order waits for payment or a reservation runs. */
    case Hold = 'hold';
    /** Put back when that order is cancelled or expires. */
    case HoldReleased = 'hold_released';
    case Cancellation = 'cancellation';
    case Return = 'return';
}
