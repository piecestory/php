<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;

// FULLTEXT indexes only see committed rows, so this suite commits (DatabaseTruncation) instead of rolling back.

it('finds products through the fulltext index with Arabic spelling variants', function (): void {
    Product::factory()->create(['name_ar' => 'نجفة كريستال']);
    Product::factory()->create(['name_ar' => 'كرسي كلاسيك']);

    $hits = Product::query()
        ->whereFullText('search_text', 'نجفه')
        ->pluck('name_ar');

    expect($hits->all())->toBe(['نجفة كريستال']);
});
