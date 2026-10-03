<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Actions\StockLedger;
use App\Domain\Inventory\Enums\InventoryReason;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\Staff;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** The opening quantity is recorded in the stock ledger like any other change. */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Product {
            $quantity = (int) ($data['stock_quantity'] ?? 0);

            /** @var Product $product */
            $product = parent::handleRecordCreation([...$data, 'stock_quantity' => 0]);
            app(StockLedger::class)->setQuantity($product, $quantity, InventoryReason::InitialStock, Staff::id(), __('admin.stock.initial_note'));

            return $product;
        });
    }
}
