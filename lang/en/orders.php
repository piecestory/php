<?php

declare(strict_types=1);

return [
    'status' => [
        'pending' => 'Awaiting confirmation',
        'confirmed' => 'Confirmed',
        'processing' => 'Being prepared',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],
    'track' => [
        'title' => 'Track your order',
        'intro' => 'Enter your order number and the mobile number used at checkout.',
        'number' => 'Order number',
        'number_hint' => 'Shown in your order confirmation, e.g. PS-2026-000123',
        'phone' => 'Mobile number',
        'submit' => 'Show order status',
        'not_found' => 'We could not find an order with these details. Please check the order and mobile numbers.',
        'order' => 'Order :number',
        'placed' => 'Placed on',
        'current' => 'Current status',
        'items' => 'Pieces',
        'total' => 'Total',
        'tracking' => 'Shipment number',
        'carrier_link' => 'Track with the carrier',
        'history' => 'Status history',
        'search_again' => 'Track another order',
    ],
];
