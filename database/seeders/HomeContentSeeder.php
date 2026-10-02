<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Models\HeroSlide;
use Illuminate\Database\Seeder;

/** First home-page slide: the owner's photo of the Al-Bawadi storefront. Skipped once any slide exists. */
class HomeContentSeeder extends Seeder
{
    public function run(): void
    {
        if (HeroSlide::query()->exists()) {
            return;
        }

        $slide = HeroSlide::query()->create([
            'title_ar' => 'حيث تلتقي الأصالة بالفخامة',
            'title_en' => 'Where heritage meets luxury',
            'subtitle_ar' => 'اكتشف مجموعة مختارة بعناية من التحف والقطع النادرة المصممة لتروي قصة كل عصر.',
            'subtitle_en' => 'Discover a carefully curated collection of antiques and rare pieces, each telling the story of its era.',
            'cta_label_ar' => 'تصفح المتجر',
            'cta_label_en' => 'Browse the store',
            'cta_url' => '/store',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $slide->addMedia(resource_path('images/content/showroom-front.jpg'))
            ->preservingOriginal()
            ->withCustomProperties([
                'alt_ar' => 'واجهة معرض قطعة وقصة في البوادي بجدة',
                'alt_en' => 'The Piece & Story showroom in Al-Bawadi, Jeddah',
            ])
            ->toMediaCollection(HeroSlide::MEDIA_DESKTOP);
    }
}
