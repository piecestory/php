<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Enums\Permission as PermissionName;
use App\Domain\Identity\Enums\Role as RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        foreach (RoleName::cases() as $roleName) {
            Role::findOrCreate($roleName->value)->syncPermissions(
                array_map(fn (PermissionName $p) => $p->value, $roleName->permissions()),
            );
        }
    }
}
