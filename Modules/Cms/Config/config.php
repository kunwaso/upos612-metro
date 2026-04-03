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
];
