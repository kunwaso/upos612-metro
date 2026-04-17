<?php

return [
    'name' => 'Cms',
    'module_version' => '2.0',
    'pid' => 15,

    /*
    | Public CMS storefront catalog: which tenant's App\Product rows to show.
    | Set CMS_STOREFRONT_BUSINESS_ID in .env (integer business id).
    */
    'storefront_business_id' => env('CMS_STOREFRONT_BUSINESS_ID') !== null && env('CMS_STOREFRONT_BUSINESS_ID') !== ''
        ? (int) env('CMS_STOREFRONT_BUSINESS_ID')
        : null,

    /*
    | Optional: user id (must belong to the storefront business) to assign Essentials To Dos
    | created from storefront RFQ. If unset, business owner_id is used, then first business user.
    */
    'storefront_rfq_todo_user_id' => env('CMS_STOREFRONT_RFQ_TODO_USER_ID') !== null && env('CMS_STOREFRONT_RFQ_TODO_USER_ID') !== ''
        ? (int) env('CMS_STOREFRONT_RFQ_TODO_USER_ID')
        : null,

    /*
    | Blog v2 re-architecture flags and locale settings.
    */
    'blog_supported_locales' => ['en', 'vi'],
    'blog_default_locale' => 'vi',
    'blog_features' => [
        'v2_routes_enabled' => env('CMS_BLOG_V2_ROUTES_ENABLED', true),
        'v2_render_enabled' => env('CMS_BLOG_V2_RENDER_ENABLED', true),
        'comments_enabled' => env('CMS_BLOG_COMMENTS_ENABLED', true),
        'likes_enabled' => env('CMS_BLOG_LIKES_ENABLED', true),
    ],
];
