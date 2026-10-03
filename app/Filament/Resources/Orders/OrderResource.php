<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\RequiresPermission;
use App\Support\Money\Money;
use App\Support\Phone\SaudiMobile;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Orders are created only by customers at checkout and never edited or deleted here: staff move them
 * forward (prepare, ship, deliver), record showroom payments and cancel, each through a domain action.
 */
class OrderResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 1;

    /** Everything the order page shows, loaded in one go. */
    public const array DETAIL_RELATIONS = ['items', 'payments', 'shipments', 'statusChanges', 'pickupBranch', 'user'];

    /** @return Builder<Model> */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->with(self::DETAIL_RELATIONS);
    }

    protected static function permission(): Permission
    {
        return Permission::ManageOrders;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['create', 'update', 'delete', 'deleteAny', 'replicate'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.sales');
    }

    public static function getModelLabel(): string
    {
        return __('admin.orders.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.orders.plural');
    }

    /** Orders waiting for staff: paid and not yet prepared, or reservations about to lapse. */
    public static function getNavigationBadge(): ?string
    {
        $count = Order::query()->where('status', OrderStatus::Confirmed)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')->label(__('admin.orders.number'))->searchable()->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('customer_name')->label(__('admin.orders.customer'))
                    // Staff type mobiles as 05…; they are stored as +9665….
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                        ->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone', SaudiMobile::normalize($search) ?? $search)))
                    ->description(fn (Order $record) => SaudiMobile::local($record->phone)),
                TextColumn::make('type')->label(__('admin.orders.type'))->badge()->color('gray')
                    ->formatStateUsing(fn (OrderType $state) => EnumLabels::of($state)),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => EnumLabels::of($state))
                    ->color(fn (OrderStatus $state) => self::statusColor($state)),
                TextColumn::make('payment_status')->label(__('admin.orders.payment'))->badge()
                    ->formatStateUsing(fn (OrderPaymentStatus $state) => EnumLabels::of($state))
                    ->color(fn (OrderPaymentStatus $state) => match ($state) {
                        OrderPaymentStatus::Paid => 'success',
                        OrderPaymentStatus::DepositPaid => 'info',
                        OrderPaymentStatus::Failed => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('grand_total')->label(__('orders.page.total'))
                    ->formatStateUsing(fn (string $state) => Money::amount($state).' '.Money::currency())->sortable(),
                TextColumn::make('placed_at')->label(__('admin.fields.date'))->dateTime('j M Y، g:i a')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin.fields.status'))->options(EnumLabels::options(OrderStatus::class))->multiple(),
                SelectFilter::make('type')->label(__('admin.orders.type'))->options(EnumLabels::options(OrderType::class)),
                SelectFilter::make('payment_status')->label(__('admin.orders.payment'))->options(EnumLabels::options(OrderPaymentStatus::class)),
                Filter::make('needs_action')->label(__('admin.orders.needs_action'))
                    ->query(fn (Builder $query) => $query->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Processing])),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function statusColor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending, OrderStatus::Reserved => 'warning',
            OrderStatus::Confirmed, OrderStatus::Processing => 'info',
            OrderStatus::Shipped, OrderStatus::Delivered => 'success',
            OrderStatus::Cancelled, OrderStatus::Refunded => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    /** @return list<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'customer_name', 'phone'];
    }
}
