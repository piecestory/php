<?php

declare(strict_types=1);

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;

it('renders the layout right-to-left in Arabic and left-to-right in English', function (string $locale, string $dir): void {
    app()->setLocale($locale);

    $this->blade('<x-layouts.base>content</x-layouts.base>')
        ->assertSee('<html lang="'.$locale.'" dir="'.$dir.'">', escape: false);
})->with([['ar', 'rtl'], ['en', 'ltr']]);

it('shows the sale price with the original price struck through', function (): void {
    app()->setLocale('ar');

    $this->blade('<x-ui.price amount="3750.00" compare-at="4400.00" />')
        ->assertSeeInOrder(['3,750', 'ر.س', '<del', '4,400'], escape: false);
});

it('shows a single price when there is no discount', function (): void {
    app()->setLocale('en');

    $this->blade('<x-ui.price amount="1850.00" compare-at="1850.00" />')
        ->assertSee('1,850')->assertSee('SAR')->assertDontSee('<del', escape: false);
});

it('falls back to the brand placeholder when an image is missing', function (): void {
    $this->blade('<x-ui.image alt="Clock" />')
        ->assertSee('images/placeholder.svg')
        ->assertSee('data-fallback=', escape: false);
});

it('lazy-loads images unless marked eager', function (): void {
    $this->blade('<x-ui.image src="/a.jpg" alt="A" />')->assertSee('loading="lazy"', escape: false);
    $this->blade('<x-ui.image src="/a.jpg" alt="A" eager />')->assertSee('fetchpriority="high"', escape: false);
});

it('links form errors to their inputs for screen readers', function (): void {
    view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag(['email' => ['Invalid email']])));

    $this->blade('<x-form.input name="email" label="Email" required />')
        ->assertSee('aria-invalid="true"', escape: false)
        ->assertSee('aria-describedby="field-email-error"', escape: false)
        ->assertSee('id="field-email-error"', escape: false)
        ->assertSee('Invalid email');
});

it('mirrors directional icons in right-to-left layouts only', function (): void {
    $this->blade('<x-ui.icon name="arrow-right" />')->assertSee('rtl:-scale-x-100');
    $this->blade('<x-ui.icon name="heart" />')->assertDontSee('rtl:-scale-x-100');
});

it('refuses unknown or unsafe icon names', function (string $name): void {
    $this->blade('<x-ui.icon name="'.$name.'" />');
})->with(['does-not-exist', '../../.env'])->throws(ViewException::class);

it('draws the logo from its versioned file, decorative and with the file\'s proportions', function (string $variant, string $file): void {
    $html = (string) $this->blade('<x-logo variant="'.$variant.'" />');

    expect($html)->toContain('logo-mask')
        ->toContain('aria-hidden="true"')
        ->toMatch('#images/brand/'.$file.'\.svg\?v=[0-9a-f]{16}#')
        ->toMatch('#aspect-ratio: [\d.]+ / [\d.]+#')
        ->not->toContain('<svg');
})->with([['horizontal', 'horizontal-ar'], ['compact', 'compact-ar'], ['emblem', 'emblem']]);

it('does not expose the design reference outside local development', function (): void {
    $this->get('/_design')->assertNotFound();
});
