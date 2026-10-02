<?php

declare(strict_types=1);

use App\Support\Search\ArabicNormalizer;

it('unifies hamza forms, taa marbuta and alef maqsura', function (string $input, string $expected): void {
    expect(ArabicNormalizer::normalize($input))->toBe($expected);
})->with([
    'hamza on alef' => ['أثاث', 'اثاث'],
    'hamza below alef' => ['إضاءة', 'اضاءه'],
    'madda' => ['آنية', 'انيه'],
    'taa marbuta' => ['قطعة', 'قطعه'],
    'alef maqsura' => ['مستشفى', 'مستشفي'],
]);

it('removes diacritics and tatweel', function (): void {
    expect(ArabicNormalizer::normalize('قِطْعَـــة'))->toBe('قطعه');
});

it('lowercases latin text and collapses punctuation into single spaces', function (): void {
    expect(ArabicNormalizer::normalize('  French CLOCK — 19th-century! '))->toBe('french clock 19th century');
});

it('strips html tags', function (): void {
    expect(ArabicNormalizer::normalize('<p>ساعة <b>فرنسية</b></p>'))->toBe('ساعه فرنسيه');
});

it('returns an empty string for null or empty input', function (): void {
    expect(ArabicNormalizer::normalize(null))->toBe('')
        ->and(ArabicNormalizer::normalize(''))->toBe('');
});
