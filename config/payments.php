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
];
