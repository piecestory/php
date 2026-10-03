<?php

declare(strict_types=1);

use App\Domain\Content\Enums\ContactMessageStatus;
use App\Domain\Content\Models\ContactMessage;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Post;
use App\Domain\Identity\Models\User;
use App\Mail\ContactMessageMail;
use Database\Seeders\PagesSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;

it('seeds the pages from the owner\'s decisions: four published, legal texts as drafts', function (): void {
    $this->seed(PagesSeeder::class);

    expect(Page::query()->where('is_published', true)->pluck('key')->sort()->values()->all())
        ->toBe(['about', 'returns-policy', 'services', 'shipping-policy'])
        ->and(Page::query()->where('is_published', false)->pluck('key')->sort()->values()->all())
        ->toBe(['privacy', 'terms']);

    $this->get('/policies/returns')->assertOk()->assertSee('عيب تصنيع')->assertSee('لا تطابق الوصف');
    $this->get('/policies/shipping')->assertOk()->assertSee('150 ر.س');
    $this->get('/en/services')->assertOk()->assertSee('15% deposit');
    $this->get('/policies/privacy')->assertNotFound();
});

it('never overwrites a page the owner has edited when seeding again', function (): void {
    $this->seed(PagesSeeder::class);
    Page::query()->where('key', 'about')->update(['body_ar' => 'نص المالك']);

    $this->seed(PagesSeeder::class);

    expect(Page::query()->where('key', 'about')->value('body_ar'))->toBe('نص المالك');
});

it('lists content pages in the menus only once they are published', function (): void {
    $this->seed(PagesSeeder::class);

    $this->get('/')
        ->assertSee('href="'.url('/about').'"', false)
        ->assertSee('href="'.url('/policies/returns').'"', false)
        ->assertDontSee('href="'.url('/policies/privacy').'"', false);

    Page::query()->where('key', 'privacy')->sole()->update(['is_published' => true]);

    $this->get('/')->assertSee('href="'.url('/policies/privacy').'"', false);
});

it('renders Markdown safely: no raw HTML, no script links', function (): void {
    Page::query()->create([
        'key' => 'about', 'slug_ar' => 'about', 'slug_en' => 'about', 'title_ar' => 'من نحن', 'title_en' => 'About', 'is_published' => true,
        'body_ar' => "## عنوان\n\n<script>alert(1)</script>\n\n[اضغط](javascript:alert(1)) **مهم**",
    ]);

    $this->get('/about')->assertOk()
        ->assertSee('<h2>عنوان</h2>', false)
        ->assertSee('<strong>مهم</strong>', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('javascript:alert', false);
});

it('shows only published articles, in both languages', function (): void {
    $live = Post::factory()->published()->create(['title_ar' => 'العناية بالنحاس', 'title_en' => 'Caring for brass']);
    $draft = Post::factory()->create(['title_ar' => 'مسودة']);
    $scheduled = Post::factory()->create(['title_ar' => 'لاحقًا', 'status' => 'published', 'published_at' => now()->addWeek()]);

    $this->get('/blog')->assertOk()->assertSee('العناية بالنحاس')->assertDontSee('مسودة')->assertDontSee('لاحقًا');
    $this->get('/blog/'.$live->slug_ar)->assertOk()->assertSee('العناية بالنحاس');
    $this->get('/en/blog/'.$live->slug_en)->assertOk()->assertSee('Caring for brass');
    $this->get('/blog/'.$draft->slug_ar)->assertNotFound();
    $this->get('/blog/'.$scheduled->slug_ar)->assertNotFound();
});

it('shows the journal in the menu once it has an article', function (): void {
    $this->get('/')->assertDontSee('href="'.url('/blog').'"', false);

    Post::factory()->published()->create();

    $this->get('/')->assertSee('href="'.url('/blog').'"', false);
});

it('delivers contact messages to the team and the store mailbox', function (): void {
    $this->seed(SettingsSeeder::class);
    Mail::fake();

    $this->post('/contact', ['name' => 'فهد', 'phone' => '0551234567', 'email' => 'Fahad@Example.com', 'subject' => 'معاينة', 'message' => 'أرغب بمعاينة الثريا في معرض البوادي.'])
        ->assertRedirect('/contact')
        ->assertSessionHas('status', __('contact.sent'));

    $message = ContactMessage::query()->sole();
    expect($message->status)->toBe(ContactMessageStatus::New)
        ->and($message->phone)->toBe('+966551234567')
        ->and($message->email)->toBe('fahad@example.com');
    Mail::assertQueued(ContactMessageMail::class, fn (ContactMessageMail $mail) => $mail->hasTo('info@piecenstory.com') && $mail->hasReplyTo('fahad@example.com'));
});

it('validates the contact form and traps bots', function (array $input, string $field): void {
    $this->from('/contact')->post('/contact', array_merge(['name' => 'فهد', 'phone' => '0551234567', 'message' => 'رسالة كافية الطول'], $input))
        ->assertSessionHasErrors($field);

    expect(ContactMessage::query()->count())->toBe(0);
})->with([
    'a way to reply is required' => [['phone' => '', 'email' => ''], 'phone'],
    'message too short' => [['message' => 'قصيرة'], 'message'],
    'honeypot filled' => [['website' => 'http://spam.example'], 'website'],
]);

it('limits contact messages per visitor', function (): void {
    foreach (range(1, 3) as $attempt) {
        $this->post('/contact', ['name' => 'x', 'phone' => '0551234567', 'message' => 'رسالة كافية الطول']);
    }

    $this->post('/contact', ['name' => 'x', 'phone' => '0551234567', 'message' => 'رسالة كافية الطول'])->assertStatus(429);
});

it('prefills the contact form for signed-in customers', function (): void {
    $this->actingAs(User::factory()->create(['name' => 'منيرة', 'phone' => '+966559990000']))
        ->get('/contact')->assertOk()->assertSee('value="منيرة"', false)->assertSee('value="0559990000"', false);
});
