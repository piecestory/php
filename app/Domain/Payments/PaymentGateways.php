<?php

declare(strict_types=1);

namespace App\Domain\Payments;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Enums\PaymentMethod;

/**
 * The configured gateways. Each payment method is served by the first gateway that supports it;
 * methods no gateway supports are simply not offered at checkout.
 */
final class PaymentGateways
{
    /** @param  list<PaymentGateway>  $gateways */
    public function __construct(private readonly array $gateways) {}

    public function for(PaymentMethod $method): ?PaymentGateway
    {
        foreach ($this->gateways as $gateway) {
            if (in_array($method, $gateway->methods(), true)) {
                return $gateway;
            }
        }

        return null;
    }

    public function named(string $code): ?PaymentGateway
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->code() === $code) {
                return $gateway;
            }
        }

        return null;
    }

    /**
     * Offered methods in display order; deposits exclude buy-now-pay-later plans.
     *
     * @return list<PaymentMethod>
     */
    public function availableMethods(bool $forDeposit = false): array
    {
        return array_values(array_filter(
            PaymentMethod::OFFERED,
            fn (PaymentMethod $method) => $this->for($method) !== null && (! $forDeposit || $method->canPayDeposit()),
        ));
    }
}
