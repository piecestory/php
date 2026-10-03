<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContactMessageStatus;
use App\Domain\Content\Models\ContactMessage;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Post;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Filament\Resources\Pages\Pages\ManagePages;
use App\Filament\Resources\Posts\Pages\CreatePost;
use Database\Seeders\PagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, PagesSeeder::class]);
});

it('lets content editors edit and publish the fixed pages, but not add or remove them', function (): void {
    $this->actingAs(User::factory()->create()->assignRole(Role::ContentEditor->value));
    $privacy = Page::query()->where('key', 'privacy')->sole();

    Livewire::test(ManagePages::class)
        ->assertActionDoesNotExist('create')
        ->callTableAction('edit', $privacy, ['title_ar' => 'سياسة الخصوصية', 'body_ar' => 'نص معتمد', 'is_published' => true])
        ->assertHasNoTableActionErrors();

    expect($privacy->refresh()->is_published)->toBeTrue()->and($privacy->body_ar)->toBe('نص معتمد');
    $this->get('/policies/privacy')->assertOk()->assertSee('نص معتمد');
});

it('publishes an article with its author and a suggested slug', function (): void {
    $editor = User::factory()->create()->assignRole(Role::ContentEditor->value);
    $this->actingAs($editor);

    Livewire::test(CreatePost::class)
        ->fillForm(['title_ar' => 'كيف تعتني بالنحاس', 'slug_ar' => 'كيف-تعتني-بالنحاس', 'title_en' => 'Caring for brass', 'slug_en' => 'caring-for-brass', 'body_ar' => 'نص المقالة', 'status' => 'published'])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::query()->sole();
    expect($post->author_id)->toBe($editor->id)->and($post->isPublished())->toBeTrue();
    $this->get('/blog/'.$post->slug_ar)->assertOk();
});

it('lets customer service read and close contact messages', function (): void {
    $agent = User::factory()->create()->assignRole(Role::CustomerService->value);
    $message = ContactMessage::query()->create(['name' => 'فهد', 'phone' => '+966551234567', 'message' => 'سؤال عن الثريا', 'status' => ContactMessageStatus::New]);
    $this->actingAs($agent);

    Livewire::test(ManageContactMessages::class)
        ->assertCanSeeTableRecords([$message])
        ->callTableAction('markHandled', $message);

    expect($message->refresh()->status)->toBe(ContactMessageStatus::Handled)->and($message->handled_by)->toBe($agent->id);
});

it('keeps content and messages to their roles', function (): void {
    $this->actingAs(User::factory()->create()->assignRole(Role::CustomerService->value));
    $this->get('/admin/site-pages')->assertForbidden();
    $this->get('/admin/posts')->assertForbidden();
    $this->get('/admin/messages')->assertOk();

    $this->actingAs(User::factory()->create()->assignRole(Role::ContentEditor->value));
    $this->get('/admin/messages')->assertForbidden();
    $this->get('/admin/site-pages')->assertOk();
    $this->get('/admin/posts')->assertOk();
});
