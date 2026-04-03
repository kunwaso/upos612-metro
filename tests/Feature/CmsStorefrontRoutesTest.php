<?php

namespace Tests\Feature;

use App\Business;
use App\Product;
use Tests\TestCase;

class CmsStorefrontRoutesTest extends TestCase
{
    public function test_storefront_pages_return_ok(): void
    {
        foreach ([
            '/shop/catalog',
            '/shop/collections',
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

    public function test_shop_product_redirects_to_catalog(): void
    {
        $this->get('/shop/product')->assertRedirect('/shop/catalog');
    }

    public function test_catalog_and_product_show_respect_storefront_config(): void
    {
        $business = Business::query()->first();
        if ($business === null) {
            $this->markTestSkipped('Requires a business row in the database.');
        }

        config(['cms.storefront_business_id' => $business->id]);

        $this->get('/shop/catalog')->assertOk();

        $product = Product::query()
            ->where('business_id', $business->id)
            ->active()
            ->productForSales()
            ->first();

        if ($product === null) {
            $this->markTestSkipped('Requires an active for-sale product for the first business.');
        }

        $this->get('/shop/product/'.$product->id)
            ->assertOk()
            ->assertSee($product->name, false);

        $this->get('/shop/product/999999001')->assertNotFound();
    }

    public function test_named_home_route_exists(): void
    {
        $this->get(route('cms.home'))->assertStatus(200);
    }
}
