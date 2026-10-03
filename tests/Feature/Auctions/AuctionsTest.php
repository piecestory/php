<?php

declare(strict_types=1);

use App\Domain\Auctions\Enums\AuctionPhase;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Models\Auction;
use App\Domain\Auctions\Models\AuctionInterest;
use App\Domain\Auctions\Models\AuctionLot;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Notifications\Sms\SmsGateway;
use App\Filament\Resources\Auctions\Pages\CreateAuction;
use App\Filament\Resources\Auctions\Pages\EditAuction;
use App\Filament\Resources\Auctions\RelationManagers\LotsRelationManager;
use App\Mail\AuctionInterestAlertMail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\FakeSmsGateway;

beforeEach(function (): void {
    $this->seed([SettingsSeeder::class, RolesAndPermissionsSeeder::class]);
    Mail::fake();
    $this->app->instance(SmsGateway::class, $this->sms = new FakeSmsGateway);
});

function lotFor(Auction $auction, int $number = 1, array $overrides = []): AuctionLot
{
    return $auction->lots()->create(array_merge([
        'lot_number' => $number,
        'title_ar' => "ثريا رقم {$number}",
        'title_en' => "Chandelier no. {$number}",
        'starting_price' => '4500.00',
        'estimate_low' => '6000.00',
        'estimate_high' => '9000.00',
    ], $overrides));
}

/** @return array<string, mixed> */
function interestForm(array $overrides = []): array
{
    return array_merge(['name' => 'ريم', 'phone' => '0551234567', 'email' => 'reem@example.com'], $overrides);
}

it('derives upcoming, live and ended from the dates, and cancelled from the status', function (): void {
    expect(Auction::factory()->create()->phase())->toBe(AuctionPhase::Upcoming)
        ->and(Auction::factory()->create(['starts_at' => now()->subDay(), 'ends_at' => now()->addDay()])->phase())->toBe(AuctionPhase::Live)
        ->and(Auction::factory()->create(['starts_at' => now()->subWeek(), 'ends_at' => now()->subDay()])->phase())->toBe(AuctionPhase::Ended)
        ->and(Auction::factory()->create(['status' => AuctionStatus::Cancelled])->phase())->toBe(AuctionPhase::Cancelled);
});

it('hides the auctions link and home card until an auction is published', function (): void {
    Auction::factory()->create(['status' => AuctionStatus::Draft]);

    $this->get('/')->assertOk()->assertDontSee(localized_route('auctions'), false);

    Auction::factory()->create();

    $this->get('/')->assertOk()->assertSee(localized_route('auctions'), false)->assertSee(__('site.home.auctions_title'));
});

it('lists published auctions, upcoming first and past ones apart, never drafts or cancelled ones', function (): void {
    $upcoming = Auction::factory()->create(['title_ar' => 'مزاد الربيع']);
    $past = Auction::factory()->create(['title_ar' => 'مزاد الشتاء', 'starts_at' => now()->subWeeks(2), 'ends_at' => now()->subWeek()]);
    Auction::factory()->create(['title_ar' => 'مسودة سرية', 'status' => AuctionStatus::Draft]);
    Auction::factory()->create(['title_ar' => 'مزاد ملغى', 'status' => AuctionStatus::Cancelled]);

    $this->get(localized_route('auctions'))->assertOk()
        ->assertSeeInOrder([__('auctions.current'), 'مزاد الربيع', __('auctions.past'), 'مزاد الشتاء'])
        ->assertDontSee('مسودة سرية')
        ->assertDontSee('مزاد ملغى');
});

it('says so when nothing is coming up', function (): void {
    Auction::factory()->create(['starts_at' => now()->subWeeks(2), 'ends_at' => now()->subWeek()]);

    $this->get(localized_route('auctions'))->assertOk()->assertSee(__('auctions.empty'));
});

it('shows an auction with its lots, prices and estimates, in both languages', function (): void {
    $auction = Auction::factory()->create(['title_ar' => 'مزاد الثريات', 'title_en' => 'Chandeliers Sale', 'slug_en' => 'chandeliers-sale']);
    lotFor($auction, 2);
    lotFor($auction, 1);

    $this->get(localized_route('auctions.show', $auction))->assertOk()
        ->assertSee('مزاد الثريات')
        ->assertSeeInOrder(['ثريا رقم 1', 'ثريا رقم 2'])
        ->assertSee('4,500')
        ->assertSee('6,000 – 9,000')
        ->assertSee(__('auctions.interest.submit'));

    // The "Auctions" menu item stays highlighted on an auction's page.
    expect($this->get(localized_route('auctions.show', $auction))->getContent())
        ->toMatch('#href="'.preg_quote(localized_route('auctions'), '#').'"\s+aria-current="page"#');

    $this->get('/en/auctions/chandeliers-sale')->assertOk()->assertSee('Chandeliers Sale')->assertSee('Lot 1');
});

it('does not open drafts or cancelled auctions', function (AuctionStatus $status): void {
    $auction = Auction::factory()->create(['status' => $status]);

    $this->get(localized_route('auctions.show', $auction))->assertNotFound();
    $this->post(localized_route('auctions.interest', $auction), interestForm())->assertNotFound();

    expect(AuctionInterest::query()->count())->toBe(0);
})->with([AuctionStatus::Draft, AuctionStatus::Cancelled]);

it('registers interest in an auction, confirms by SMS and alerts the team', function (): void {
    $auction = Auction::factory()->create(['title_ar' => 'مزاد الثريات']);

    $this->post(localized_route('auctions.interest', $auction), interestForm(['phone' => '٠٥٥١٢٣٤٥٦٧']))
        ->assertRedirect(localized_route('auctions.show', $auction).'#interest');

    $interest = AuctionInterest::query()->sole();
    expect($interest->phone)->toBe('+966551234567')
        ->and($interest->auction_lot_id)->toBeNull()
        ->and($this->sms->sent)->toHaveCount(1)
        ->and($this->sms->sent[0]['message'])->toContain('مزاد الثريات');
    Mail::assertQueued(AuctionInterestAlertMail::class);

    $this->get(localized_route('auctions.show', $auction))->assertSee(__('auctions.interest.registered'));
});

it('records the chosen lot and links each lot to the form', function (): void {
    $auction = Auction::factory()->create();
    $lot = lotFor($auction, 3);

    $this->get(localized_route('auctions.show', $auction))
        ->assertSee(localized_route('auctions.show', [$auction, 'lot' => $lot->id]).'#interest', false);

    $this->post(localized_route('auctions.interest', $auction), interestForm(['lot' => $lot->id]))->assertRedirect();

    expect(AuctionInterest::query()->sole()->auction_lot_id)->toBe($lot->id);
});

it('does not register the same phone twice for the same auction and lot', function (): void {
    $auction = Auction::factory()->create();

    $this->post(localized_route('auctions.interest', $auction), interestForm());
    $this->post(localized_route('auctions.interest', $auction), interestForm(['name' => 'ريم أحمد']))
        ->assertSessionHas('interest_registered');

    expect(AuctionInterest::query()->count())->toBe(1)->and($this->sms->sent)->toHaveCount(1);
    Mail::assertQueuedCount(1);
});

it('refuses a lot from another auction', function (): void {
    $auction = Auction::factory()->create();
    $otherLot = lotFor(Auction::factory()->create());

    $this->post(localized_route('auctions.interest', $auction), interestForm(['lot' => $otherLot->id]))->assertSessionHasErrors('lot');

    expect(AuctionInterest::query()->count())->toBe(0);
});

it('stops registrations once the auction has ended', function (): void {
    $auction = Auction::factory()->create(['starts_at' => now()->subWeeks(2), 'ends_at' => now()->subDay()]);

    $this->get(localized_route('auctions.show', $auction))->assertOk()
        ->assertSee(__('auctions.interest.ended_text'))
        ->assertDontSee(__('auctions.no_lots'))
        ->assertDontSee(__('auctions.how_title'))
        ->assertDontSee(__('auctions.interest.submit'));

    $this->post(localized_route('auctions.interest', $auction), interestForm())->assertSessionHas('interest_closed');

    expect(AuctionInterest::query()->count())->toBe(0)->and($this->sms->sent)->toBeEmpty();
});

it('validates the contact details and rejects bots', function (): void {
    $auction = Auction::factory()->create();

    $this->post(localized_route('auctions.interest', $auction), interestForm(['name' => '', 'phone' => '12345']))
        ->assertSessionHasErrors(['name', 'phone']);
    $this->post(localized_route('auctions.interest', $auction), interestForm(['website' => 'spam']))->assertSessionHasErrors('website');

    expect(AuctionInterest::query()->count())->toBe(0);
});

it('lets staff create an auction and add lots in the admin', function (): void {
    $this->actingAs(User::factory()->create()->assignRole(Role::StoreManager->value));

    Livewire::test(CreateAuction::class)
        ->fillForm([
            'title_ar' => 'مزاد الخريف', 'title_en' => 'Autumn Sale', 'slug_ar' => 'مزاد-الخريف', 'slug_en' => 'autumn-sale',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i'), 'ends_at' => now()->addWeeks(2)->format('Y-m-d H:i'),
            'status' => AuctionStatus::Scheduled->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $auction = Auction::query()->sole();
    expect($auction->status)->toBe(AuctionStatus::Scheduled);

    Livewire::test(LotsRelationManager::class, ['ownerRecord' => $auction, 'pageClass' => EditAuction::class])
        ->callTableAction('create', data: ['lot_number' => 1, 'title_ar' => 'ساعة', 'title_en' => 'Clock', 'starting_price' => 1200])
        ->assertHasNoTableActionErrors();

    expect($auction->lots()->sole()->title_en)->toBe('Clock');

    Livewire::test(LotsRelationManager::class, ['ownerRecord' => $auction, 'pageClass' => EditAuction::class])
        ->callTableAction('create', data: ['lot_number' => 1, 'title_ar' => 'مكرر', 'title_en' => 'Duplicate', 'starting_price' => 10])
        ->assertHasTableActionErrors(['lot_number' => 'unique']);
});

it('rejects an auction that ends before it starts', function (): void {
    $this->actingAs(User::factory()->create()->assignRole(Role::StoreManager->value));

    Livewire::test(CreateAuction::class)
        ->fillForm([
            'title_ar' => 'مزاد', 'title_en' => 'Sale', 'slug_ar' => 'مزاد', 'slug_en' => 'sale',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i'), 'ends_at' => now()->addDay()->format('Y-m-d H:i'),
            'status' => AuctionStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['ends_at' => 'after']);
});

it('keeps the interest list when a lot is removed', function (): void {
    $auction = Auction::factory()->create();
    $lot = lotFor($auction);
    $this->post(localized_route('auctions.interest', $auction), interestForm(['lot' => $lot->id]));

    $lot->delete();

    expect(AuctionInterest::query()->sole()->auction_lot_id)->toBeNull();
});
