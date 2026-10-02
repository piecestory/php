<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Domain\Store\Models\Branch;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role as RoleModel;

it('seeds reference data idempotently', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Category::query()->count())->toBe(6)
        ->and(Branch::query()->count())->toBe(3)
        ->and(ShippingMethod::query()->count())->toBe(2)
        ->and(RoleModel::query()->count())->toBe(count(Role::cases()));
});

it('gives each role exactly its defined permissions', function (Role $role): void {
    $this->seed(DatabaseSeeder::class);

    $granted = RoleModel::findByName($role->value)->permissions->pluck('name')->sort()->values()->all();
    $expected = collect($role->permissions())->map->value->sort()->values()->all();

    expect($granted)->toBe($expected);
})->with(Role::cases());

it('grants admin panel access to every staff role', function (Role $role): void {
    expect($role->permissions())->toContain(Permission::AccessAdmin);
})->with(Role::cases());

it('keeps home delivery disabled until delivery pricing is decided', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(ShippingMethod::query()->where('code', ShippingMethod::DELIVERY)->value('is_active'))->toBeFalse()
        ->and(ShippingMethod::query()->where('code', ShippingMethod::BRANCH_PICKUP)->value('is_active'))->toBeTrue();
});
