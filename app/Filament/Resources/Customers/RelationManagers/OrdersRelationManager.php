<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\EnumLabels;
use App\Support\Money\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** The customer's orders; each opens in the orders section (for staff allowed to see orders). */
class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.orders.plural');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->emptyStateHeading(__('account.orders.empty'))
            ->recordUrl(fn (Order $record) => OrderResource::canView($record) ? OrderResource::getUrl('view', ['record' => $record]) : null)
            ->columns([
                TextColumn::make('number')->label(__('admin.orders.number')),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => EnumLabels::of($state))
                    ->color(fn (OrderStatus $state) => OrderResource::statusColor($state)),
                TextColumn::make('grand_total')->label(__('orders.page.total'))->formatStateUsing(fn (string $state) => Money::amount($state).' '.Money::currency()),
                TextColumn::make('placed_at')->label(__('admin.fields.date'))->date('j M Y'),
            ]);
    }
}
