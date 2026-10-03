<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\RefundStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Refund;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\Staff;
use App\Support\Money\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** The numbers the team checks first thing: today's orders, money in this month, work waiting. */
class SalesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return Staff::can(Permission::ManageOrders);
    }

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();

        // Money actually received this month (captured payments), less money returned this month.
        $collected = bcsub(
            (string) Payment::query()->whereIn('status', [PaymentStatus::Captured, PaymentStatus::Refunded])->where('paid_at', '>=', $monthStart)->sum('amount'),
            (string) Refund::query()->where('status', RefundStatus::Completed)->where('created_at', '>=', $monthStart)->sum('amount'),
            2,
        );

        $toPrepare = Order::query()->where('status', OrderStatus::Confirmed)->count();
        $expiring = Order::query()->where('status', OrderStatus::Reserved)
            ->whereBetween('hold_expires_at', [now(), now()->addHours(48)])->count();

        return [
            Stat::make(__('admin.dashboard.orders_today'), (string) Order::query()->whereDate('placed_at', today())->where('status', '!=', OrderStatus::Cancelled)->count()),
            Stat::make(__('admin.dashboard.collected_month'), Money::amount($collected).' '.Money::currency())
                ->description(__('admin.dashboard.collected_hint')),
            Stat::make(__('admin.dashboard.to_prepare'), (string) $toPrepare)
                ->color($toPrepare > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index', ['filters' => ['status' => ['values' => [OrderStatus::Confirmed->value]]]])),
            Stat::make(__('admin.dashboard.reservations_expiring'), (string) $expiring)
                ->description(__('admin.dashboard.reservations_expiring_hint'))
                ->color($expiring > 0 ? 'danger' : 'gray'),
        ];
    }
}
