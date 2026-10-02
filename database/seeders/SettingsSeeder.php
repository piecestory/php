<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Settings\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /** Defaults only: existing values (changed from the admin panel) are never overwritten. */
    private const array DEFAULTS = [
        // Mobile-code sign-in stays off until an SMS provider is configured.
        ['sms', 'enabled', '0'],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as [$group, $key, $value]) {
            if (! Setting::query()->where('group', $group)->where('key', $key)->exists()) {
                Setting::put($group, $key, $value);
            }
        }
    }
}
