<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shipping\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        ShippingMethod::query()->firstOrCreate(['code' => ShippingMethod::BRANCH_PICKUP], [
            'name_ar' => 'الاستلام من الفرع',
            'name_en' => 'Pick up from showroom',
            'rate' => 0,
            'requires_pickup_branch' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Inactive until the owner sets the delivery pricing (open decision before Phase 9).
        ShippingMethod::query()->firstOrCreate(['code' => ShippingMethod::DELIVERY], [
            'name_ar' => 'التوصيل داخل المملكة',
            'name_en' => 'Delivery within Saudi Arabia',
            'rate' => 0,
            'is_active' => false,
            'sort_order' => 1,
        ]);
    }
}
