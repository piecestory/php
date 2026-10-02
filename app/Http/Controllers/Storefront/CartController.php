<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Cart\Actions\AddToCart;
use App\Domain\Cart\Actions\UpdateCartItem;
use App\Domain\Cart\Exceptions\CartException;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Support\CurrentCart;
use App\Support\Money\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Cart page and cart changes. Forms work without JavaScript; with it they are sent in the background (JSON). */
class CartController extends Controller
{
    public function __construct(private readonly CurrentCart $cart) {}

    public function show(): View
    {
        return view('storefront.cart', ['summary' => $this->cart->summary()]);
    }

    public function add(Request $request, Product $product, AddToCart $add): JsonResponse|RedirectResponse
    {
        try {
            $add->handle($this->cart->getOrCreate(...), $product, $request->integer('quantity', 1));
        } catch (CartException $e) {
            return $this->respond($request, __($e->translationKey()), success: false);
        }

        return $this->respond($request, __('cart.added', ['name' => $product->translate('name')]));
    }

    public function update(Request $request, Product $product, UpdateCartItem $update): JsonResponse|RedirectResponse
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);
        $cart = $this->cart->get();

        if ($cart !== null) {
            try {
                $update->handle($cart, $product, $request->integer('quantity'));
            } catch (CartException $e) {
                return $this->respond($request, __($e->translationKey()), success: false);
            }
        }

        return $this->respond($request, __('cart.updated'));
    }

    public function remove(Request $request, Product $product, UpdateCartItem $update): JsonResponse|RedirectResponse
    {
        if (($cart = $this->cart->get()) !== null) {
            $update->handle($cart, $product, 0);
        }

        return $this->respond($request, __('cart.removed'));
    }

    private function respond(Request $request, string $message, bool $success = true): JsonResponse|RedirectResponse
    {
        $this->cart->refresh();
        $summary = $this->cart->summary();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $success,
                'message' => $message,
                'cart' => ['count' => $summary->count, 'total' => Money::amount($summary->total).' '.Money::currency()],
            ], $success ? 200 : 422);
        }

        return back()->with($success ? 'status' : 'error', $message);
    }
}
