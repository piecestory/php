<?php

declare(strict_types=1);

namespace App\Http\Support;

use App\Domain\Orders\Models\Order;
use Illuminate\Http\Request;

/**
 * Who may open an order page: the signed-in owner, or anyone holding the order's private key
 * (sent to the customer by SMS / email and given after a successful order lookup).
 * Anything else gets a 404, so order numbers cannot be probed.
 */
final class OrderAccess
{
    public static function ensure(Request $request, Order $order): void
    {
        $key = $request->input('key');
        $ownsOrder = $order->user_id !== null && $request->user()?->getAuthIdentifier() === $order->user_id;
        $hasKey = is_string($key) && $order->access_token !== null && hash_equals($order->access_token, $key);

        abort_unless($ownsOrder || $hasKey, 404);
    }
}
