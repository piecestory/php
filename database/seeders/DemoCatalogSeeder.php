<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Collection;
use App\Domain\Catalog\Models\Material;
use App\Domain\Catalog\Models\Origin;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Enums\PublicationStatus;
use App\Support\Localization\Slug;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Sample catalogue for staging and design review ONLY, built on the owner's showroom photos.
 * Names describe what each photo shows; prices, conditions and descriptions are placeholders for the
 * team to replace from the admin panel. Every piece is marked (SKU DEMO-###, sample description) and is
 * removed with `php artisan catalog:remove-demo`.
 *
 * Run with: php artisan db:seed --class=DemoCatalogSeeder (then `php artisan queue:work --stop-when-empty`
 * locally to generate image renditions).
 */
class DemoCatalogSeeder extends Seeder
{
    public const string SKU_PREFIX = 'DEMO-';

    public const string COLLECTION_PREFIX = 'demo-';

    private const string PHOTOS = 'seeders/assets/demo-catalog';

    // [ar, en, category, origin, materials, price, condition, extras, photos]
    private const array PIECES = [
        ['أورغ كهربائي منزلي إيطالي كلاسيكي', 'Vintage Italian home electronic organ', 'antiques', 'italy', ['wood'], 3500, 'very_good', [], ['0197']],
        ['نجفة كريستال طراز إمباير', 'Empire-style crystal chandelier', 'chandeliers-lighting', null, ['crystal', 'brass'], 12500, 'excellent', ['rare' => true], ['1006', '0596']],
        ['نجفة زجاجية بطبقات متدرجة', 'Tiered glass-prism chandelier', 'chandeliers-lighting', null, ['glass'], 6800, 'very_good', [], ['0597']],
        ['إبريق شاي بورسلين مزهّر مع صحنه', 'Floral porcelain teapot with saucer', 'luxury-homeware', null, ['porcelain'], 450, 'excellent', [], ['0763']],
        ['إبريق نحاسي منقوش', 'Engraved brass ewer', 'antiques', null, ['brass'], 1800, 'good', [], ['0800']],
        ['نجفة بورسلين مزخرفة بالورود', 'Floral porcelain chandelier', 'chandeliers-lighting', null, ['porcelain'], 5400, 'very_good', [], ['0860']],
        ['مجسم سفينة شراعية خشبية', 'Wooden sailing-ship model', 'rare-pieces', null, ['wood', 'fabric'], 4200, 'very_good', ['rare' => true], ['0923', '0924', '0925', '0928']],
        ['نجفة كريستال بأذرع شموع', 'Candle-arm crystal chandelier', 'chandeliers-lighting', null, ['crystal'], 9800, 'excellent', ['sale' => 8900], ['0938']],
        ['مزهرية بورسلين خضراء بغطاء', 'Lidded green porcelain urn', 'luxury-homeware', null, ['porcelain', 'gilt'], 2900, 'very_good', [], ['1001']],
        ['طقم ساعة رف وشمعدانين من البرونز المذهّب', 'Gilt-bronze clock garniture with candelabra', 'antiques', null, ['bronze', 'gilt'], 7800, 'very_good', ['rare' => true], ['1008', '1009', '1010']],
        ['زوج مزهريات بورسلين مزخرفة', 'Pair of decorated porcelain vases', 'luxury-homeware', null, ['porcelain', 'gilt'], 3600, 'excellent', [], ['1016']],
        ['مزهرية بورسلين زرقاء بزخارف الطيور', 'Blue porcelain vase with bird motifs', 'rare-pieces', null, ['porcelain'], 2400, 'excellent', [], ['1023']],
        ['طبق تذكاري مزخرف', 'Decorative souvenir plate', 'luxury-homeware', null, [], 250, 'good', [], ['1035']],
        ['نجفة كريستال كلاسيكية', 'Classic crystal chandelier', 'chandeliers-lighting', null, ['crystal'], 7600, 'excellent', [], ['1087']],
        ['خزانة عرض خشبية كلاسيكية', 'Classic wooden display sideboard', 'classic-furniture', null, ['wood'], 8500, 'good', [], ['1093']],
        ['نافورة حديقة بتمثال طفل', 'Garden fountain with child figure', 'art', null, [], 5200, 'very_good', [], ['1122']],
        ['ساعة رف برونزية بتمثال على قاعدة رخامية', 'Bronze figural mantel clock on marble base', 'antiques', null, ['bronze', 'marble'], 4600, 'good', ['rare' => true], ['1130']],
        ['مزهرية بورسلين مزهّرة بمقابض مذهّبة', 'Floral porcelain vase with gilt handles', 'luxury-homeware', null, ['porcelain', 'gilt'], 2200, 'excellent', [], ['1143', '1145']],
        ['لوحة زيتية لمشهد شارع قديم', 'Oil painting of an old street scene', 'art', null, ['canvas', 'gilt'], 6900, 'very_good', [], ['1362']],
        ['أباجورة بقاعدة تمثال كلاسيكي', 'Classical figure table lamp', 'chandeliers-lighting', null, ['fabric'], 1900, 'excellent', [], ['1400']],
        ['تمثال حصان أبيض بلمسات ذهبية', 'White rearing horse with gold accents', 'art', null, ['gilt'], 3200, 'excellent', [], ['1569']],
        ['تمثال حصان برونزي', 'Bronze horse sculpture', 'art', null, ['bronze'], 3900, 'very_good', ['availability' => 'reserved'], ['1860', '1861']],
        ['علبة حلوى كريستال بغطاء', 'Lidded crystal bonbonnière', 'luxury-homeware', null, ['crystal'], 380, 'excellent', [], ['2105', '2106']],
        ['حامل مصحف خزفي مزخرف', 'Decorated ceramic Quran stand', 'luxury-homeware', null, ['porcelain', 'gilt'], 650, 'excellent', [], ['2312', '2313', '2310', '2314', '2311']],
        ['راديو خشبي عتيق', 'Vintage wooden cabinet radio', 'antiques', null, ['wood'], 4400, 'as_found', ['rare' => true, 'availability' => 'sold'], ['2501']],
        ['علبة مزخرفة باللون الأحمر', 'Red decorated trinket box', 'luxury-homeware', null, ['porcelain'], 320, 'excellent', [], ['2968', '2969']],
        ['صحن صغير أحمر مزخرف', 'Small decorated red dish', 'luxury-homeware', null, ['porcelain'], 260, 'excellent', ['sale' => 220], ['2970', '2971']],
        ['لوحة المسجد النبوي بإطار مذهّب', 'Framed artwork of the Prophet’s Mosque', 'art', null, ['gilt'], 2800, 'excellent', [], ['3003', '3004', '3005']],
    ];

    // [slug, ar, en, piece numbers (1-based)]
    private const array COLLECTIONS = [
        ['classic-salon', 'صالون كلاسيكي', 'Classic salon', [15, 10, 11, 19]],
        ['crystal-light', 'نور الكريستال', 'Crystal light', [2, 3, 8, 14]],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo catalogue data must never be seeded in production.');
        }

        $this->callSilent([CategorySeeder::class, CatalogAttributeSeeder::class]);

        $categories = Category::query()->pluck('id', 'slug_en');
        $origins = Origin::query()->pluck('id', 'slug');
        $materials = Material::query()->pluck('id', 'slug');
        $ids = [];

        foreach (self::PIECES as $index => [$ar, $en, $category, $origin, $materialSlugs, $price, $condition, $extras, $photos]) {
            $sku = self::SKU_PREFIX.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $product = Product::query()->updateOrCreate(['sku' => $sku], [
                'category_id' => $categories[$category],
                'origin_id' => $origin !== null ? $origins[$origin] : null,
                'name_ar' => $ar,
                'name_en' => $en,
                'slug_ar' => Slug::make($ar.' '.$sku),
                'slug_en' => Slug::make($en.' '.$sku),
                'description_ar' => 'قطعة تجريبية لعرض تصميم المتجر بصور من المعرض؛ السعر والتفاصيل مؤقتة.',
                'description_en' => 'Sample piece previewing the store with showroom photos; price and details are placeholders.',
                'price' => $price,
                'sale_price' => $extras['sale'] ?? null,
                'stock_quantity' => ($extras['availability'] ?? null) === 'sold' ? 0 : 1,
                'availability' => ProductAvailability::from($extras['availability'] ?? 'available'),
                'condition' => ProductCondition::from($condition),
                'is_rare' => $extras['rare'] ?? false,
                'is_featured' => $index < 10,
                'status' => PublicationStatus::Published,
                'published_at' => now()->subDays(count(self::PIECES) - $index),
            ]);

            $product->materials()->sync(array_map(fn (string $slug) => $materials[$slug], $materialSlugs));
            $this->attachPhotos($product, $photos);
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

            if (! $collection->hasMedia(Collection::MEDIA_COVER)) {
                $collection->addMedia(database_path(self::PHOTOS.'/'.self::PIECES[$pieces[0] - 1][8][0].'.jpg'))
                    ->preservingOriginal()
                    ->toMediaCollection(Collection::MEDIA_COVER);
            }
        }
    }

    /** @param list<string> $photos */
    private function attachPhotos(Product $product, array $photos): void
    {
        if ($product->hasMedia(Product::MEDIA_GALLERY)) {
            return;
        }

        foreach ($photos as $photo) {
            $product->addMedia(database_path(self::PHOTOS."/{$photo}.jpg"))
                ->preservingOriginal()
                ->withCustomProperties(['alt_ar' => $product->name_ar, 'alt_en' => $product->name_en])
                ->toMediaCollection(Product::MEDIA_GALLERY);
        }
    }
}
