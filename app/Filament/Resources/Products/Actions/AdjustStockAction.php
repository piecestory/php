<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Actions;

use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Inventory\Actions\StockLedger;
use App\Domain\Inventory\Enums\InventoryReason;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\Staff;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/** Sets the quantity available for sale; every change is written to the stock movement log with its reason. */
final class AdjustStockAction
{
    public static function make(): Action
    {
        return Action::make('adjustStock')
            ->label(__('admin.stock.adjust'))
            ->icon(Heroicon::OutlinedArchiveBox)
            ->visible(fn () => Staff::can(Permission::ManageInventory))
            ->modalDescription(__('admin.stock.adjust_hint'))
            ->fillForm(fn (Product $record) => ['quantity' => $record->stock_quantity, 'reason' => InventoryReason::Adjustment->value])
            ->schema([
                TextInput::make('quantity')->label(__('admin.stock.quantity'))->integer()->minValue(0)->maxValue(999)->required(),
                Select::make('reason')->label(__('admin.stock.reason'))->required()
                    ->options(EnumLabels::options(InventoryReason::class, [InventoryReason::Adjustment, InventoryReason::Return, InventoryReason::InitialStock])),
                Textarea::make('note')->label(__('admin.stock.note'))->required()->maxLength(500)->rows(2),
            ])
            ->action(function (Product $record, array $data): void {
                app(StockLedger::class)->setQuantity(
                    $record,
                    (int) $data['quantity'],
                    InventoryReason::from((string) $data['reason']),
                    Staff::id(),
                    (string) $data['note'],
                );
            })
            ->successNotificationTitle(__('admin.stock.saved'));
    }
}
