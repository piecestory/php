<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\Models\Product;
use App\Filament\Resources\Products\Actions\AdjustStockAction;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(__('admin.actions.view_on_site'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (Product $record) => localized_route('product', $record), shouldOpenInNewTab: true)
                ->visible(fn (Product $record) => $record->isPublished() && ! $record->trashed()),
            AdjustStockAction::make()->after(fn () => $this->refreshFormData(['stock_quantity'])),
            DeleteAction::make()->label(__('admin.actions.archive')),
            RestoreAction::make(),
        ];
    }
}
