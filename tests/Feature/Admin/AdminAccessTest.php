<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Filament\Widgets\LatestOrders;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/** Every section of the panel and the role(s) allowed in. */
dataset('sections', [
    'products' => ['/admin/products', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'categories' => ['/admin/categories', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'collections' => ['/admin/collections', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'origins' => ['/admin/origins', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'eras' => ['/admin/eras', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'materials' => ['/admin/materials', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'orders' => ['/admin/orders', [Role::Admin, Role::StoreManager, Role::CustomerService]],
    'customers' => ['/admin/customers', [Role::Admin, Role::StoreManager, Role::CustomerService]],
    'contact messages' => ['/admin/messages', [Role::Admin, Role::StoreManager, Role::CustomerService]],
    'finder requests' => ['/admin/finder-requests', [Role::Admin, Role::StoreManager, Role::CustomerService]],
    'consignments' => ['/admin/consignments', [Role::Admin, Role::StoreManager, Role::CustomerService]],
    'site pages' => ['/admin/site-pages', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'journal' => ['/admin/posts', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'faqs' => ['/admin/faqs', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'hero slides' => ['/admin/hero-slides', [Role::Admin, Role::StoreManager, Role::ContentEditor]],
    'branches' => ['/admin/branches', [Role::Admin]],
    'shipping' => ['/admin/shipping-methods', [Role::Admin]],
    'settings' => ['/admin/store-settings', [Role::Admin]],
    'staff' => ['/admin/staff', [Role::Admin]],
    'change log' => ['/admin/change-log', [Role::Admin]],
]);

it('opens each section only to the roles that need it', function (string $url, array $allowed): void {
    foreach (Role::cases() as $role) {
        $staff = User::factory()->create()->assignRole($role->value);

        $this->actingAs($staff)->get($url)->assertStatus(in_array($role, $allowed, true) ? 200 : 403);
    }
})->with('sections');

it('lets every staff role reach the dashboard', function (Role $role): void {
    $this->actingAs(User::factory()->create()->assignRole($role->value))->get('/admin')->assertOk();
})->with(Role::cases());

it('keeps customers and deactivated staff out of the panel', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->create(['is_active' => false])->assignRole(Role::Admin->value))->get('/admin')->assertForbidden();
});

it('sends signed-out visitors to the staff sign-in page', function (): void {
    $this->get('/admin/orders')->assertRedirect('/admin/login');
    $this->get('/admin/login')->assertOk();
});

it('shows the panel in Arabic, right to left', function (): void {
    $this->actingAs(User::factory()->create()->assignRole(Role::Admin->value))
        ->get('/admin')
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSeeLivewire(LatestOrders::class);
});
