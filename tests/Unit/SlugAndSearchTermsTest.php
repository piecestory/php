<?php

declare(strict_types=1);

use App\Support\Localization\Slug;
use App\Support\Search\ArabicNormalizer;

it('makes URL slugs that keep Arabic letters', function (string $input, string $expected): void {
    expect(Slug::make($input))->toBe($expected);
})->with([
    ['ساعة رف فرنسية', 'ساعة-رف-فرنسية'],
    ['  Gilded French Clock! ', 'gilded-french-clock'],
    ['نجف وإضاءة / Lighting', 'نجف-وإضاءة-lighting'],
    ['قِطْعَـــة DEMO-001', 'قطعة-demo-001'],
]);

it('indexes words with and without their Arabic prefix', function (): void {
    $index = explode(' ', ArabicNormalizer::forIndex('الساعة الذهبية بالذهب'));

    expect($index)->toContain('الساعه', 'ساعه', 'الذهبيه', 'ذهبيه', 'بالذهب', 'ذهب');
});

it('keeps short words intact when stripping prefixes', function (): void {
    expect(ArabicNormalizer::queryTerms('الف'))->toBe(['الف']);
});

it('turns a query into unique normalized terms', function (): void {
    expect(ArabicNormalizer::queryTerms('  الساعة ساعة  Clock '))->toBe(['ساعه', 'clock']);
});
