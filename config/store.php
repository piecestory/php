<?php

declare(strict_types=1);

return [
    // Saudi VAT. Catalogue prices already include it; the VAT part is extracted for display and invoices.
    'vat_rate' => env('STORE_VAT_RATE', '15'),

    // How long an untouched guest cart is kept before it is pruned.
    'guest_cart_days' => (int) env('STORE_GUEST_CART_DAYS', 30),

    // Pieces are held for an unpaid order this long; then the order is cancelled and they return to the shop.
    'payment_hold_minutes' => (int) env('STORE_PAYMENT_HOLD_MINUTES', 15),

    // Advance reservation (owner's decision): held 4 days, then 3 more days of payment reminders,
    // then cancelled automatically. The optional deposit is 15% of the total and is refunded on cancellation.
    'reservation' => [
        'hold_days' => (int) env('STORE_RESERVATION_DAYS', 4),
        'reminder_days' => (int) env('STORE_RESERVATION_REMINDER_DAYS', 3),
        'deposit_percent' => env('STORE_RESERVATION_DEPOSIT_PERCENT', '15'),
        // Open reservations a single mobile number may hold at once (stops one visitor locking the shop).
        'max_open_per_phone' => (int) env('STORE_RESERVATION_MAX_OPEN', 2),
    ],
];
