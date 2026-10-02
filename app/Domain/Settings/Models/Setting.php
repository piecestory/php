<?php

declare(strict_types=1);

namespace App\Domain\Settings\Models;

use App\Domain\Settings\StoreSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Admin-editable key/value settings. Secrets (e.g. SMS provider keys) are stored encrypted
 * with the application key. Write through put() so encryption never depends on attribute order.
 */
#[Fillable(['group', 'key'])]
#[Hidden(['value'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }

    protected static function booted(): void
    {
        $flush = fn () => app(StoreSettings::class)->flush();

        static::saved($flush);
        static::deleted($flush);
    }

    public static function put(string $group, string $key, ?string $value, bool $encrypt = false): self
    {
        $setting = self::query()->firstOrNew(['group' => $group, 'key' => $key]);
        $setting->is_encrypted = $encrypt;
        $setting->attributes['value'] = $value !== null && $encrypt ? Crypt::encryptString($value) : $value;
        $setting->save();

        return $setting;
    }

    /** @return Attribute<?string, never> */
    protected function value(): Attribute
    {
        return Attribute::get(
            fn (?string $value, array $attributes) => $value !== null && ($attributes['is_encrypted'] ?? false)
                ? Crypt::decryptString($value)
                : $value,
        );
    }
}
