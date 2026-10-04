<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Notifications\ResetPasswordLink;
use App\Domain\Orders\Models\Order;
use App\Domain\Wishlist\Models\WishlistItem;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SensitiveParameter;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'locale', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /** Queued, so a mail outage never turns "forgot password" into an error page. */
    public function sendPasswordResetNotification(#[SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordLink($token));
    }

    /** Staff panel: active accounts holding the admin-access permission (customers never). */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->can(Permission::AccessAdmin->value);
    }

    public function isStaff(): bool
    {
        return $this->roles()->exists();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Address, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<WishlistItem, $this> */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }
}
