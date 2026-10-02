<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Store\Enums\BranchType;
use App\Domain\Store\Models\Branch;
use Illuminate\Database\Seeder;

/** Branches provided by the owner. Addresses, phones and map links are completed from the admin panel. */
class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            // Main showroom; coordinates provided by the owner.
            ['name_en' => 'Al-Bawadi Showroom', 'name_ar' => 'معرض البوادي', 'district' => 'البوادي', 'type' => BranchType::Showroom, 'is_pickup_point' => true,
                'map_url' => 'https://www.google.com/maps/search/?api=1&query=21.587040,39.175657'],
            ['name_en' => 'Al-Harazat Showroom', 'name_ar' => 'معرض الحرازات', 'district' => 'الحرازات', 'type' => BranchType::Showroom, 'is_pickup_point' => true],
            ['name_en' => 'Al-Khumrah Warehouse', 'name_ar' => 'مستودع الخمرة', 'district' => 'الخمرة', 'type' => BranchType::Warehouse, 'is_pickup_point' => false],
        ];

        foreach ($branches as $order => $branch) {
            Branch::query()->firstOrCreate(['name_en' => $branch['name_en']], [
                ...$branch,
                'city' => 'جدة',
                'is_active' => true,
                'sort_order' => $order + 1,
            ]);
        }
    }
}
