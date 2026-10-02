<?php

declare(strict_types=1);

namespace App\Http\Support;

use App\Domain\Cart\Actions\MergeCarts;
use App\Domain\Cart\Data\CartSummary;
use App\Domain\Cart\Models\Cart;
use App\Domain\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * The visitor's cart for this request: the member's cart, or a guest cart identified by an
 * (encrypted, HTTP-only) cookie. A guest cart is only created when something is first added.
 */
final class CurrentCart
{
    public const string COOKIE = 'ps_cart';

    private ?Cart $cart = null;

    private ?CartSummary $summary = null;

    public function __construct(private readonly Request $request) {}

    public function get(): ?Cart
    {
        if ($this->cart !== null) {
            return $this->cart;
        }

        $user = $this->request->user();
        if ($user instanceof User) {
            return $this->cart = Cart::query()->where('user_id', $user->id)->first();
        }

        $token = $this->request->cookie(self::COOKIE);

        return $this->cart = is_string($token)
            ? Cart::query()->whereNull('user_id')->where('token', $token)->where('expires_at', '>', now())->first()
            : null;
    }

    public function getOrCreate(): Cart
    {
        if (($cart = $this->get()) !== null) {
            return $cart;
        }

        $user = $this->request->user();
        if ($user instanceof User) {
            return $this->cart = Cart::query()->firstOrCreate(['user_id' => $user->id], ['token' => bin2hex(random_bytes(20))]);
        }

        $this->cart = Cart::newGuestCart();
        Cookie::queue(self::COOKIE, $this->cart->token, (int) config('store.guest_cart_days') * 24 * 60);

        return $this->cart;
    }

    public function summary(): CartSummary
    {
        return $this->summary ??= CartSummary::for($this->get());
    }

    /** Call after any change so the next summary() reflects it. */
    public function refresh(): void
    {
        $this->summary = null;
        $this->cart?->refresh();
    }

    /** At sign-in: fold the guest cart from the cookie into the member's cart and drop the cookie. */
    public function mergeGuestCartInto(User $user, MergeCarts $merge): void
    {
        $token = $this->request->cookie(self::COOKIE);
        if (! is_string($token)) {
            return;
        }

        $guest = Cart::query()->whereNull('user_id')->where('token', $token)->first();
        if ($guest !== null && $guest->items()->exists()) {
            $member = Cart::query()->firstOrCreate(['user_id' => $user->id], ['token' => bin2hex(random_bytes(20))]);
            $merge->handle($guest, $member);
        } else {
            $guest?->delete();
        }

        Cookie::queue(Cookie::forget(self::COOKIE));
        $this->cart = null;
        $this->summary = null;
    }
}
