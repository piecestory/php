<?php

declare(strict_types=1);

use App\Domain\Settings\Models\Setting;
use Illuminate\Support\Facades\DB;

it('stores secrets encrypted and reads them back decrypted', function (): void {
    Setting::put('sms', 'api_key', 'super-secret-key', encrypt: true);

    $raw = DB::table('settings')->where('key', 'api_key')->value('value');

    expect($raw)->not->toBe('super-secret-key')->not->toContain('super-secret')
        ->and(Setting::query()->where('key', 'api_key')->sole()->value)->toBe('super-secret-key');
});

it('stores plain settings as-is and updates in place', function (): void {
    Setting::put('store', 'opens_at', '12:00');
    Setting::put('store', 'opens_at', '13:00');

    expect(Setting::query()->where('group', 'store')->count())->toBe(1)
        ->and(Setting::query()->where('key', 'opens_at')->sole()->value)->toBe('13:00');
});

it('never exposes setting values when serialized', function (): void {
    expect(Setting::put('sms', 'api_key', 'x', encrypt: true)->toArray())->not->toHaveKey('value');
});
