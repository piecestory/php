<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = (string) (fake()->numberBetween(10, 400) * 50);
        $shipping = '0.00';
        $grand = bcadd($subtotal, $shipping, 2);

        return [
            'number' => 'PS-'.fake()->unique()->numerify('########'),
            'customer_name' => fake()->name(),
            'phone' => '+9665'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'locale' => 'ar',
            'currency' => 'SAR',
            'subtotal' => $subtotal,
            'shipping_total' => $shipping,
            // Prices include 15% VAT: tax = total * 15 / 115
            'tax_total' => bcdiv(bcmul($grand, '15', 4), '115', 2),
            'grand_total' => $grand,
            'ship_recipient_name' => fake()->name(),
            'ship_phone' => '+9665'.fake()->numerify('########'),
            'ship_city' => 'جدة',
            'ship_district' => 'الروضة',
            'placed_at' => now(),
        ];
    }
}
