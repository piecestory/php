<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\SaveAddress;
use App\Domain\Identity\Models\Address;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use Illuminate\Support\Facades\Hash;

/** @return array<string, string> */
function addressForm(array $overrides = []): array
{
    return array_merge([
        'label' => 'المنزل',
        'recipient_name' => 'سارة',
        'phone' => '0551234567',
        'city' => 'جدة',
        'district' => 'الروضة',
        'street' => 'شارع الأمير سلطان',
        'building_number' => '1234',
        'postal_code' => '23435',
    ], $overrides);
}

it('sends guests to sign in', function (string $method, string $uri): void {
    $this->call($method, $uri)->assertRedirect('/login');
})->with([
    ['GET', '/account'],
    ['GET', '/account/orders'],
    ['GET', '/account/addresses'],
    ['GET', '/account/addresses/new'],
    ['GET', '/account/profile'],
    ['POST', '/account/profile'],
    ['POST', '/account/password'],
    ['GET', '/en/account'],
]);

it('shows the customer only their own orders', function (): void {
    $me = User::factory()->create(['name' => 'سارة']);
    $other = User::factory()->create();
    $mine = Order::factory()->create(['user_id' => $me->id, 'number' => 'PS-2026-000101']);
    Order::factory()->create(['user_id' => $other->id, 'number' => 'PS-2026-000202']);
    Order::factory()->create(['user_id' => null, 'number' => 'PS-2026-000303']); // a guest's order

    $this->actingAs($me)->get('/account')->assertOk()->assertSee('أهلًا سارة')->assertSee('PS-2026-000101')
        ->assertDontSee('PS-2026-000202')->assertDontSee('PS-2026-000303');
    $this->actingAs($me)->get('/account/orders')->assertOk()->assertSee('PS-2026-000101')
        ->assertDontSee('PS-2026-000202')->assertDontSee('PS-2026-000303');

    $this->actingAs($me)->get("/orders/{$mine->number}")->assertOk();
    $this->actingAs($me)->get('/orders/PS-2026-000202')->assertNotFound();
});

it('highlights orders and reservations still awaiting payment', function (): void {
    $me = User::factory()->create();
    Order::factory()->create(['user_id' => $me->id, 'number' => 'PS-2026-000111', 'type' => OrderType::Reservation, 'hold_expires_at' => now()->addDays(3)]);

    $this->actingAs($me)->get('/account')->assertSee(__('account.overview.awaiting'))->assertSee(__('account.overview.pay_now'));
});

it('paginates long order histories', function (): void {
    $me = User::factory()->create();
    Order::factory()->count(12)->create(['user_id' => $me->id]);

    $this->actingAs($me)->get('/account/orders')->assertOk()->assertSee('?page=2', false);
    $this->actingAs($me)->get('/account/orders?page=2')->assertOk();
});

it('saves national addresses, the first one becoming the default', function (): void {
    $me = User::factory()->create();

    $this->actingAs($me)->post('/account/addresses', addressForm(['building_number' => '١٢٣٤', 'short_address' => 'rrrd2929']))
        ->assertRedirect('/account/addresses')->assertSessionHas('status');
    $this->actingAs($me)->post('/account/addresses', addressForm(['label' => 'العمل']));

    $first = $me->addresses()->oldest('id')->first();
    expect($me->addresses()->count())->toBe(2)
        ->and($first->is_default)->toBeTrue()
        ->and($first->building_number)->toBe('1234')
        ->and($first->short_address)->toBe('RRRD2929')
        ->and($first->phone)->toBe('+966551234567')
        ->and($me->addresses()->where('is_default', true)->count())->toBe(1);
});

it('validates the address', function (array $input, string $field): void {
    $me = User::factory()->create();

    $this->actingAs($me)->from('/account/addresses/new')->post('/account/addresses', addressForm($input))->assertSessionHasErrors($field);
    expect($me->addresses()->count())->toBe(0);
})->with([
    'building number is 4 digits' => [['building_number' => '12'], 'building_number'],
    'postal code is 5 digits' => [['postal_code' => 'abcde'], 'postal_code'],
    'Saudi mobile' => [['phone' => '0123'], 'phone'],
    'city required' => [['city' => ''], 'city'],
    'short address format' => [['short_address' => '12'], 'short_address'],
]);

it('moves the default to another address and keeps exactly one default', function (): void {
    $me = User::factory()->create();
    $home = Address::factory()->for($me)->create(['is_default' => true]);
    $work = Address::factory()->for($me)->create();

    $this->actingAs($me)->post("/account/addresses/{$work->id}/default")->assertRedirect('/account/addresses');

    expect($work->refresh()->is_default)->toBeTrue()->and($home->refresh()->is_default)->toBeFalse();
});

it('promotes another address when the default one is deleted', function (): void {
    $me = User::factory()->create();
    $home = Address::factory()->for($me)->create(['is_default' => true]);
    $work = Address::factory()->for($me)->create();

    $this->actingAs($me)->post("/account/addresses/{$home->id}/delete")->assertRedirect('/account/addresses');

    expect(Address::query()->find($home->id))->toBeNull()->and($work->refresh()->is_default)->toBeTrue();
});

it('never lets a customer see or change another customer\'s address', function (): void {
    $me = User::factory()->create();
    $theirs = Address::factory()->create(['city' => 'الدمام', 'is_default' => true]);

    $this->actingAs($me)->get("/account/addresses/{$theirs->id}/edit")->assertNotFound();
    $this->actingAs($me)->post("/account/addresses/{$theirs->id}", addressForm())->assertNotFound();
    $this->actingAs($me)->post("/account/addresses/{$theirs->id}/default")->assertNotFound();
    $this->actingAs($me)->post("/account/addresses/{$theirs->id}/delete")->assertNotFound();
    $this->actingAs($me)->get('/account/addresses')->assertDontSee('الدمام');

    expect($theirs->refresh()->city)->toBe('الدمام')->and($theirs->user_id)->not->toBe($me->id);
});

it('edits an address in place', function (): void {
    $me = User::factory()->create();
    $address = Address::factory()->for($me)->create(['is_default' => true]);

    $this->actingAs($me)->get("/account/addresses/{$address->id}/edit")->assertOk()->assertSee($address->street);
    $this->actingAs($me)->post("/account/addresses/{$address->id}", addressForm(['district' => 'النعيم']))->assertRedirect('/account/addresses');

    expect($address->refresh()->district)->toBe('النعيم')->and($address->is_default)->toBeTrue();
});

it('limits the address book to 10 addresses', function (): void {
    $me = User::factory()->create();
    Address::factory()->for($me)->count(SaveAddress::MAX_ADDRESSES)->create();

    $this->actingAs($me)->from('/account/addresses/new')->post('/account/addresses', addressForm())
        ->assertSessionHas('error', __('account.addresses.limit', ['max' => 10]));
    expect($me->addresses()->count())->toBe(10);
});

it('prefills checkout with the default address', function (): void {
    $me = User::factory()->create();
    Address::factory()->for($me)->create(['is_default' => true, 'street' => 'شارع التحلية']);
    $product = App\Domain\Catalog\Models\Product::factory()->published()->create();
    $cart = App\Domain\Cart\Models\Cart::query()->create(['user_id' => $me->id, 'token' => str_repeat('c', 40)]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
    $this->seed([Database\Seeders\ShippingMethodSeeder::class, Database\Seeders\BranchSeeder::class]);
    App\Domain\Shipping\Models\ShippingMethod::query()->update(['is_active' => true]);

    $this->actingAs($me)->get('/checkout')->assertOk()->assertSee('value="شارع التحلية"', false);
});

it('updates the customer\'s details and message language', function (): void {
    $me = User::factory()->create(['name' => 'قديم', 'phone' => '+966551234567', 'email' => 'me@example.com', 'locale' => 'ar']);

    $this->actingAs($me)->post('/account/profile', ['name' => 'Sara', 'email' => 'me@example.com', 'phone' => '0551234567', 'locale' => 'en'])
        ->assertRedirect('/en/account/profile')
        ->assertSessionHas('status', __('account.profile.saved', locale: 'en'));

    expect($me->refresh()->name)->toBe('Sara')->and($me->locale)->toBe('en');
});

it('asks for the current password before changing the email or mobile', function (): void {
    $me = User::factory()->create(['phone' => '+966551234567', 'email' => 'me@example.com', 'password' => 'secret-pass-1', 'phone_verified_at' => now()]);
    $form = ['name' => $me->name, 'email' => 'me@example.com', 'phone' => '0559999999', 'locale' => 'ar'];

    $this->actingAs($me)->from('/account/profile')->post('/account/profile', $form)->assertSessionHasErrors('current_password');
    $this->actingAs($me)->from('/account/profile')->post('/account/profile', [...$form, 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
    expect($me->refresh()->phone)->toBe('+966551234567');

    $this->actingAs($me)->post('/account/profile', [...$form, 'current_password' => 'secret-pass-1'])->assertSessionHasNoErrors();
    expect($me->refresh()->phone)->toBe('+966559999999')->and($me->phone_verified_at)->toBeNull(); // must be verified again
});

it('refuses an email or mobile that belongs to another account', function (): void {
    User::factory()->create(['email' => 'taken@example.com', 'phone' => '+966500000000']);
    $me = User::factory()->create(['phone' => '+966551234567', 'email' => 'me@example.com', 'password' => 'secret-pass-1']);

    $this->actingAs($me)->from('/account/profile')
        ->post('/account/profile', ['name' => 'x', 'email' => 'TAKEN@example.com', 'phone' => '0500000000', 'locale' => 'ar', 'current_password' => 'secret-pass-1'])
        ->assertSessionHasErrors(['email', 'phone']);
});

it('keeps the email on accounts that sign in with it', function (): void {
    $me = User::factory()->create(['phone' => '+966551234567', 'email' => 'me@example.com', 'password' => 'secret-pass-1']);

    $this->actingAs($me)->from('/account/profile')
        ->post('/account/profile', ['name' => 'x', 'email' => '', 'phone' => '0551234567', 'locale' => 'ar', 'current_password' => 'secret-pass-1'])
        ->assertSessionHasErrors('email');
});

it('changes the password only with the current one', function (): void {
    $me = User::factory()->create(['password' => 'secret-pass-1']);

    $this->actingAs($me)->from('/account/profile')
        ->post('/account/password', ['password_current' => 'wrong', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
        ->assertSessionHasErrors('password_current');

    $this->actingAs($me)
        ->post('/account/password', ['password_current' => 'secret-pass-1', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
        ->assertRedirect('/account/profile')->assertSessionHas('status');

    expect(Hash::check('new-pass-123', $me->refresh()->password))->toBeTrue();
});

it('lets customers who signed up by SMS set a first password and change their mobile freely', function (): void {
    $me = User::factory()->create(['email' => null, 'password' => null, 'phone' => '+966551234567']);

    $this->actingAs($me)->get('/account/profile')->assertOk()->assertSee(__('account.password.set_title'));
    $this->actingAs($me)->post('/account/profile', ['name' => 'سارة', 'email' => '', 'phone' => '0559998888', 'locale' => 'ar'])->assertSessionHasNoErrors();
    $this->actingAs($me)->post('/account/password', ['password' => 'first-pass-1', 'password_confirmation' => 'first-pass-1'])->assertSessionHasNoErrors();

    expect($me->refresh()->phone)->toBe('+966559998888')->and(Hash::check('first-pass-1', $me->password))->toBeTrue();
});

it('links the header account icon to the account once signed in', function (): void {
    $this->actingAs(User::factory()->create())->get('/')->assertSee('href="'.url('/account').'"', false);
});
