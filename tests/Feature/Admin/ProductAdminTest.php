<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Inventory\Enums\InventoryReason;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->staff = User::factory()->create()->assignRole(Role::StoreManager->value);
    $this->actingAs($this->staff);
});

it('lists pieces with their stock and status', function (): void {
    $product = Product::factory()->published()->create(['name_ar' => 'ساعة جدارية']);

    $this->get('/admin/products')->assertOk();
    // Admin pages are addressed by id, so editing the public slug never moves them.
    $this->get("/admin/products/{$product->id}/edit")->assertOk()->assertSee('ساعة جدارية');
    Livewire::test(ListProducts::class)->assertCanSeeTableRecords([$product])->assertSee('ساعة جدارية');
});

it('creates a piece and records its opening stock in the ledger', function (): void {
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name_ar' => 'مزهرية صينية',
            'name_en' => 'Chinese vase',
            'sku' => 'PS-9001',
            'category_id' => $category->id,
            'price' => '4500',
            'stock_quantity' => 2,
            'availability' => 'available',
            'status' => 'published',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('sku', 'PS-9001')->sole();
    expect($product->slug_ar)->toBe('مزهرية-صينية')
        ->and($product->slug_en)->toBe('chinese-vase')
        ->and($product->stock_quantity)->toBe(2)
        ->and($product->published_at)->not->toBeNull()
        ->and($product->inventoryMovements()->sole()->reason)->toBe(InventoryReason::InitialStock)
        ->and($product->inventoryMovements()->sole()->user_id)->toBe($this->staff->id);
});

it('rejects a sale price that is not below the price', function (): void {
    $product = Product::factory()->create(['price' => 1000]);

    Livewire::test(EditProduct::class, ['record' => $product->id])
        ->fillForm(['sale_price' => '1200'])
        ->call('save')
        ->assertHasFormErrors(['sale_price']);
});

it('changes stock only through the logged adjustment', function (): void {
    $product = Product::factory()->create(['stock_quantity' => 1]);

    Livewire::test(EditProduct::class, ['record' => $product->id])
        ->fillForm(['stock_quantity' => 50])
        ->call('save');
    expect($product->refresh()->stock_quantity)->toBe(1);

    Livewire::test(EditProduct::class, ['record' => $product->id])
        ->callAction('adjustStock', ['quantity' => 3, 'reason' => 'adjustment', 'note' => 'جرد المستودع'])
        ->assertHasNoActionErrors();

    $movement = $product->inventoryMovements()->sole();
    expect($product->refresh()->stock_quantity)->toBe(3)
        ->and($movement->quantity_change)->toBe(2)
        ->and($movement->note)->toBe('جرد المستودع');
});
