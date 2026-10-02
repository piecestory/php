<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Seeder;

/** Category tile images from the owner's showroom photos. Only fills categories that have no image yet. */
class CategoryImageSeeder extends Seeder
{
    public function run(): void
    {
        Category::query()->whereNull('parent_id')->get()->each(function (Category $category): void {
            $file = resource_path("images/content/categories/{$category->slug_en}.jpg");

            if (! is_file($file) || $category->hasMedia(Category::MEDIA_IMAGE)) {
                return;
            }

            $category->addMedia($file)
                ->preservingOriginal()
                ->withCustomProperties(['alt_ar' => $category->name_ar, 'alt_en' => $category->name_en])
                ->toMediaCollection(Category::MEDIA_IMAGE);
        });
    }
}
