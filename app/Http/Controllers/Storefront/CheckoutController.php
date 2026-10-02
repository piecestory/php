<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Domain\Checkout\Actions\PlaceOrder;
use App\Domain\Checkout\Data\OrderTotals;
use App\Domain\Checkout\Exceptions\CheckoutException;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Payments\Actions\StartPayment;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentGateways;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Domain\Store\Enums\BranchType;
use App\Domain\Store\Models\Branch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Http\Support\CurrentCart;
use App\Support\Money\Money;
use App\Support\Orders\OrderLink;
use App\Support\Phone\SaudiMobile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** One-page checkout: contact, pickup or delivery, buy now or reserve, payment method. Guests welcome. */
class CheckoutController extends Controller
{
    public function __construct(private readonly PaymentGateways $gateways) {}

    public function show(Request $request, CurrentCart $cart): View|RedirectResponse
    {
        $summary = $cart->summary();
        if ($summary->lines === []) {
            return redirect()->to(localized_route('cart'));
        }

        $methods = ShippingMethod::query()->where('is_active', true)->orderBy('sort_order')->get();
        $types = CheckoutRequest::allowedTypes($this->gateways);
        $user = $request->user();

        // Totals for every shipping method × order type, so the summary can follow the customer's choices.
        $totals = [];
        foreach ($methods as $method) {
            foreach ($types as $type) {
                $t = OrderTotals::calculate($summary->total, $method, $type);
                $totals["{$method->id}:{$type->value}"] = [
                    'shipping' => bccomp($t->shipping, '0', 2) === 0 ? __('checkout.free') : Money::amount($t->shipping).' '.Money::currency(),
                    'vat' => Money::amount($t->vat).' '.Money::currency(),
                    'total' => Money::amount($t->grandTotal).' '.Money::currency(),
                    'deposit' => Money::amount($t->deposit).' '.Money::currency(),
                ];
            }
        }

        return view('storefront.checkout', [
            'summary' => $summary,
            'methods' => $methods,
            'branches' => Branch::query()->where('is_active', true)->where('is_pickup_point', true)
                ->where('type', BranchType::Showroom)->orderBy('sort_order')->get(),
            'types' => $types,
            'paymentMethods' => $this->gateways->availableMethods(),
            'totals' => $totals,
            'defaults' => [
                'name' => $user instanceof User ? $user->name : null,
                'phone' => $user instanceof User && $user->phone !== null ? SaudiMobile::local($user->phone) : null,
                'email' => $user instanceof User ? $user->email : null,
                'address' => $user instanceof User ? $user->addresses()->orderByDesc('is_default')->first() : null,
            ],
        ]);
    }

    public function store(CheckoutRequest $request, CurrentCart $current, PlaceOrder $place, StartPayment $pay): RedirectResponse
    {
        $cart = $current->get();
        if ($cart === null) {
            return redirect()->to(localized_route('cart'));
        }

        try {
            $order = $place->handle($cart, $request->details());
        } catch (CheckoutException $e) {
            return back()->withInput()->with('error', __($e->translationKey(), $e->replace));
        } finally {
            $current->refresh();
        }

        if ($order->type === OrderType::Reservation) {
            return redirect()->to(OrderLink::page($order))->with('status', __('checkout.reserved'));
        }

        $method = $request->paymentMethod();
        if ($method === null) {
            // Not reachable after validation; the order page lets the customer choose a method anyway.
            return redirect()->to(OrderLink::page($order));
        }

        try {
            $redirect = $pay->handle($order, $method, fn (Payment $payment) => PaymentController::returnUrl($payment));
        } catch (PaymentException $e) {
            return redirect()->to(OrderLink::page($order))->with('error', __($e->translationKey()));
        }

        return redirect()->away($redirect->url);
    }
}
