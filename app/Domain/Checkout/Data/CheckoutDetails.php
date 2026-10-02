<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Data;

use App\Domain\Orders\Enums\OrderType;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Domain\Store\Models\Branch;

/** Everything the customer entered at checkout, already validated. */
final readonly class CheckoutDetails
{
    /**
     * @param  string  $phone  E.164 (+9665XXXXXXXX)
     * @param  array{city: string, district: string, street: string, building_number: string, postal_code: string, additional_number: ?string, short_address: ?string}|null  $address  delivery only
     */
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public OrderType $type,
        public ShippingMethod $shippingMethod,
        public ?Branch $pickupBranch,
        public ?array $address,
        public ?string $note,
        public string $locale,
        public ?int $userId = null,
    ) {}
}
