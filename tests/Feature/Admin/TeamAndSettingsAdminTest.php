<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Filament\Pages\StoreSettings;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\ShippingMethods\Pages\ManageShippingMethods;
use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\ShippingMethodSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, SettingsSeeder::class, ShippingMethodSeeder::class]);
    $this->admin = User::factory()->create()->assignRole(Role::Admin->value);
    $this->actingAs($this->admin);
});

it('adds a team member who can then sign in to the panel', function (): void {
    Livewire::test(CreateStaff::class)
        ->fillForm(['name' => 'نورة', 'email' => 'Noura@PieceNStory.com', 'phone' => '0559876543', 'role' => Role::ContentEditor->value, 'password' => 'Strong-pass-123', 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $member = User::query()->where('email', 'noura@piecenstory.com')->sole();
    expect($member->hasRole(Role::ContentEditor->value))->toBeTrue()
        ->and($member->phone)->toBe('+966559876543')
        ->and(Hash::check('Strong-pass-123', $member->password))->toBeTrue();

    $this->actingAs($member)->get('/admin/products')->assertOk();
    $this->actingAs($member)->get('/admin/orders')->assertForbidden();
});

it('refuses to let admins remove their own access or the last admin', function (): void {
    Livewire::test(EditStaff::class, ['record' => $this->admin->id])
        ->fillForm(['role' => Role::ContentEditor->value])
        ->call('save');
    expect($this->admin->refresh()->hasRole(Role::Admin->value))->toBeTrue();

    $other = User::factory()->create()->assignRole(Role::Admin->value);
    $this->actingAs($other);
    Livewire::test(EditStaff::class, ['record' => $this->admin->id])->fillForm(['is_active' => false])->call('save');
    expect($this->admin->refresh()->is_active)->toBeFalse(); // another admin remains: allowed

    Livewire::test(EditStaff::class, ['record' => $other->id])->fillForm(['role' => Role::StoreManager->value])->call('save');
    expect($other->refresh()->hasRole(Role::Admin->value))->toBeTrue(); // own role: refused
});

it('keeps the last active admin', function (): void {
    $manager = User::factory()->create()->assignRole(Role::Admin->value);
    $this->actingAs($manager);
    $this->admin->forceFill(['is_active' => false])->save();

    // $manager is now the only active admin and cannot be demoted by anyone.
    $third = User::factory()->create()->assignRole(Role::Admin->value);
    $third->forceFill(['is_active' => false])->save();
    expect(fn () => app(App\Domain\Identity\Actions\SaveStaffMember::class)->handle(
        ['name' => 'x', 'email' => $manager->email, 'role' => Role::StoreManager, 'is_active' => true], $third, $manager,
    ))->toThrow(App\Domain\Identity\Exceptions\StaffChangeRefused::class);
});

it('lists customers only, and can switch an account off', function (): void {
    $customer = User::factory()->create(['name' => 'عميل كريم']);

    Livewire::test(ListCustomers::class)->assertCanSeeTableRecords([$customer])->assertCanNotSeeTableRecords([$this->admin]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->id])->callAction('toggleActive');
    expect($customer->refresh()->is_active)->toBeFalse();
    $this->get("/admin/customers/{$this->admin->id}")->assertNotFound(); // staff are not customers
});

it('will not open delivery to customers before it has a price', function (): void {
    $delivery = ShippingMethod::query()->where('code', ShippingMethod::DELIVERY)->sole();

    Livewire::test(ManageShippingMethods::class)
        ->callTableAction('edit', $delivery, ['is_active' => true, 'rate' => '0'])
        ->assertHasTableActionErrors(['is_active']);
    expect($delivery->refresh()->is_active)->toBeFalse();

    Livewire::test(ManageShippingMethods::class)
        ->callTableAction('edit', $delivery, ['is_active' => true, 'rate' => '35', 'free_shipping_threshold' => '150'])
        ->assertHasNoTableActionErrors();
    expect($delivery->refresh()->is_active)->toBeTrue()->and($delivery->rate)->toBe('35.00');
});

it('saves the store contact details shown in the footer', function (): void {
    Livewire::test(StoreSettings::class)
        ->fillForm(['email' => 'Info@PieceNStory.com', 'phone' => '012 345 6789'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/')->assertSee('mailto:info@piecenstory.com', false)->assertSee('tel:0123456789', false);
});

it('records who changed a piece and what changed', function (): void {
    $product = Product::factory()->create(['price' => 1000]);
    Activity::query()->delete();

    Livewire::test(EditProduct::class, ['record' => $product->id])->fillForm(['price' => '1200'])->call('save');

    $entry = Activity::query()->sole();
    expect($entry->causer_id)->toBe($this->admin->id)
        ->and($entry->attribute_changes->get('old'))->toMatchArray(['price' => '1000.00'])
        ->and($entry->attribute_changes->get('attributes'))->toMatchArray(['price' => '1200.00']);
    $this->get('/admin/change-log')->assertOk();
});

it('creates the first admin from the command line without the password in history', function (): void {
    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'المالك')
        ->expectsQuestion('Email', 'owner@piecenstory.com')
        ->expectsQuestion('Password', 'Owner-pass-2026')
        ->assertSuccessful();

    expect(User::query()->where('email', 'owner@piecenstory.com')->sole()->hasRole(Role::Admin->value))->toBeTrue();
});
