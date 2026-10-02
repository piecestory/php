<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('shows a branded Arabic 404 page', function (): void {
    $this->get('/no-such-page')->assertNotFound()->assertSee('الصفحة غير موجودة')->assertSee('dir="rtl"', escape: false);
});

it('shows the English 404 page under /en', function (): void {
    $this->get('/en/no-such-page')->assertNotFound()->assertSee('Page not found')->assertSee('dir="ltr"', escape: false);
});

it('hides internal details on server errors', function (): void {
    config(['app.debug' => false]);
    Route::get('/boom-test', fn () => throw new RuntimeException('SQLSTATE secret path C:\\internal'));

    $this->get('/boom-test')
        ->assertStatus(500)
        ->assertSee('حدث خطأ غير متوقع')
        ->assertDontSee('SQLSTATE')
        ->assertDontSee('internal');
});

it('uses friendly pages for expired forms and rate limits', function (int $status, string $text): void {
    Route::get('/status-test', fn () => abort($status));

    $this->get('/status-test')->assertStatus($status)->assertSee($text);
})->with([
    [403, 'غير مصرح'],
    [419, 'انتهت صلاحية الصفحة'],
    [429, 'طلبات كثيرة'],
    [503, 'نعود قريبًا'],
]);
