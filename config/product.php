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
        'incoterm' => ['FOB', 'CIF', 'LOCAL'],
        'purchase_uom' => ['pcs', 'yds'],
    ],

    'quote_mailer_driver' => env('PRODUCT_QUOTE_MAILER_DRIVER', 'laravel'),

    /*
    | Public quote password gate (quotes.public_quote_password)
    | input_mode: "password" = single password field; "otp" = split boxes (length = otp_length)
    */
    'public_quote_unlock' => [
        'input_mode' => env('PRODUCT_PUBLIC_QUOTE_UNLOCK_INPUT_MODE', 'password'),
        'otp_length' => (int) env('PRODUCT_PUBLIC_QUOTE_UNLOCK_OTP_LENGTH', 6),
        'otp_digits_only' => filter_var(
            env('PRODUCT_PUBLIC_QUOTE_UNLOCK_OTP_DIGITS_ONLY', true),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],
];
