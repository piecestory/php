<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Content\Models\Faq;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Filament\Resources\HeroSlides\Pages\ManageHeroSlides;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/* The small "add / edit in a window" forms the team uses for site content. */

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole(Role::ContentEditor->value));
});

it('adds a question to the FAQ, shown on the site straight away', function (): void {
    Livewire::test(ManageFaqs::class)
        ->callAction('create', data: [
            'question_ar' => 'هل يمكنني معاينة القطعة قبل الشراء؟', 'question_en' => 'Can I view a piece before buying?',
            'answer_ar' => 'نعم، في معارضنا بجدة.', 'answer_en' => 'Yes, at our Jeddah showrooms.',
        ])
        ->assertHasNoActionErrors();

    expect(Faq::query()->sole())->is_active->toBeTrue();
    $this->get(localized_route('faq'))->assertOk()->assertSee('هل يمكنني معاينة القطعة قبل الشراء؟');
});

it('requires both languages for an FAQ entry', function (): void {
    Livewire::test(ManageFaqs::class)
        ->callAction('create', data: ['question_ar' => 'سؤال', 'answer_ar' => 'جواب'])
        ->assertHasActionErrors(['question_en' => 'required', 'answer_en' => 'required']);
});

it('adds a sub-category with addresses in both languages', function (): void {
    $parent = Category::factory()->create();

    Livewire::test(ManageCategories::class)
        ->callAction('create', data: [
            'name_ar' => 'ساعات حائط', 'name_en' => 'Wall clocks', 'slug_ar' => 'ساعات-حائط', 'slug_en' => 'wall-clocks',
            'parent_id' => $parent->id,
        ])
        ->assertHasNoActionErrors();

    $category = Category::query()->where('slug_en', 'wall-clocks')->sole();
    expect($category->parent_id)->toBe($parent->id);
    $this->get(localized_route('category', $category))->assertOk()->assertSee('ساعات حائط');
});

it('refuses a category address already used by another category', function (): void {
    Category::factory()->create(['slug_en' => 'lighting']);

    Livewire::test(ManageCategories::class)
        ->callAction('create', data: ['name_ar' => 'إضاءة', 'name_en' => 'Lighting', 'slug_ar' => 'إضاءة-٢', 'slug_en' => 'lighting'])
        ->assertHasActionErrors(['slug_en' => 'unique']);
});

it('keeps home slide buttons on this site', function (string $url): void {
    Livewire::test(ManageHeroSlides::class)
        ->callAction('create', data: ['title_ar' => 'شريحة', 'title_en' => 'Slide', 'cta_label_ar' => 'تسوق', 'cta_url' => $url])
        ->assertHasActionErrors(['cta_url']);
})->with(['https://other-site.example/offer', '//other-site.example', '/\\other-site.example', 'javascript:alert(1)']);
