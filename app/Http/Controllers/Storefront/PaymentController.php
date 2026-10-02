<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Payments\Actions\HandlePaymentWebhook;
use App\Domain\Payments\Actions\SettlePayment;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentGateways;
use App\Http\Controllers\Controller;
use App\Http\Support\OrderAccess;
use App\Support\Orders\OrderLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Where providers send the customer back, and where they post notifications. The browser's return
 * is never taken at its word: the outcome is read from the provider before anything changes.
 */
class PaymentController extends Controller
{
    public static function returnUrl(Payment $payment): string
    {
        return localized_route('payments.return', ['payment' => $payment->id, 'key' => $payment->order->access_token]);
    }

    public function return(Request $request, Payment $payment, PaymentGateways $gateways, SettlePayment $settle): RedirectResponse
    {
        $order = $payment->order;
        OrderAccess::ensure($request, $order);

        $gateway = $gateways->named($payment->provider);
        if ($gateway !== null) {
            $payment = $settle->handle($payment, $gateway->result($payment));
        }

        $redirect = redirect()->to(OrderLink::page($order->refresh(), app()->getLocale()));

        // Success needs no extra message: the order page itself says the order is confirmed or reserved.
        return match ($payment->status) {
            PaymentStatus::Captured => $redirect,
            PaymentStatus::Refunded => $redirect->with('error', __('payments.flash.refunded')),
            PaymentStatus::Failed, PaymentStatus::Cancelled => $redirect->with('error', __('payments.flash.failed')),
            default => $redirect->with('status', __('payments.flash.processing')),
        };
    }

    public function webhook(Request $request, string $provider, HandlePaymentWebhook $handle): Response
    {
        $headers = array_map(fn (array $values) => (string) ($values[0] ?? ''), $request->headers->all());

        return $handle->handle($provider, $request->getContent(), $headers)
            ? response('ok')
            : response('invalid', 400);
    }
}
