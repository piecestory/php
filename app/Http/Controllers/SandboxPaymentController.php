<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Payments\PaymentGateways;
use App\Infrastructure\Payments\SandboxGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The sandbox provider's "hosted payment page": the tester approves or declines. Not routed in production. */
class SandboxPaymentController extends Controller
{
    public function show(string $reference, PaymentGateways $gateways): View
    {
        $state = $this->gateway($gateways)->state($reference);
        abort_if($state === [], 404);

        return view('payments.sandbox', ['reference' => $reference, 'state' => $state]);
    }

    public function decide(Request $request, string $reference, PaymentGateways $gateways): RedirectResponse
    {
        $returnUrl = $this->gateway($gateways)->decide($reference, $request->input('outcome') === 'approve');
        abort_if($returnUrl === null, 404);

        return redirect()->to($returnUrl);
    }

    private function gateway(PaymentGateways $gateways): SandboxGateway
    {
        $gateway = $gateways->named(SandboxGateway::CODE);
        abort_unless($gateway instanceof SandboxGateway, 404);

        return $gateway;
    }
}
