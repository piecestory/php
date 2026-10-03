<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\Staff;
use App\Support\Money\Money;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Staff::can(Permission::ManageOrders);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.dashboard.latest_orders'))
            ->query(Order::query()->latest('id')->limit(8))
            ->paginated(false)
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label(__('admin.orders.number')),
                TextColumn::make('customer_name')->label(__('admin.orders.customer')),
                TextColumn::make('type')->label(__('admin.orders.type'))->formatStateUsing(fn (OrderType $state) => EnumLabels::of($state)),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => EnumLabels::of($state))
                    ->color(fn (OrderStatus $state) => OrderResource::statusColor($state)),
                TextColumn::make('grand_total')->label(__('orders.page.total'))->formatStateUsing(fn (string $state) => Money::amount($state).' '.Money::currency()),
                TextColumn::make('placed_at')->label(__('admin.fields.date'))->since(),
            ]);
    }
}
