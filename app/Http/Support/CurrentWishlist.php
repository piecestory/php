<?php

declare(strict_types=1);

namespace App\Http\Support;

use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Models\User;
use App\Domain\Wishlist\Models\WishlistItem;
use Illuminate\Http\Request;

/**
 * The visitor's saved pieces: in the database for members, in the session for guests
 * (moved to the account at sign-in).
 */
final class CurrentWishlist
{
    private const string SESSION_KEY = 'wishlist';

    /** @var list<int>|null */
    private ?array $ids = null;

    public function __construct(private readonly Request $request) {}

    /** @return list<int> product ids, most recently saved first */
    public function ids(): array
    {
        if ($this->ids !== null) {
            return $this->ids;
        }

        $user = $this->user();

        return $this->ids = $user !== null
            ? WishlistItem::query()->where('user_id', $user->id)->latest('id')->pluck('product_id')->map(fn ($id) => (int) $id)->all()
            : array_values(array_map('intval', (array) $this->request->session()->get(self::SESSION_KEY, [])));
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    /** @return bool whether the piece is saved after the toggle */
    public function toggle(Product $product): bool
    {
        $saved = ! $this->has($product->id);
        $user = $this->user();

        if ($user !== null) {
            $saved
                ? WishlistItem::query()->firstOrCreate(['user_id' => $user->id, 'product_id' => $product->id])
                : WishlistItem::query()->where('user_id', $user->id)->where('product_id', $product->id)->delete();
        } else {
            $ids = array_values(array_diff($this->ids(), [$product->id]));
            $this->request->session()->put(self::SESSION_KEY, $saved ? [$product->id, ...$ids] : $ids);
        }

        $this->ids = null;

        return $saved;
    }

    /** At sign-in: keep the pieces saved as a guest. */
    public function mergeGuestListInto(User $user): void
    {
        $ids = array_map('intval', (array) $this->request->session()->pull(self::SESSION_KEY, []));
        $existing = Product::query()->whereIn('id', $ids)->pluck('id');

        foreach ($existing as $productId) {
            WishlistItem::query()->firstOrCreate(['user_id' => $user->id, 'product_id' => $productId]);
        }

        $this->ids = null;
    }

    private function user(): ?User
    {
        $user = $this->request->user();

        return $user instanceof User ? $user : null;
    }
}
