<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Enums\PublicationStatus;
use App\Filament\Resources\Products\Actions\AdjustStockAction;
use App\Filament\Support\EnumLabels;
use App\Support\Money\Money;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('gallery')->label('')
                    ->collection(Product::MEDIA_GALLERY)->conversion('thumb')->limit(1)->square()->imageSize(56),
                TextColumn::make('name_ar')->label(__('admin.fields.name'))->searchable(['name_ar', 'name_en', 'sku'])
                    ->description(fn (Product $record) => $record->sku)->wrap(),
                TextColumn::make('category.name_ar')->label(__('admin.products.category'))->toggleable(),
                TextColumn::make('price')->label(__('admin.products.price'))
                    ->formatStateUsing(fn (Product $record) => self::price($record))->sortable(),
                TextColumn::make('stock_quantity')->label(__('admin.products.stock'))->numeric()->sortable()
                    ->color(fn (int $state) => $state === 0 ? 'danger' : null),
                TextColumn::make('availability')->label(__('admin.products.availability'))->badge()
                    ->formatStateUsing(fn (ProductAvailability $state) => EnumLabels::of($state))
                    ->color(fn (ProductAvailability $state) => match ($state) {
                        ProductAvailability::Available => 'success',
                        ProductAvailability::Sold => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (PublicationStatus $state) => EnumLabels::of($state))
                    ->color(fn (PublicationStatus $state) => $state === PublicationStatus::Published ? 'success' : 'gray'),
                IconColumn::make('is_featured')->label(__('admin.products.is_featured'))->boolean()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label(__('admin.fields.updated_at'))->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->label(__('admin.products.category'))->relationship('category', 'name_ar')->preload(),
                SelectFilter::make('status')->label(__('admin.fields.status'))->options(EnumLabels::options(PublicationStatus::class)),
                SelectFilter::make('availability')->label(__('admin.products.availability'))->options(EnumLabels::options(ProductAvailability::class)),
                TernaryFilter::make('in_stock')->label(__('admin.products.in_stock'))
                    ->queries(
                        true: fn ($query) => $query->where('stock_quantity', '>', 0),
                        false: fn ($query) => $query->where('stock_quantity', 0),
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                AdjustStockAction::make(),
            ]);
    }

    private static function price(Product $product): string
    {
        $price = Money::amount($product->effectivePrice()).' '.Money::currency();

        return $product->isOnSale() ? $price.' ('.__('admin.products.on_sale').')' : $price;
    }
}
