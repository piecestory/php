<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Notifications\Sms\SmsGateway;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\PaymentWebhookEvent;
use Database\Seeders\BranchSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\ShippingMethodSeeder;
use Illuminate\Support\Facades\Mail;
use Tests\Support\FakeSmsGateway;

beforeEach(function (): void {
    $this->seed([ShippingMethodSeeder::class, BranchSeeder::class, SettingsSeeder::class]);
    Mail::fake();
    $this->app->instance(SmsGateway::class, new FakeSmsGateway);
});

it('confirms an order from a signed provider notification, once, however often it is resent', function (): void {
    $order = reserve(Product::factory()->published()->create(['price' => 900]), OrderType::Purchase);
    $reference = $order->payments()->sole()->provider_reference;
    $event = ['id' => 'evt_1', 'type' => 'payment.captured', 'reference' => $reference, 'status' => 'captured', 'amount' => '900.00', 'currency' => 'SAR'];

    sandboxWebhook($event)->assertOk();
    sandboxWebhook($event)->assertOk();
    sandboxWebhook($event)->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->amount_paid)->toBe('900.00')
        ->and($order->statusChanges()->where('to_status', 'confirmed')->count())->toBe(1)
        ->and(PaymentWebhookEvent::query()->count())->toBe(1);

    // The customer's own return after the webhook changes nothing either.
    $this->get(localized_route('payments.return', ['payment' => $order->payments()->sole()->id, 'key' => $order->access_token]))
        ->assertSessionMissing('error');
    expect($order->refresh()->amount_paid)->toBe('900.00');
});

it('rejects notifications with a bad signature', function (): void {
    $order = reserve(Product::factory()->published()->create(['price' => 900]), OrderType::Purchase);
    $reference = $order->payments()->sole()->provider_reference;

    sandboxWebhook(['id' => 'evt_2', 'reference' => $reference, 'status' => 'captured', 'amount' => '900.00'], 'forged')->assertStatus(400);
    $this->postJson('/payments/unknown/webhook', [])->assertStatus(400);

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and(PaymentWebhookEvent::query()->count())->toBe(0);
});

it('never confirms an order for a different amount than was charged', function (): void {
    $order = reserve(Product::factory()->published()->create(['price' => 900]), OrderType::Purchase);
    $payment = $order->payments()->sole();

    sandboxWebhook(['id' => 'evt_3', 'reference' => $payment->provider_reference, 'status' => 'captured', 'amount' => '9.00'])->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Failed)
        ->and($payment->failure_reason)->toBe('amount_mismatch');
});

it('does not expose the sandbox payment page for unknown references', function (): void {
    $this->get('/payments/sandbox/sbx_unknown')->assertNotFound();
    $this->post('/payments/sandbox/sbx_unknown', ['outcome' => 'approve'])->assertNotFound();
});
