<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Actions\StartPayment;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentGateways;
use App\Http\Controllers\Controller;
use App\Http\Support\OrderAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The customer's order page (confirmation after checkout, and the link in every message), with payment when due. */
class OrderController extends Controller
{
    public function __construct(private readonly PaymentGateways $gateways) {}

    public function show(Request $request, Order $order): View
    {
        OrderAccess::ensure($request, $order);

        $order->load(['items.product.media', 'pickupBranch', 'shippingMethod', 'statusChanges']);

        return view('storefront.order', [
            'order' => $order,
            'key' => $request->query('key'),
            'paymentMethods' => $order->awaitsPayment() ? $this->methodsFor($order) : [],
        ]);
    }

    public function pay(Request $request, Order $order, StartPayment $pay): RedirectResponse
    {
        OrderAccess::ensure($request, $order);

        $request->validate([
            'payment_method' => ['required', Rule::in(array_map(fn (PaymentMethod $m) => $m->value, $this->methodsFor($order)))],
        ]);

        try {
            $redirect = $pay->handle($order, PaymentMethod::from($request->string('payment_method')->toString()), fn (Payment $payment) => PaymentController::returnUrl($payment));
        } catch (PaymentException $e) {
            return back()->with('error', __($e->translationKey()));
        }

        return redirect()->away($redirect->url);
    }

    /** @return list<PaymentMethod> */
    private function methodsFor(Order $order): array
    {
        $deposit = $order->type === OrderType::DepositReservation && $order->status === OrderStatus::Pending;

        return $this->gateways->availableMethods(forDeposit: $deposit);
    }
}
