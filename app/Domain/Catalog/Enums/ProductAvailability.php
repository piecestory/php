<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum ProductAvailability: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case OnRequest = 'on_request';
}
