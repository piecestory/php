<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Domain\Consignment\Models\ConsignmentRequest;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\PersonalFinder\Models\FinderRequest;
use App\Http\Controllers\Controller;
use App\Http\Support\CurrentWishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use LogicException;

/** "My account": an overview and the order history. Everything is read through the signed-in customer, never by id. */
class AccountController extends Controller
{
    private const int RECENT_ORDERS = 3;

    private const int ORDERS_PER_PAGE = 10;

    public function overview(Request $request, CurrentWishlist $wishlist): View
    {
        $user = self::customer($request);

        return view('account.overview', [
            'user' => $user,
            'awaitingPayment' => $user->orders()
                ->whereIn('status', [OrderStatus::Pending, OrderStatus::Reserved])
                ->where('hold_expires_at', '>', now())
                ->latest('id')
                ->get(),
            'recentOrders' => $user->orders()->withCount('items')->latest('id')->limit(self::RECENT_ORDERS)->get(),
            'defaultAddress' => $user->addresses()->where('is_default', true)->first(),
            'wishlistCount' => $wishlist->count(),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('account.orders', [
            'orders' => self::customer($request)->orders()->withCount('items')->latest('id')->paginate(self::ORDERS_PER_PAGE),
        ]);
    }

    /** Personal Finder and "sell with us" requests sent while signed in. */
    public function requests(Request $request): View
    {
        $user = self::customer($request);

        return view('account.requests', [
            'finderRequests' => FinderRequest::query()->where('user_id', $user->id)->latest('id')->get(),
            'consignments' => ConsignmentRequest::query()->where('user_id', $user->id)->latest('id')->get(),
        ]);
    }

    /** The signed-in customer (these routes sit behind the `auth` middleware). */
    public static function customer(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new LogicException('Account pages require a signed-in customer.');
    }
}
