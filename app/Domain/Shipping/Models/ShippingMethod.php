<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Models;

use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'name_ar', 'name_en', 'description_ar', 'description_en', 'rate', 'free_shipping_threshold',
    'requires_pickup_branch', 'is_active', 'sort_order',
])]
class ShippingMethod extends Model
{
    use HasTranslations;

    public const string DELIVERY = 'delivery';

    public const string BRANCH_PICKUP = 'branch_pickup';

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'requires_pickup_branch' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
