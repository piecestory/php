<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Catalog\Models\Collection;
use App\Domain\Catalog\Models\Product;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Console\Command;

/** Removes the sample catalogue (and its images) before launch. Real pieces are never touched. */
class RemoveDemoCatalog extends Command
{
    protected $signature = 'catalog:remove-demo {--force : Skip the confirmation prompt}';

    protected $description = 'Delete the sample products and collections created by DemoCatalogSeeder';

    public function handle(): int
    {
        $products = Product::withTrashed()->where('sku', 'like', DemoCatalogSeeder::SKU_PREFIX.'%');
        $collections = Collection::query()->where('slug_en', 'like', DemoCatalogSeeder::COLLECTION_PREFIX.'%');

        $this->info("Sample products: {$products->count()}, sample collections: {$collections->count()}");

        if (! $this->option('force') && ! $this->confirm('Delete them permanently?')) {
            return self::SUCCESS;
        }

        $products->each(fn (Product $product) => $product->forceDelete());
        $collections->each(fn (Collection $collection) => $collection->delete());

        $this->info('Sample catalogue removed.');

        return self::SUCCESS;
    }
}
