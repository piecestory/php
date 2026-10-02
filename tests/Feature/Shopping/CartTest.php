<?php

declare(strict_types=1);

use App\Domain\Cart\Data\CartSummary;
use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\Enums\PublicationStatus;
use App\Http\Support\CurrentCart;

function guestToken(): string
{
    return Cart::query()->whereNull('user_id')->latest('id')->value('token');
}

it('creates a guest cart on the first addition and remembers it in a cookie', function (): void {
    $product = Product::factory()->published()->create(['price' => 1150]);

    $this->get('/store')->assertOk();
    expect(Cart::query()->count())->toBe(0); // browsing alone never creates carts

    $this->postJson("/cart/items/{$product->id}")
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('cart.count', 1)
        ->assertJsonPath('cart.total', '1,150 ر.س')
        ->assertCookie(CurrentCart::COOKIE);

    $this->withCookie(CurrentCart::COOKIE, guestToken())->get('/cart')->assertOk()->assertSee($product->name_ar)->assertSee('1,150');
});

it('works without JavaScript by redirecting back with a message', function (): void {
    $product = Product::factory()->published()->create();

    $this->from('/store')->post("/cart/items/{$product->id}")->assertRedirect('/store')->assertSessionHas('status');
});

it('refuses pieces that cannot be bought', function (array $state): void {
    $product = Product::factory()->published()->create($state);

    $this->postJson("/cart/items/{$product->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', __('cart.errors.unavailable'))
        ->assertCookieMissing(CurrentCart::COOKIE);

    expect(Cart::query()->count())->toBe(0); // refusals never leave empty carts behind
})->with([
    'sold' => [['availability' => ProductAvailability::Sold]],
    'reserved' => [['availability' => ProductAvailability::Reserved]],
    'price on request' => [['availability' => ProductAvailability::OnRequest]],
    'out of stock' => [['stock_quantity' => 0]],
    'draft' => [['status' => PublicationStatus::Draft]],
]);

it('never adds more of a unique piece than exists', function (): void {
    $product = Product::factory()->published()->create(['stock_quantity' => 1]);

    $this->postJson("/cart/items/{$product->id}")->assertOk();
    $this->withCookie(CurrentCart::COOKIE, guestToken())
        ->postJson("/cart/items/{$product->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', __('cart.errors.quantity_exceeded'));
});

it('updates quantities within stock and removes lines', function (): void {
    $product = Product::factory()->published()->create(['stock_quantity' => 3]);
    $this->postJson("/cart/items/{$product->id}");
    $cookie = [CurrentCart::COOKIE => guestToken()];

    $this->withCookies($cookie)->postJson("/cart/items/{$product->id}/quantity", ['quantity' => 3])->assertJsonPath('cart.count', 3);
    $this->withCookies($cookie)->postJson("/cart/items/{$product->id}/quantity", ['quantity' => 4])->assertStatus(422);
    $this->withCookies($cookie)->postJson("/cart/items/{$product->id}/remove")->assertJsonPath('cart.count', 0);
});

it('always charges the current price and sets aside pieces that became unavailable', function (): void {
    $kept = Product::factory()->published()->create(['price' => 1000]);
    $sold = Product::factory()->published()->create(['price' => 500]);
    $deleted = Product::factory()->published()->create(['price' => 700]);
    $cart = Cart::newGuestCart();
    foreach ([$kept, $sold, $deleted] as $product) {
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
    }

    $kept->update(['price' => 1150]);
    $sold->update(['availability' => ProductAvailability::Sold]);
    $deleted->delete();

    $summary = CartSummary::for($cart);

    expect($summary->total)->toBe('1150.00')
        ->and($summary->vat)->toBe('150.00') // 15% VAT contained in a VAT-inclusive 1,150
        ->and($summary->count)->toBe(1)
        ->and($summary->unavailable)->toHaveCount(2);

    $this->withCookie(CurrentCart::COOKIE, $cart->token)->get('/cart')->assertSee(__('cart.unavailable'));
    $this->withCookie(CurrentCart::COOKIE, $cart->token)->postJson("/cart/items/{$deleted->id}/remove")->assertOk();
    expect($cart->items()->count())->toBe(2);
});

it('moves the guest cart into the account at sign-in', function (): void {
    $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'secret-pass-1']);
    $inBoth = Product::factory()->published()->create(['stock_quantity' => 2]);
    $guestOnly = Product::factory()->published()->create();
    $soldMeanwhile = Product::factory()->published()->create();

    $member = Cart::query()->create(['user_id' => $user->id, 'token' => str_repeat('m', 40)]);
    $member->items()->create(['product_id' => $inBoth->id, 'quantity' => 2]);
    $guest = Cart::newGuestCart();
    $guest->items()->createMany([
        ['product_id' => $inBoth->id, 'quantity' => 1],
        ['product_id' => $guestOnly->id, 'quantity' => 1],
        ['product_id' => $soldMeanwhile->id, 'quantity' => 1],
    ]);
    $soldMeanwhile->update(['availability' => ProductAvailability::Sold]);

    $this->withCookie(CurrentCart::COOKIE, $guest->token)
        ->post('/login', ['login' => 'a@example.com', 'password' => 'secret-pass-1'])
        ->assertCookieExpired(CurrentCart::COOKIE);

    expect(Cart::query()->find($guest->id))->toBeNull()
        ->and($member->items()->pluck('quantity', 'product_id')->all())->toEqual([$inBoth->id => 2, $guestOnly->id => 1]);
});

it('gives members one cart that follows them', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->published()->create();

    $this->actingAs($user)->postJson("/cart/items/{$product->id}")->assertOk();

    expect(Cart::query()->where('user_id', $user->id)->sole()->items()->count())->toBe(1);
    $this->actingAs($user)->get('/')->assertSee('data-count="1"', escape: false);
});

it('prunes abandoned guest carts but never member carts', function (): void {
    $user = User::factory()->create();
    $expired = Cart::newGuestCart();
    $expired->forceFill(['expires_at' => now()->subDay()])->save();
    $active = Cart::newGuestCart();
    $member = Cart::query()->create(['user_id' => $user->id, 'token' => str_repeat('x', 40), 'expires_at' => now()->subYear()]);

    $this->artisan('model:prune', ['--model' => [Cart::class]])->assertSuccessful();

    expect(Cart::query()->pluck('id')->sort()->values()->all())->toBe(collect([$active->id, $member->id])->sort()->values()->all());
});
