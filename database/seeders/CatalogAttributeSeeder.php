<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Era;
use App\Domain\Catalog\Models\Material;
use App\Domain\Catalog\Models\Origin;
use Illuminate\Database\Seeder;

/** A starting vocabulary for product attributes; the team edits and extends it from the admin panel. */
class CatalogAttributeSeeder extends Seeder
{
    private const array ERAS = [
        ['18th-century', 'القرن الثامن عشر', '18th century', 1700, 1799],
        ['19th-century', 'القرن التاسع عشر', '19th century', 1800, 1899],
        ['early-20th-century', 'أوائل القرن العشرين', 'Early 20th century', 1900, 1945],
        ['mid-20th-century', 'منتصف القرن العشرين', 'Mid 20th century', 1946, 1979],
        ['contemporary', 'معاصر', 'Contemporary', 1980, null],
    ];

    private const array ORIGINS = [
        ['france', 'فرنسا', 'France'], ['italy', 'إيطاليا', 'Italy'], ['england', 'إنجلترا', 'England'],
        ['austria', 'النمسا', 'Austria'], ['czech', 'التشيك', 'Czech Republic'], ['turkey', 'تركيا', 'Turkey'],
        ['china', 'الصين', 'China'], ['japan', 'اليابان', 'Japan'], ['india', 'الهند', 'India'],
        ['egypt', 'مصر', 'Egypt'], ['morocco', 'المغرب', 'Morocco'], ['syria', 'سوريا', 'Syria'],
    ];

    private const array MATERIALS = [
        ['wood', 'خشب', 'Wood'], ['bronze', 'برونز', 'Bronze'], ['brass', 'نحاس', 'Brass'],
        ['crystal', 'كريستال', 'Crystal'], ['porcelain', 'بورسلين', 'Porcelain'], ['marble', 'رخام', 'Marble'],
        ['silver', 'فضة', 'Silver'], ['glass', 'زجاج', 'Glass'], ['fabric', 'قماش', 'Fabric'],
        ['gilt', 'تذهيب', 'Gilt'], ['leather', 'جلد', 'Leather'], ['canvas', 'قماش لوحات', 'Canvas'],
    ];

    public function run(): void
    {
        foreach (self::ERAS as $order => [$slug, $ar, $en, $from, $to]) {
            Era::query()->firstOrCreate(['slug' => $slug], [
                'name_ar' => $ar, 'name_en' => $en, 'year_from' => $from, 'year_to' => $to, 'sort_order' => $order + 1,
            ]);
        }

        foreach ([Origin::class => self::ORIGINS, Material::class => self::MATERIALS] as $model => $rows) {
            foreach ($rows as $order => [$slug, $ar, $en]) {
                $model::query()->firstOrCreate(['slug' => $slug], ['name_ar' => $ar, 'name_en' => $en, 'sort_order' => $order + 1]);
            }
        }
    }
}
