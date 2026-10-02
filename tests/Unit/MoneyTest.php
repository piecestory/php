<?php

declare(strict_types=1);

use App\Support\Money\Money;

it('formats amounts with thousands separators and drops empty fractions', function (string $amount, string $expected): void {
    expect(Money::amount($amount))->toBe($expected);
})->with([
    ['0', '0'],
    ['950.00', '950'],
    ['1850.00', '1,850'],
    ['1850.5', '1,850.50'],
    ['1234567.89', '1,234,567.89'],
    ['-2500', '-2,500'],
]);
