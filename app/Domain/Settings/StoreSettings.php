<?php

declare(strict_types=1);

namespace App\Domain\Settings;

use App\Domain\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Read access to admin-editable settings ("group.key"), loaded once and cached until a setting changes.
 */
final class StoreSettings
{
    private const string CACHE_KEY = 'store-settings';

    /** @var array<string, ?string>|null */
    private ?array $values = null;

    public function get(string $key, ?string $default = null): ?string
    {
        $this->values ??= Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->get()
            ->mapWithKeys(fn (Setting $setting) => ["{$setting->group}.{$setting->key}" => $setting->value])
            ->all());

        return $this->values[$key] ?? $default;
    }

    public function enabled(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN);
    }

    public function flush(): void
    {
        $this->values = null;
        Cache::forget(self::CACHE_KEY);
    }
}
