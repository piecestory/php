<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Identity\Models\User;
use App\Domain\Wishlist\Models\WishlistItem;

it('lets guests save and unsave pieces in their session', function (): void {
    $product = Product::factory()->published()->create(['name_ar' => 'نجفة محفوظة']);

    $this->postJson("/wishlist/{$product->id}")->assertJson(['saved' => true, 'count' => 1]);
    $this->get('/wishlist')->assertSee('نجفة محفوظة')->assertSee(__('wishlist.guest_note'));
    $this->postJson("/wishlist/{$product->id}")->assertJson(['saved' => false, 'count' => 0]);
    $this->get('/wishlist')->assertSee(__('wishlist.empty'));
});

it('stores members’ wishlists in their account', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->published()->create();

    $this->actingAs($user)->postJson("/wishlist/{$product->id}")->assertJson(['saved' => true]);

    expect(WishlistItem::query()->where('user_id', $user->id)->pluck('product_id')->all())->toBe([$product->id]);
});

it('keeps pieces saved as a guest after signing in, without duplicates', function (): void {
    $user = User::factory()->create(['email' => 'w@example.com', 'password' => 'secret-pass-1']);
    $already = Product::factory()->published()->create();
    $new = Product::factory()->published()->create();
    WishlistItem::query()->create(['user_id' => $user->id, 'product_id' => $already->id]);

    $this->postJson("/wishlist/{$already->id}");
    $this->postJson("/wishlist/{$new->id}");
    $this->post('/login', ['login' => 'w@example.com', 'password' => 'secret-pass-1']);

    expect(WishlistItem::query()->where('user_id', $user->id)->pluck('product_id')->sort()->values()->all())
        ->toBe(collect([$already->id, $new->id])->sort()->values()->all());
});

it('cannot save pieces that are not published', function (): void {
    $draft = Product::factory()->create();

    $this->postJson("/wishlist/{$draft->id}")->assertNotFound();
});

it('marks saved pieces on product cards', function (): void {
    $product = Product::factory()->published()->create();
    $this->postJson("/wishlist/{$product->id}");

    $this->get('/store')->assertSee('data-product="'.$product->id.'"', escape: false)->assertSee('data-saved="true"', escape: false);
});

it('renders cart and wishlist actions as real forms outside any other form', function (): void {
    Product::factory()->published()->count(2)->create();

    $html = $this->get('/store')->getContent();

    expect(substr_count($html, 'action="'.url('/cart/items/')))->toBe(2)
        ->and(preg_match('#<form[^>]*catalog-filters[^>]*>(?:(?!</form>).)*<form#s', $html))->toBe(0);
});
