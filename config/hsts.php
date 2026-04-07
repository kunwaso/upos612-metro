<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security (HSTS)
    |--------------------------------------------------------------------------
    |
    | When enabled, Laravel adds Strict-Transport-Security on HTTPS responses
    | only. Plain HTTP responses are unchanged (never send HSTS over HTTP).
    |
    | For static assets or error pages served directly by nginx/Apache/CDN,
    | also configure HSTS at the edge so every response is covered.
    |
    */

    'enabled' => env('HSTS_ENABLED', true),

    'max_age' => (int) env('HSTS_MAX_AGE', 31536000),

    'include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', false),

    'preload' => env('HSTS_PRELOAD', false),

];
