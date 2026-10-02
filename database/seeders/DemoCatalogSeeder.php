<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Collection;
use App\Domain\Catalog\Models\Era;
use App\Domain\Catalog\Models\Material;
use App\Domain\Catalog\Models\Origin;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Enums\PublicationStatus;
use App\Support\Localization\Slug;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Sample catalogue for staging and design review ONLY. Every piece is marked as a sample
 * (SKU DEMO-###, sample description) and is removed with `php artisan catalog:remove-demo`.
 * Run with: php artisan db:seed --class=DemoCatalogSeeder
 */
class DemoCatalogSeeder extends Seeder
{
    public const string SKU_PREFIX = 'DEMO-';

    public const string COLLECTION_PREFIX = 'demo-';

    // [ar, en, category, era, origin, materials, price, condition, extras]
    private const array PIECES = [
        ['جرامافون أنتيك ببوق نحاسي', 'Antique brass-horn gramophone', 'antiques', 'early-20th-century', 'england', ['wood', 'brass'], 2150, 'very_good', []],
        ['ساعة رف فرنسية مذهبة', 'Gilded French mantel clock', 'antiques', '19th-century', 'france', ['bronze', 'gilt'], 2950, 'excellent', ['rare' => true]],
        ['نجفة كريستال بستة أذرع', 'Six-arm crystal chandelier', 'chandeliers-lighting', 'mid-20th-century', 'czech', ['crystal', 'brass'], 4200, 'excellent', ['sale' => 3800]],
        ['أباجورة برونزية بغطاء قماشي', 'Bronze table lamp with fabric shade', 'chandeliers-lighting', 'early-20th-century', 'france', ['bronze', 'fabric'], 1350, 'good', []],
        ['كرسي برجير مطرز يدويًا', 'Hand-embroidered bergère armchair', 'classic-furniture', '19th-century', 'france', ['wood', 'fabric'], 3750, 'restored', ['sale' => 3400]],
        ['طاولة جانبية بسطح رخامي', 'Marble-top side table', 'classic-furniture', 'early-20th-century', 'italy', ['wood', 'marble'], 2600, 'very_good', []],
        ['كونسول مذهب بمرآة', 'Gilt console with mirror', 'classic-furniture', '19th-century', 'italy', ['wood', 'gilt', 'glass'], 8900, 'restored', ['rare' => true]],
        ['لوحة زيتية لمنظر ريفي', 'Oil painting of a country landscape', 'art', '19th-century', 'england', ['canvas'], 6400, 'very_good', []],
        ['لوحة طبيعة صامتة بإطار مذهب', 'Still life in a gilt frame', 'art', 'mid-20th-century', 'france', ['canvas', 'gilt'], 3200, 'excellent', []],
        ['مزهرية صينية بزخارف زرقاء', 'Blue-and-white Chinese vase', 'rare-pieces', '19th-century', 'china', ['porcelain'], 1850, 'very_good', ['rare' => true]],
        ['طقم شاي بورسلين مذهب', 'Gilded porcelain tea set', 'luxury-homeware', 'mid-20th-century', 'austria', ['porcelain', 'gilt'], 1450, 'excellent', []],
        ['صينية فضية محفورة', 'Engraved silver tray', 'luxury-homeware', 'early-20th-century', 'egypt', ['silver'], 2750, 'very_good', []],
        ['إبريق نحاسي دمشقي', 'Damascene brass ewer', 'antiques', '19th-century', 'syria', ['brass'], 1900, 'good', []],
        ['صندوق مجوهرات مطعّم بالصدف', 'Mother-of-pearl inlaid jewellery box', 'antiques', 'early-20th-century', 'syria', ['wood'], 1250, 'very_good', []],
        ['مرآة جدارية بإطار منحوت', 'Carved-frame wall mirror', 'classic-furniture', '19th-century', 'italy', ['wood', 'gilt', 'glass'], 4800, 'restored', ['availability' => 'reserved']],
        ['تمثال برونزي لفارس', 'Bronze horseman sculpture', 'rare-pieces', '19th-century', 'france', ['bronze', 'marble'], 7500, 'excellent', ['rare' => true]],
        ['زوج شمعدانات كريستال', 'Pair of crystal candelabra', 'chandeliers-lighting', 'mid-20th-century', 'czech', ['crystal'], 2200, 'excellent', []],
        ['فازة زجاج مورانو', 'Murano glass vase', 'luxury-homeware', 'mid-20th-century', 'italy', ['glass'], 1650, 'excellent', []],
        ['سجادة حرير منسوجة يدويًا', 'Hand-woven silk rug', 'luxury-homeware', 'contemporary', 'turkey', ['fabric'], 9500, 'excellent', []],
        ['كرسي هزاز خشبي', 'Wooden rocking chair', 'classic-furniture', 'early-20th-century', 'england', ['wood'], 1950, 'good', ['availability' => 'sold']],
        ['خريطة عتيقة مؤطرة', 'Framed antique map', 'art', '18th-century', 'england', ['glass', 'wood'], 2400, 'as_found', ['rare' => true]],
        ['مصباح مكتب نحاسي', 'Brass desk lamp', 'chandeliers-lighting', 'early-20th-century', 'england', ['brass'], 980, 'good', []],
        ['طبق خزفي مغربي', 'Moroccan ceramic plate', 'luxury-homeware', 'contemporary', 'morocco', ['porcelain'], 650, 'excellent', []],
        ['مبخرة فضية', 'Silver incense burner', 'luxury-homeware', 'mid-20th-century', 'india', ['silver'], 1100, 'very_good', []],
    ];

    private const array COLLECTIONS = [
        ['classic-salon', 'صالون كلاسيكي', 'Classic salon', [5, 6, 7, 15]],
        ['crystal-light', 'نور الكريستال', 'Crystal light', [3, 4, 17, 22]],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo catalogue data must never be seeded in production.');
        }

        $this->call([CategorySeeder::class, CatalogAttributeSeeder::class]);

        $categories = Category::query()->pluck('id', 'slug_en');
        $eras = Era::query()->pluck('id', 'slug');
        $origins = Origin::query()->pluck('id', 'slug');
        $materials = Material::query()->pluck('id', 'slug');
        $ids = [];

        foreach (self::PIECES as $index => [$ar, $en, $category, $era, $origin, $materialSlugs, $price, $condition, $extras]) {
            $sku = self::SKU_PREFIX.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $product = Product::query()->updateOrCreate(['sku' => $sku], [
                'category_id' => $categories[$category],
                'era_id' => $eras[$era] ?? null,
                'origin_id' => $origins[$origin] ?? null,
                'name_ar' => $ar,
                'name_en' => $en,
                'slug_ar' => Slug::make($ar.' '.$sku),
                'slug_en' => Slug::make($en.' '.$sku),
                'description_ar' => 'قطعة تجريبية لعرض تصميم المتجر، وستُستبدل بالقطع الحقيقية.',
                'description_en' => 'Sample piece used to preview the store design; it will be replaced by real pieces.',
                'price' => $price,
                'sale_price' => $extras['sale'] ?? null,
                'stock_quantity' => ($extras['availability'] ?? null) === 'sold' ? 0 : 1,
                'availability' => ProductAvailability::from($extras['availability'] ?? 'available'),
                'condition' => ProductCondition::from($condition),
                'is_rare' => $extras['rare'] ?? false,
                'is_featured' => $index < 8,
                'status' => PublicationStatus::Published,
                'published_at' => now()->subDays(count(self::PIECES) - $index),
            ]);
            $product->materials()->sync(array_map(fn (string $slug) => $materials[$slug], $materialSlugs));
            $ids[$index + 1] = $product->id;
        }

        foreach (self::COLLECTIONS as $order => [$slug, $ar, $en, $pieces]) {
            $collection = Collection::query()->updateOrCreate(['slug_en' => self::COLLECTION_PREFIX.$slug], [
                'slug_ar' => self::COLLECTION_PREFIX.Slug::make($ar),
                'name_ar' => $ar,
                'name_en' => $en,
                'description_ar' => 'مجموعة تجريبية لعرض التصميم.',
                'description_en' => 'Sample collection used to preview the design.',
                'sort_order' => $order + 1,
                'is_active' => true,
            ]);
            $collection->products()->sync(collect($pieces)->mapWithKeys(fn (int $n, int $i) => [$ids[$n] => ['sort_order' => $i]])->all());
        }
    }
}
