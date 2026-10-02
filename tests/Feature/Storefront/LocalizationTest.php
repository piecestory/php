<?php

declare(strict_types=1);

it('serves Arabic right-to-left at the root and English left-to-right under /en', function (): void {
    $this->get('/')->assertOk()->assertSee('<html lang="ar" dir="rtl">', escape: false)->assertSee('قصة الماضي وجمال الحاضر');
    $this->get('/en')->assertOk()->assertSee('<html lang="en" dir="ltr">', escape: false)->assertSee('The story of the past');
});

it('links each page to its counterpart in the other language', function (): void {
    $this->get('/faq')
        ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en/faq').'">', escape: false)
        ->assertSee('<link rel="alternate" hreflang="ar" href="'.url('/faq').'">', escape: false)
        ->assertSee('href="'.url('/en/faq').'" hreflang="en"', escape: false);

    $this->get('/en/faq')->assertSee('href="'.url('/faq').'" hreflang="ar"', escape: false);
});

it('declares a canonical URL without query strings', function (): void {
    $this->get('/faq?utm_source=x')->assertSee('<link rel="canonical" href="'.url('/faq').'">', escape: false);
});

it('only lists pages that exist in the navigation', function (): void {
    $this->get('/')
        ->assertSee(url('/faq'))
        ->assertSee(url('/track-order'))
        ->assertSee(url('/store'))
        ->assertDontSee(url('/auctions'));
});
