<?php

declare(strict_types=1);

/*
| Shared helpers for the checkout, reservation and payment tests (loaded from tests/Pest.php).
*/

use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Domain\Store\Models\Branch;
use App\Http\Support\CurrentCart;
use App\Infrastructure\Payments\SandboxGateway;
use Illuminate\Testing\TestResponse;

/** A guest cart holding these pieces; returns its cookie token. */
function checkoutCart(Product ...$products): string
{
    $cart = Cart::newGuestCart();
    foreach ($products as $product) {
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
    }

    return $cart->token;
}

/**
 * A valid checkout form: guest, pickup from Al-Bawadi, buy now with mada.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function checkoutForm(array $overrides = []): array
{
    return array_merge([
        'name' => 'سارة العتيبي',
        'phone' => '0551234567',
        'email' => 'sara@example.com',
        'shipping_method' => ShippingMethod::query()->where('code', ShippingMethod::BRANCH_PICKUP)->value('id'),
        'pickup_branch' => Branch::query()->where('name_en', 'Al-Bawadi Showroom')->value('id'),
        'order_type' => OrderType::Purchase->value,
        'payment_method' => 'mada',
    ], $overrides);
}

/**
 * Checks out one piece with the given order type and returns the new order.
 *
 * @param  array<string, mixed>  $overrides
 */
function reserve(Product $product, OrderType $type, array $overrides = []): Order
{
    test()->withCookie(CurrentCart::COOKIE, checkoutCart($product))
        ->post('/checkout', checkoutForm(['order_type' => $type->value, ...$overrides]));

    return Order::query()->latest('id')->firstOrFail();
}

/** Approves the order's latest sandbox payment and follows the customer back to the shop. */
function approveLatestPayment(Order $order): TestResponse
{
    $reference = $order->payments()->latest('id')->value('provider_reference');
    $back = test()->post(route('payments.sandbox.decide', ['reference' => $reference]), ['outcome' => 'approve']);

    return test()->get((string) $back->headers->get('Location'));
}

/**
 * Posts a sandbox provider notification, correctly signed unless a signature is given.
 *
 * @param  array<string, string>  $event
 */
function sandboxWebhook(array $event, ?string $signature = null): TestResponse
{
    $body = json_encode($event, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/payments/sandbox/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SANDBOX_SIGNATURE' => $signature ?? app(SandboxGateway::class)->sign($body),
    ], $body);
}
