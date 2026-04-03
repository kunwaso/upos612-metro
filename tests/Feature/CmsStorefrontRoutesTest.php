<?php

namespace Tests\Feature;

use Tests\TestCase;

class CmsStorefrontRoutesTest extends TestCase
{
    public function test_storefront_pages_return_ok(): void
    {
        foreach ([
            '/shop/catalog',
            '/shop/collections',
            '/shop/product',
            '/shop/cart',
            '/shop/checkout',
            '/shop/account',
            '/shop/wishlist',
            '/shop/faq',
            '/shop/about-us',
            '/shop/contact-us',
            '/shop/blogs',
        ] as $path) {
            $response = $this->get($path);
            $response->assertOk();
        }
    }

    public function test_named_home_route_exists(): void
    {
        $this->get(route('cms.home'))->assertStatus(200);
    }
}
