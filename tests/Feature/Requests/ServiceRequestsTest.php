<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Consignment\Enums\ConsignmentStatus;
use App\Domain\Consignment\Models\ConsignmentRequest;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Notifications\Sms\SmsGateway;
use App\Domain\PersonalFinder\Enums\FinderRequestStatus;
use App\Domain\PersonalFinder\Models\FinderRequest;
use App\Filament\Resources\ConsignmentRequests\Pages\ViewConsignmentRequest;
use App\Filament\Resources\FinderRequests\Pages\ViewFinderRequest;
use App\Mail\RequestNoticeMail;
use App\Mail\StoreRequestAlertMail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\FakeSmsGateway;

beforeEach(function (): void {
    $this->seed([SettingsSeeder::class, RolesAndPermissionsSeeder::class]);
    Mail::fake();
    $this->app->instance(SmsGateway::class, $this->sms = new FakeSmsGateway);
});

/** @return array<string, mixed> */
function finderForm(array $overrides = []): array
{
    return array_merge([
        'name' => 'هند',
        'phone' => '0551234567',
        'email' => 'hind@example.com',
        'description' => 'أبحث عن ثريا كريستال بوهيمية بستة أذرع.',
        'budget_min' => '٣٠٠٠',
        'budget_max' => '9000',
    ], $overrides);
}

/** A real file with text content and a .jpg name: the type must be detected from the content. */
function textPretendingToBeJpeg(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'fake');
    file_put_contents($path, 'this is not an image');

    return new UploadedFile($path, 'photo.jpg', null, null, true);
}

/** @return array<string, mixed> */
function consignmentForm(array $overrides = []): array
{
    return array_merge([
        'name' => 'سلمان',
        'phone' => '0559876543',
        'city' => 'جدة',
        'title' => 'ساعة جدارية فرنسية',
        'description' => 'ساعة نحاسية من القرن التاسع عشر بحالة جيدة.',
        'asking_price' => '4500',
        'ownership' => '1',
        'photos' => [UploadedFile::fake()->image('front.jpg', 800, 600)],
    ], $overrides);
}

it('takes a Personal Finder request with private photos and a reference number', function (): void {
    $category = Category::factory()->create();

    $this->post('/personal-finder', finderForm([
        'category_id' => $category->id,
        'photos' => [UploadedFile::fake()->image('بيت-سارة-الرياض.jpg'), UploadedFile::fake()->image('b.png')],
    ]))->assertRedirect('/personal-finder')->assertSessionHas('submitted');

    $request = FinderRequest::query()->sole();
    $photos = $request->getMedia(FinderRequest::MEDIA_REFERENCES);

    expect($request->reference)->toBe(sprintf('PF-%s-%06d', now()->format('Y'), $request->id))
        ->and($request->status)->toBe(FinderRequestStatus::New)
        ->and($request->phone)->toBe('+966551234567')
        ->and($request->budget_min)->toBe('3000.00')
        ->and($photos)->toHaveCount(2)
        ->and($photos->every(fn ($m) => $m->disk === 'local'))->toBeTrue()
        ->and($photos->first()->file_name)->not->toContain('سارة'); // customer file names are not kept

    expect($this->sms->sent[0]['message'])->toContain($request->reference);
    Mail::assertQueued(RequestNoticeMail::class, fn (RequestNoticeMail $mail) => $mail->hasTo('hind@example.com'));
    Mail::assertQueued(StoreRequestAlertMail::class, fn (StoreRequestAlertMail $mail) => $mail->hasTo('info@piecenstory.com'));

    $this->get('/personal-finder')->assertSee($request->reference);
});

it('validates finder requests', function (array $input, string $field): void {
    $this->from('/personal-finder')->post('/personal-finder', finderForm($input))->assertSessionHasErrors($field);

    expect(FinderRequest::query()->count())->toBe(0);
})->with([
    'description too short' => [['description' => 'ثريا'], 'description'],
    'budget range reversed' => [['budget_min' => '9000', 'budget_max' => '3000'], 'budget_max'],
    'too many photos' => [['photos' => array_map(fn () => UploadedFile::fake()->image('x.jpg'), range(1, 7))], 'photos'],
    'text renamed to .jpg' => [['photos' => [textPretendingToBeJpeg()]], 'photos.0'],
    'pdf' => [['photos' => [UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf')]], 'photos.0'],
    'oversized photo' => [['photos' => [UploadedFile::fake()->image('big.jpg')->size(11000)]], 'photos.0'],
    'honeypot' => [['website' => 'spam'], 'website'],
]);

it('takes a piece for review only with photos and the owner confirmation', function (): void {
    $this->from('/sell-with-us')->post('/sell-with-us', consignmentForm(['photos' => []]))->assertSessionHasErrors('photos');
    $this->from('/sell-with-us')->post('/sell-with-us', consignmentForm(['ownership' => null]))->assertSessionHasErrors('ownership');
    expect(ConsignmentRequest::query()->count())->toBe(0);

    $this->post('/sell-with-us', consignmentForm())->assertRedirect('/sell-with-us');

    $request = ConsignmentRequest::query()->sole();
    expect($request->reference)->toStartWith('CS-')
        ->and($request->status)->toBe(ConsignmentStatus::New)
        ->and($request->getMedia(ConsignmentRequest::MEDIA_PHOTOS))->toHaveCount(1);
});

it('shows signed-in customers their own requests only', function (): void {
    $me = User::factory()->create();
    $this->actingAs($me)->post('/sell-with-us', consignmentForm(['title' => 'مزهرية زرقاء']));
    $this->actingAs($me)->post('/personal-finder', finderForm());
    $other = new ConsignmentRequest(['name' => 'غيري', 'phone' => '+966500000000', 'city' => 'جدة', 'title' => 'قطعة غيري', 'description' => 'وصف قطعة شخص آخر.', 'user_id' => User::factory()->create()->id]);
    $other->forceFill(['reference' => 'CS-OTHER'])->save();

    $this->actingAs($me)->get('/account/requests')->assertOk()
        ->assertSee('مزهرية زرقاء')->assertSee('PF-')->assertDontSee('قطعة غيري');
});

it('limits request submissions per visitor', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->post('/personal-finder', finderForm());
    }

    $this->post('/personal-finder', finderForm())->assertStatus(429);
});

it('serves request photos only to staff allowed to handle them', function (): void {
    $this->post('/sell-with-us', consignmentForm());
    $photo = ConsignmentRequest::query()->sole()->getFirstMedia(ConsignmentRequest::MEDIA_PHOTOS);
    $url = "/admin/private-media/{$photo->id}";

    $this->get($url)->assertRedirect(); // signed out → sign in
    $this->actingAs(User::factory()->create())->get($url)->assertNotFound(); // a customer
    $this->actingAs(User::factory()->create()->assignRole(Role::ContentEditor->value))->get($url)->assertNotFound();
    $this->actingAs(User::factory()->create()->assignRole(Role::CustomerService->value))->get($url)
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private');

    // Public catalogue images are not reachable through this route.
    $product = Product::factory()->create();
    $public = $product->addMedia(UploadedFile::fake()->image('p.jpg'))->toMediaCollection(Product::MEDIA_GALLERY);
    $this->actingAs(User::factory()->create()->assignRole(Role::Admin->value))->get("/admin/private-media/{$public->id}")->assertNotFound();
});

it('lets staff move a finder request along and send the offer to the customer', function (): void {
    $this->post('/personal-finder', finderForm());
    $request = FinderRequest::query()->sole();
    $staff = User::factory()->create()->assignRole(Role::CustomerService->value);
    $this->actingAs($staff);
    Mail::fake();
    $this->sms->sent = [];

    Livewire::test(ViewFinderRequest::class, ['record' => $request->id])
        ->callAction('changeStatus', ['status' => FinderRequestStatus::Sourcing->value])
        ->assertHasNoActionErrors();
    expect($request->refresh()->status)->toBe(FinderRequestStatus::Sourcing)->and($request->assigned_to)->toBe($staff->id)
        ->and($this->sms->sent)->toBeEmpty();

    Livewire::test(ViewFinderRequest::class, ['record' => $request->id])
        ->callAction('changeStatus', ['status' => FinderRequestStatus::OfferSent->value])
        ->assertHasActionErrors(['message']);

    Livewire::test(ViewFinderRequest::class, ['record' => $request->id])
        ->callAction('changeStatus', ['status' => FinderRequestStatus::OfferSent->value, 'message' => 'وجدنا ثريا بوهيمية بـ 7,500 ر.س في معرض البوادي.'])
        ->assertHasNoActionErrors();

    expect($request->refresh()->status)->toBe(FinderRequestStatus::OfferSent)
        ->and($request->statusChanges()->first()->note)->toContain('7,500');
    Mail::assertQueued(RequestNoticeMail::class, fn (RequestNoticeMail $mail) => $mail->event === 'offer_sent' && str_contains((string) $mail->staffMessage, '7,500'));
});

it('lets staff approve or decline a piece, telling the owner why', function (): void {
    $this->post('/sell-with-us', consignmentForm(['email' => 'salman@example.com']));
    $this->post('/sell-with-us', consignmentForm(['title' => 'ثانية', 'email' => 'salman@example.com']));
    [$first, $second] = ConsignmentRequest::query()->oldest('id')->get()->all();
    $manager = User::factory()->create()->assignRole(Role::StoreManager->value);
    $this->actingAs($manager);
    Mail::fake();

    Livewire::test(ViewConsignmentRequest::class, ['record' => $first->id])
        ->callAction('startReview')
        ->callAction('approve', ['note' => 'أحضر القطعة إلى معرض البوادي لمعاينتها.']);
    Livewire::test(ViewConsignmentRequest::class, ['record' => $second->id])
        ->callAction('reject', ['note' => 'لا تناسب مجموعتنا حاليًا.']);

    expect($first->refresh()->status)->toBe(ConsignmentStatus::Approved)
        ->and($first->reviewed_by)->toBe($manager->id)
        ->and($first->decided_at)->not->toBeNull()
        ->and($second->refresh()->status)->toBe(ConsignmentStatus::Rejected);
    Mail::assertQueued(RequestNoticeMail::class, fn (RequestNoticeMail $mail) => $mail->event === 'approved');
    Mail::assertQueued(RequestNoticeMail::class, fn (RequestNoticeMail $mail) => $mail->event === 'rejected' && $mail->staffMessage === 'لا تناسب مجموعتنا حاليًا.');

    Livewire::test(ViewConsignmentRequest::class, ['record' => $first->id])->assertActionHidden('approve')->assertActionHidden('reject');
});

it('renders the customer messages in both languages', function (string $locale): void {
    $request = FinderRequest::factory()->create(['locale' => $locale, 'reference' => 'PF-2026-000009']);

    $html = (new RequestNoticeMail($request, 'offer_sent', 'Details here'))->locale($locale)->render();

    expect($html)->toContain('PF-2026-000009')->toContain('Details here');
})->with(['ar', 'en']);
