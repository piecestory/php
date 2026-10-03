<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderNotice;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Support\Facades\DB;

/**
 * Staff moves a paid order along: preparing → shipped (with the courier's tracking number, and the
 * customer is told) → delivered. Showroom pickups go from preparing straight to delivered.
 */
final class FulfilOrder
{
    public function __construct(
        private readonly TransitionOrder $transition,
        private readonly NotifyCustomer $notify,
    ) {}

    public function startPreparing(Order $order, int $staffId): void
    {
        $this->transition->handle($order, OrderStatus::Processing, $staffId);
    }

    public function ship(Order $order, string $carrier, ?string $trackingNumber, ?string $trackingUrl, int $staffId): Shipment
    {
        $shipment = DB::transaction(function () use ($order, $carrier, $trackingNumber, $trackingUrl, $staffId): Shipment {
            $shipment = $order->shipments()->create([
                'carrier' => $carrier,
                'tracking_number' => $trackingNumber,
                'tracking_url' => $trackingUrl,
                'shipped_at' => now(),
            ]);
            $this->transition->handle($order, OrderStatus::Shipped, $staffId);

            return $shipment;
        });

        $this->notify->handle($order, OrderNotice::Shipped);

        return $shipment;
    }

    public function markDelivered(Order $order, int $staffId): void
    {
        DB::transaction(function () use ($order, $staffId): void {
            $order->shipments()->whereNull('delivered_at')->update(['delivered_at' => now()]);
            $this->transition->handle($order, OrderStatus::Delivered, $staffId);
        });
    }
}
