<?php

declare(strict_types=1);

use App\Domain\Auctions\Models\Auction;
use App\Domain\Catalog\Models\Product;
use App\Domain\Consignment\Models\ConsignmentRequest;
use App\Domain\Content\Models\Post;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Inventory\Actions\StockLedger;
use App\Domain\Inventory\Enums\InventoryReason;
use App\Domain\Orders\Models\Order;
use App\Domain\PersonalFinder\Models\FinderRequest;
use App\Filament\Resources\Auctions\Pages\EditAuction;
use App\Filament\Resources\Auctions\RelationManagers\InterestsRelationManager;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\InventoryMovementsRelationManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

/*
| Every create / edit / detail screen of the staff panel opens with a real record. Catches broken
| forms, columns or labels that the section-access tests (list pages only) cannot see.
*/

beforeEach(function (): void {
    $this->seed([SettingsSeeder::class, RolesAndPermissionsSeeder::class]);
    $this->actingAs(User::factory()->create()->assignRole(Role::Admin->value));
});

it('opens every create screen', function (string $url): void {
    $this->get($url)->assertOk();
})->with([
    '/admin/products/create', '/admin/posts/create', '/admin/auctions/create', '/admin/staff/create',
]);

it('opens every edit and detail screen with a real record', function (Closure $record, string $pattern): void {
    $this->get(sprintf($pattern, $record()->getKey()))->assertOk();
})->with([
    'product' => [fn () => Product::factory()->published()->create(), '/admin/products/%d/edit'],
    'journal post' => [fn () => Post::factory()->published()->create(), '/admin/posts/%d/edit'],
    'auction' => [fn () => Auction::factory()->create(), '/admin/auctions/%d/edit'],
    'staff member' => [fn () => User::factory()->create()->assignRole(Role::StoreManager->value), '/admin/staff/%d/edit'],
    'customer' => [fn () => User::factory()->create(), '/admin/customers/%d'],
    'order' => [fn () => Order::factory()->create(), '/admin/orders/%d'],
    'finder request' => [fn () => FinderRequest::factory()->create(), '/admin/finder-requests/%d'],
    'consignment' => [fn () => tap(new ConsignmentRequest([
        'name' => 'سلمان', 'phone' => '+966559876543', 'city' => 'جدة', 'title' => 'ساعة جدارية',
        'description' => 'ساعة جدارية فرنسية من القرن التاسع عشر بحالة جيدة.',
    ]), fn (ConsignmentRequest $request) => $request->forceFill(['reference' => 'CS-2026-000001'])->save()), '/admin/consignments/%d'],
]);

it('shows a customer\'s orders on their page', function (): void {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    Order::factory()->create();

    Livewire::test(OrdersRelationManager::class, ['ownerRecord' => $customer, 'pageClass' => ViewCustomer::class])
        ->assertOk()
        ->assertCanSeeTableRecords([$order])
        ->assertCountTableRecords(1);
});

it('shows a piece\'s stock history on its page', function (): void {
    $product = Product::factory()->published()->create(['stock_quantity' => 1]);
    app(StockLedger::class)->setQuantity($product, 4, InventoryReason::Adjustment, null, 'جرد');

    Livewire::test(InventoryMovementsRelationManager::class, ['ownerRecord' => $product->fresh(), 'pageClass' => EditProduct::class])
        ->assertOk()
        ->assertCountTableRecords(1)
        ->assertSee('جرد');
});

it('lists who registered interest in an auction', function (): void {
    $auction = Auction::factory()->create();
    $interest = $auction->interests()->create(['name' => 'ريم', 'phone' => '+966551234567']);

    Livewire::test(InterestsRelationManager::class, ['ownerRecord' => $auction, 'pageClass' => EditAuction::class])
        ->assertOk()
        ->assertCanSeeTableRecords([$interest])
        ->assertSee('0551234567');
});
