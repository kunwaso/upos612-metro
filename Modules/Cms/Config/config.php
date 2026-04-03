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
];
