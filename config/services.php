<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google_custom_search' => [
        'api_key' => env('GOOGLE_CUSTOM_SEARCH_API_KEY'),
        'search_engine_id' => env('GOOGLE_CUSTOM_SEARCH_ENGINE_ID'),
        'base_url' => env('GOOGLE_CUSTOM_SEARCH_BASE_URL', 'https://www.googleapis.com/customsearch/v1'),
        'default_limit' => env('GOOGLE_CUSTOM_SEARCH_DEFAULT_LIMIT', 20),
        'timeout' => env('GOOGLE_CUSTOM_SEARCH_TIMEOUT', 10),
        'verify_ssl' => env('GOOGLE_CUSTOM_SEARCH_VERIFY_SSL', true),
        'ca_bundle' => env('GOOGLE_CUSTOM_SEARCH_CA_BUNDLE', env('AICHAT_HTTP_CA_BUNDLE')),
    ],

    'contact_feeds' => [
        'default_provider' => env('CONTACT_FEEDS_DEFAULT_PROVIDER', 'google'),
        'providers' => ['google', 'facebook', 'linkedin'],
    ],

];
