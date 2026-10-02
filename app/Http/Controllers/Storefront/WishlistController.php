<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Support\CurrentWishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private readonly CurrentWishlist $wishlist) {}

    public function show(): View
    {
        $ids = $this->wishlist->ids();
        $products = Product::query()->published()->whereIn('id', $ids)->with('media')->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $ids, true))
            ->values();

        return view('storefront.wishlist', ['products' => $products]);
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->isPublished(), 404);

        $saved = $this->wishlist->toggle($product);
        $message = __($saved ? 'wishlist.added' : 'wishlist.removed', ['name' => $product->translate('name')]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'saved' => $saved, 'count' => $this->wishlist->count(), 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
