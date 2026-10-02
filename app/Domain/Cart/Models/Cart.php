<?php

declare(strict_types=1);

namespace App\Domain\Cart\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A member has one cart; a guest cart is identified by a random token kept in a cookie.
 * Guest carts expire after a period of inactivity and are pruned by the scheduler.
 */
#[Fillable(['user_id', 'token', 'expires_at'])]
#[Hidden(['token'])]
class Cart extends Model
{
    use Prunable;

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public static function newGuestCart(): self
    {
        return self::query()->create([
            'token' => Str::random(40),
            'expires_at' => now()->addDays((int) config('store.guest_cart_days')),
        ]);
    }

    /** Keeps an active guest cart alive. */
    public function touchActivity(): void
    {
        if ($this->user_id === null) {
            $this->forceFill(['expires_at' => now()->addDays((int) config('store.guest_cart_days'))])->save();
        }
    }

    /** @return Builder<self> */
    public function prunable(): Builder
    {
        return self::query()->whereNull('user_id')->where('expires_at', '<', now());
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CartItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
