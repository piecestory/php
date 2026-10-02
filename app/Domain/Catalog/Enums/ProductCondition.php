<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum ProductCondition: string
{
    case Excellent = 'excellent';
    case VeryGood = 'very_good';
    case Good = 'good';
    case Restored = 'restored';
    case AsFound = 'as_found';
}
