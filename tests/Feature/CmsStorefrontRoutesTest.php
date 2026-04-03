<?php

namespace Tests\Feature;

use App\Business;
use App\Product;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CmsStorefrontRoutesTest extends TestCase
{
    public function test_storefront_pages_return_ok(): void
    {
        foreach ([
            '/shop/catalog',
            '/shop/collections',
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

    public function test_cart_and_checkout_redirect_to_catalog(): void
    {
        $this->get('/shop/cart')->assertRedirect('/shop/catalog');
        $this->get('/shop/checkout')->assertRedirect('/shop/catalog');
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

    public function test_rfq_show_and_store(): void
    {
        if (! Schema::hasTable('cms_quote_requests')) {
            $this->markTestSkipped('Requires cms_quote_requests table.');
        }

        $business = Business::query()->first();
        if ($business === null) {
            $this->markTestSkipped('Requires a business row in the database.');
        }

        config(['cms.storefront_business_id' => $business->id]);

        $product = Product::query()
            ->where('business_id', $business->id)
            ->active()
            ->productForSales()
            ->first();

        if ($product === null) {
            $this->markTestSkipped('Requires an active for-sale product for the first business.');
        }

        $this->get('/shop/product/'.$product->id.'/request-quote')->assertOk();
        $this->get('/shop/product/999999001/request-quote')->assertNotFound();

        if (Schema::hasTable('essentials_to_dos') && class_exists(\Modules\Essentials\Entities\ToDo::class)) {
            if (! \App\User::query()->where('business_id', $business->id)->exists()) {
                $this->markTestSkipped('Requires at least one user for the business to assign RFQ todo.');
            }
        }

        $response = $this->post('/shop/product/'.$product->id.'/request-quote', [
            'email' => 'customer@example.com',
            'phone' => '123456789',
            'company' => 'ACME',
            'message' => 'Please quote for bulk order.',
        ]);

        $response->assertRedirect('/shop/product/'.$product->id);
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('cms_quote_requests', [
            'business_id' => $business->id,
            'product_id' => $product->id,
            'email' => 'customer@example.com',
            'phone' => '123456789',
            'company' => 'ACME',
        ]);

        if (Schema::hasTable('essentials_to_dos') && class_exists(\Modules\Essentials\Entities\ToDo::class)) {
            $todo = \Modules\Essentials\Entities\ToDo::query()
                ->where('business_id', $business->id)
                ->where('description', 'like', '%customer@example.com%')
                ->latest('id')
                ->first();
            $this->assertNotNull($todo);
            $this->assertStringContainsString((string) $product->id, (string) $todo->description);
        }
    }

    public function test_named_home_route_exists(): void
    {
        $this->get(route('cms.home'))->assertStatus(200);
    }
}
