<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Guests track an order with its number + the mobile number used at checkout.
 * POST keeps the phone number out of URLs and logs; the error never reveals which part was wrong.
 */
class TrackOrderController extends Controller
{
    public function show(): View
    {
        return view('storefront.track-order');
    }

    public function lookup(Request $request): View
    {
        $request->validate([
            'number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', new SaudiMobileNumber],
        ]);

        $order = Order::query()
            ->where('number', mb_strtoupper(trim($request->string('number')->toString())))
            ->where('phone', SaudiMobile::normalize($request->string('phone')->toString()))
            ->with(['items', 'shipments', 'statusChanges'])
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages(['number' => __('orders.track.not_found')]);
        }

        return view('storefront.track-order', ['order' => $order]);
    }
}
