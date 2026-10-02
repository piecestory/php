<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\HeroSlide;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\StoreSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

it('shows the brand hero, categories and service promises on the home page', function (): void {
    $this->seed(DatabaseSeeder::class);

    $this->get('/')
        ->assertOk()
        ->assertSee('حيث تلتقي الأصالة بالفخامة')
        ->assertSee('نجف وإضاءة')
        ->assertSee('الاستلام من المعرض');
});

it('uses admin hero slides when there are active ones', function (): void {
    HeroSlide::query()->create(['title_ar' => 'مجموعة الخريف', 'title_en' => 'Autumn collection', 'is_active' => true]);
    HeroSlide::query()->create(['title_ar' => 'شريحة منتهية', 'title_en' => 'Ended', 'is_active' => true, 'ends_at' => now()->subDay()]);

    $this->get('/')->assertSee('مجموعة الخريف')->assertDontSee('شريحة منتهية')->assertDontSee('حيث تلتقي الأصالة بالفخامة');
});

it('lists only published pieces as latest pieces', function (): void {
    Product::factory()->published()->create(['name_ar' => 'ساعة منشورة']);
    Product::factory()->create(['name_ar' => 'مسودة مخفية']);

    $this->get('/')->assertSee('ساعة منشورة')->assertDontSee('مسودة مخفية');
});

it('hides inactive categories', function (): void {
    Category::factory()->create(['name_ar' => 'تصنيف ظاهر']);
    Category::factory()->create(['name_ar' => 'تصنيف مخفي', 'is_active' => false]);

    $this->get('/')->assertSee('تصنيف ظاهر')->assertDontSee('تصنيف مخفي');
});

it('loads the home page with a fixed number of queries regardless of content size', function (): void {
    Category::factory()->count(6)->create();
    Product::factory()->published()->count(10)->create();

    DB::enableQueryLog();
    $this->get('/')->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThan(15);
});

it('shows active FAQ entries in the page language', function (): void {
    Faq::query()->create(['question_ar' => 'سؤال؟', 'question_en' => 'Question?', 'answer_ar' => 'جواب', 'answer_en' => 'Answer', 'is_active' => true]);
    Faq::query()->create(['question_ar' => 'مخفي؟', 'question_en' => 'Hidden?', 'answer_ar' => '-', 'answer_en' => '-', 'is_active' => false]);

    $this->get('/faq')->assertOk()->assertSee('سؤال؟')->assertDontSee('مخفي؟');
    $this->get('/en/faq')->assertOk()->assertSee('Question?')->assertSee('Answer');
});

it('shows an order to a guest who knows its number and mobile', function (): void {
    $order = Order::factory()->create(['number' => 'PS-2026-000123', 'phone' => '+966512345678']);
    $order->recordStatusChange(OrderStatus::Pending, OrderStatus::Confirmed);

    $this->post('/track-order', ['number' => 'ps-2026-000123', 'phone' => '0512345678'])
        ->assertOk()
        ->assertSee('PS-2026-000123')
        ->assertSee('مؤكد');
});

it('gives the same answer when the order number or the mobile is wrong', function (array $input): void {
    Order::factory()->create(['number' => 'PS-2026-000123', 'phone' => '+966512345678']);

    $this->from('/track-order')->post('/track-order', $input)
        ->assertRedirect('/track-order')
        ->assertSessionHasErrors(['number' => __('orders.track.not_found')]);
})->with([
    'wrong phone' => [['number' => 'PS-2026-000123', 'phone' => '0599999999']],
    'wrong number' => [['number' => 'PS-2026-999999', 'phone' => '0512345678']],
]);

it('limits order tracking attempts to slow down guessing', function (): void {
    foreach (range(1, 10) as $attempt) {
        $this->post('/track-order', ['number' => 'PS-X', 'phone' => '0512345678']);
    }

    $this->post('/track-order', ['number' => 'PS-X', 'phone' => '0512345678'])->assertStatus(429);
});

it('refreshes cached settings as soon as a setting changes', function (): void {
    $settings = app(StoreSettings::class);
    expect($settings->enabled('sms.enabled'))->toBeFalse();

    Setting::put('sms', 'enabled', '1');

    expect($settings->enabled('sms.enabled'))->toBeTrue();
});
