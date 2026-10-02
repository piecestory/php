<?php

declare(strict_types=1);

namespace App\Support\Orders;

use App\Domain\Orders\Models\Order;

/** The customer's private link to their order page (sent by email and SMS), in their language. */
final class OrderLink
{
    public static function page(Order $order, ?string $locale = null): string
    {
        return localized_route('order', ['order' => $order->number, 'key' => $order->access_token], $locale ?? $order->locale);
    }
}
