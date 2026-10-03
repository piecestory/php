<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Filament\Support\InitialsAvatar;
use App\Providers\AppServiceProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function (): void {
    $this->seed([SettingsSeeder::class, RolesAndPermissionsSeeder::class]);
});

it('sends the browser protections on storefront pages', function (string $path): void {
    $this->get($path)->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeader('Permissions-Policy');
})->with(['/', '/en', '/store', '/login', '/robots.txt']);

it('lets only this site run scripts on the storefront, with no inline code or eval', function (): void {
    $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->toContain("script-src 'self';")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("base-uri 'self'")
        ->not->toContain('unsafe-eval')
        ->not->toMatch("/script-src[^;]*'unsafe-inline'/");
});

it('gives the staff panel its own policy, still limited to this site', function (): void {
    $staff = User::factory()->create()->assignRole(Role::Admin->value);

    $csp = $this->actingAs($staff)->get('/admin')->assertOk()->headers->get('Content-Security-Policy');

    expect($csp)->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval'")
        ->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'none'")
        ->not->toContain('ui-avatars.com');
});

it('repeats the policy inside every page, so a host that overwrites the header cannot remove it', function (): void {
    $storefront = $this->get('/en/contact')->assertOk()->getContent();
    $notFound = $this->get('/no-such-page-anywhere')->assertNotFound()->getContent();
    $staff = $this->actingAs(User::factory()->create()->assignRole(Role::Admin->value))->get('/admin')->assertOk()->getContent();

    $meta = '#<meta http-equiv="Content-Security-Policy" content="([^"]+)">#';
    expect($storefront)->toMatch($meta)->and($notFound)->toMatch($meta)->and($staff)->toMatch($meta);

    preg_match($meta, $storefront, $page);
    preg_match($meta, $staff, $panel);
    expect(html_entity_decode($page[1]))->toContain("script-src 'self';")->not->toContain('unsafe-eval')->not->toContain('frame-ancestors')
        ->and(html_entity_decode($panel[1]))->toContain("'unsafe-eval'");
});

it('draws staff avatars locally instead of sending names to an outside service', function (): void {
    $staff = User::factory()->create(['name' => 'منى التميمي'])->assignRole(Role::Admin->value);

    $avatar = (new InitialsAvatar)->get($staff);

    expect($avatar)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr($avatar, strlen('data:image/svg+xml;base64,'))))->toContain('ما');
    $this->actingAs($staff)->get('/admin')->assertOk()->assertDontSee('ui-avatars.com');
});

it('lets checkout lead only to this site and the payment pages configured for it', function (): void {
    config(['payments.checkout_origins' => ['https://pay.example.test']]);

    expect($this->get('/')->headers->get('Content-Security-Policy'))->toContain("form-action 'self' https://pay.example.test;");
});

it('adds HTTPS-only rules in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('https://localhost/en/contact')->assertOk();

    expect($response->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000')
        ->and($response->headers->get('Content-Security-Policy'))->toContain('upgrade-insecure-requests');
});

it('never shows debug details or sends cookies over plain HTTP in production, whatever the settings say', function (): void {
    config(['app.debug' => true, 'session.secure' => false]);
    app()->detectEnvironment(fn () => 'production');

    (new AppServiceProvider(app()))->boot();

    expect(config('app.debug'))->toBeFalse()
        ->and(config('session.secure'))->toBeTrue();
});

it('hides internal error details from visitors', function (): void {
    config(['app.debug' => false]);

    $this->get('/store/no-such-category-anywhere')->assertNotFound()
        ->assertDontSee('vendor/laravel')
        ->assertDontSee('Stack trace');
});
