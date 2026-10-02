<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Reference data only: safe and idempotent in every environment, including production. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CategorySeeder::class,
            BranchSeeder::class,
            ShippingMethodSeeder::class,
        ]);
    }
}
