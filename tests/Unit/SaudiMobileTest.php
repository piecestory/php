<?php

declare(strict_types=1);

use App\Support\Phone\SaudiMobile;

it('normalizes every common way of writing a Saudi mobile number', function (string $input): void {
    expect(SaudiMobile::normalize($input))->toBe('+966512345678');
})->with([
    '0512345678', '512345678', '966512345678', '+966512345678', '00966512345678',
    '051 234 5678', '+966-51-234-5678', '٠٥١٢٣٤٥٦٧٨',
]);

it('rejects numbers that are not Saudi mobiles', function (?string $input): void {
    expect(SaudiMobile::normalize($input))->toBeNull();
})->with([null, '', '0112345678', '05123', '0512345678901', '+971512345678', 'abc']);

it('formats a stored number the way customers write it', function (): void {
    expect(SaudiMobile::local('+966512345678'))->toBe('0512345678');
});
