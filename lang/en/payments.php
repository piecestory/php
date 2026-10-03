<?php

declare(strict_types=1);

return [
    'method' => [
        'mada' => 'mada',
        'credit_card' => 'Credit card',
        'apple_pay' => 'Apple Pay',
        'tabby' => 'Tabby — split in 4',
        'tamara' => 'Tamara — split or pay later',
        'in_store' => 'Paid in the showroom',
    ],
    'flash' => [
        'failed' => 'The payment was not completed and nothing was charged. You can try again.',
        'processing' => 'Your payment is being processed. We will update your order as soon as it is confirmed.',
        'refunded' => 'Your payment arrived after the order had expired, so we have refunded it.',
    ],
    'errors' => [
        'method_unavailable' => 'This payment method is not available right now. Please choose another.',
        'nothing_to_pay' => 'Nothing is due on this order.',
        'provider_failed' => 'We could not reach the payment provider. Please try again shortly.',
    ],
    'sandbox' => [
        'title' => 'Test payment page',
        'notice' => 'Test environment: no money is taken and no card details are asked for.',
        'amount' => 'Amount',
        'method' => 'Method',
        'approve' => 'Simulate a successful payment',
        'decline' => 'Simulate a declined payment',
    ],
];
