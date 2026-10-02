<?php

declare(strict_types=1);

namespace App\Domain\Store\Enums;

enum BranchType: string
{
    case Showroom = 'showroom';
    case Warehouse = 'warehouse';
}
