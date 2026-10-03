<?php

declare(strict_types=1);

return [
    /*
    | Internal test gateway: simulates a card / BNPL payment page so the whole checkout can be
    | exercised before the real provider accounts (Moyasar, Tabby, Tamara) are ready.
    | It never runs in production, whatever this flag says.
    */
    'sandbox' => [
        'enabled' => (bool) env('PAYMENT_SANDBOX', false),
        // Signs simulated webhooks. Empty: derived from APP_KEY.
        'secret' => env('PAYMENT_SANDBOX_SECRET'),
    ],

    /*
    | Payment pages customers are sent to after checkout (e.g. https://checkout.example-gateway.com),
    | comma-separated. The browser security policy only lets checkout forms lead to this site and these
    | addresses, so add a provider's here when its account is activated.
    */
    'checkout_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('PAYMENT_CHECKOUT_ORIGINS', ''))))),
];
