<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Domain\Checkout\Data\CheckoutDetails;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\PaymentGateways;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Domain\Store\Models\Branch;
use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use App\Support\Text\Digits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * Checkout form. Which fields are required depends on the choices: a showroom for pickup, the
 * Saudi national address for delivery, a payment method unless reserving without a deposit.
 */
class CheckoutRequest extends FormRequest
{
    private const array ADDRESS_DIGIT_FIELDS = ['building_number', 'postal_code', 'additional_number'];

    private ?ShippingMethod $chosenShippingMethod = null;

    protected function prepareForValidation(): void
    {
        $address = (array) $this->input('address', []);

        foreach (self::ADDRESS_DIGIT_FIELDS as $field) {
            if (is_string($address[$field] ?? null)) {
                $address[$field] = trim(Digits::toLatin($address[$field]));
            }
        }
        if (is_string($address['short_address'] ?? null)) {
            $address['short_address'] = mb_strtoupper(trim(Digits::toLatin($address['short_address'])));
        }

        $this->merge(['address' => $address]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $gateways = app(PaymentGateways::class);
        $type = OrderType::tryFrom($this->string('order_type')->toString());
        $method = $this->shippingMethod();

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', new SaudiMobileNumber],
            'email' => ['nullable', 'string', 'email', 'max:190'],
            'order_type' => ['required', Rule::in(array_map(fn (OrderType $t) => $t->value, self::allowedTypes($gateways)))],
            'shipping_method' => ['required', 'integer', Rule::exists('shipping_methods', 'id')->where('is_active', true)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];

        if ($type !== null && $type !== OrderType::Reservation) {
            $methods = $gateways->availableMethods(forDeposit: $type === OrderType::DepositReservation);
            $rules['payment_method'] = ['required', Rule::in(array_map(fn (PaymentMethod $m) => $m->value, $methods))];
        }

        if ($method?->requires_pickup_branch === true) {
            $rules['pickup_branch'] = ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)->where('is_pickup_point', true)];
        } elseif ($method !== null) {
            $rules += [
                'address.city' => ['required', 'string', 'max:100'],
                'address.district' => ['required', 'string', 'max:100'],
                'address.street' => ['required', 'string', 'max:150'],
                'address.building_number' => ['required', 'digits:4'],
                'address.postal_code' => ['required', 'digits:5'],
                'address.additional_number' => ['nullable', 'digits:4'],
                'address.short_address' => ['nullable', 'regex:/^[A-Z]{4}\d{4}$/'],
            ];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return Arr::dot((array) trans('checkout.attributes'));
    }

    /**
     * Purchase and deposit need an online payment method; reserving without a deposit is always possible.
     *
     * @return list<OrderType>
     */
    public static function allowedTypes(PaymentGateways $gateways): array
    {
        return array_values(array_filter([
            $gateways->availableMethods() !== [] ? OrderType::Purchase : null,
            $gateways->availableMethods(forDeposit: true) !== [] ? OrderType::DepositReservation : null,
            OrderType::Reservation,
        ]));
    }

    public function paymentMethod(): ?PaymentMethod
    {
        return PaymentMethod::tryFrom($this->string('payment_method')->toString());
    }

    public function details(): CheckoutDetails
    {
        // Validated above: the method exists and is active.
        $method = $this->shippingMethod() ?? throw new LogicException('Checkout details read before validation.');
        $user = $this->user();
        $email = trim($this->string('email')->toString());
        $note = trim($this->string('note')->toString());

        return new CheckoutDetails(
            name: trim($this->string('name')->toString()),
            phone: (string) SaudiMobile::normalize($this->string('phone')->toString()),
            email: $email === '' ? null : mb_strtolower($email),
            type: OrderType::from($this->string('order_type')->toString()),
            shippingMethod: $method,
            pickupBranch: $method->requires_pickup_branch ? Branch::query()->find($this->integer('pickup_branch')) : null,
            address: $method->requires_pickup_branch ? null : [
                'city' => trim($this->string('address.city')->toString()),
                'district' => trim($this->string('address.district')->toString()),
                'street' => trim($this->string('address.street')->toString()),
                'building_number' => $this->string('address.building_number')->toString(),
                'postal_code' => $this->string('address.postal_code')->toString(),
                'additional_number' => $this->string('address.additional_number')->toString() ?: null,
                'short_address' => $this->string('address.short_address')->toString() ?: null,
            ],
            note: $note === '' ? null : $note,
            locale: app()->getLocale(),
            userId: $user instanceof User ? $user->id : null,
        );
    }

    private function shippingMethod(): ?ShippingMethod
    {
        return $this->chosenShippingMethod ??= ShippingMethod::query()->where('is_active', true)->find($this->integer('shipping_method'));
    }
}
