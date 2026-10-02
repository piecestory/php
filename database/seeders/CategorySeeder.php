<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Seeder;

/** The store's top-level categories from the approved site map. Admins can edit them afterwards. */
class CategorySeeder extends Seeder
{
    private const array CATEGORIES = [
        ['antiques', 'تحف-وأنتيك', 'Antiques', 'تحف وأنتيك'],
        ['classic-furniture', 'أثاث-كلاسيك', 'Classic Furniture', 'أثاث كلاسيك'],
        ['chandeliers-lighting', 'نجف-وإضاءة', 'Chandeliers & Lighting', 'نجف وإضاءة'],
        ['art', 'لوحات-فنية', 'Art', 'لوحات فنية'],
        ['luxury-homeware', 'أواني-منزلية-فاخرة', 'Luxury Homeware', 'أواني منزلية فاخرة'],
        ['rare-pieces', 'قطع-نادرة', 'Rare Pieces', 'قطع نادرة'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $order => [$slugEn, $slugAr, $nameEn, $nameAr]) {
            Category::query()->firstOrCreate(['slug_en' => $slugEn], [
                'slug_ar' => $slugAr,
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'sort_order' => $order + 1,
                'is_active' => true,
            ]);
        }
    }
}
