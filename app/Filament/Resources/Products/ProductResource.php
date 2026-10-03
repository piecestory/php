<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\InventoryMovementsRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'name_ar';

    /** Admin URLs use the id: storefront slugs can be edited here and must not move the admin page. */
    protected static ?string $recordRouteKeyName = 'id';

    protected static ?int $navigationSort = 1;

    protected static function permission(): Permission
    {
        return Permission::ManageCatalog;
    }

    /**
     * Pieces are archived (soft-deleted), never erased: order history and the stock ledger point to them.
     *
     * @return list<string>
     */
    protected static function forbiddenAbilities(): array
    {
        return ['forceDelete', 'forceDeleteAny'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('admin.products.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.products.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InventoryMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    /** @return Builder<Model> */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return list<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name_ar', 'name_en', 'sku'];
    }
}
