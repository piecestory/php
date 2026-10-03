<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Domain\Inventory\Enums\InventoryReason;
use App\Filament\Support\EnumLabels;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** The product's stock ledger, newest first. Read-only: stock changes go through "Adjust stock" or orders. */
class InventoryMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryMovements';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.stock.movements');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->emptyStateHeading(__('admin.stock.no_movements'))
            ->columns([
                TextColumn::make('created_at')->label(__('admin.fields.date'))->dateTime('j M Y، g:i a'),
                TextColumn::make('reason')->label(__('admin.stock.reason'))->badge()
                    ->formatStateUsing(fn (InventoryReason $state) => EnumLabels::of($state)),
                TextColumn::make('quantity_change')->label(__('admin.stock.change'))
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('stock_after')->label(__('admin.stock.after')),
                TextColumn::make('note')->label(__('admin.stock.note'))->wrap(),
                TextColumn::make('user.name')->label(__('admin.stock.by'))->placeholder(__('admin.stock.system')),
            ]);
    }
}
