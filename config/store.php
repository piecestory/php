<?php

declare(strict_types=1);

return [
    // Saudi VAT. Catalogue prices already include it; the VAT part is extracted for display and invoices.
    'vat_rate' => env('STORE_VAT_RATE', '15'),

    // How long an untouched guest cart is kept before it is pruned.
    'guest_cart_days' => (int) env('STORE_GUEST_CART_DAYS', 30),
];
