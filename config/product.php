<?php

return [
    'quote_defaults' => [
        'prefix' => env('PRODUCT_QUOTE_PREFIX', 'RFQ'),
        'expiry_days' => (int) env('PRODUCT_QUOTE_EXPIRY_DAYS', 14),
        'remark' => env('PRODUCT_QUOTE_DEFAULT_REMARK', ''),
    ],

    'shipment_ports' => [
        // Example: 'Shanghai', 'Bangkok'
    ],

    'quote_costing_options' => [
        'incoterm' => ['FOB', 'CIF'],
        'purchase_uom' => ['pcs', 'yds'],
    ],

    'quote_mailer_driver' => env('PRODUCT_QUOTE_MAILER_DRIVER', 'laravel'),
];
