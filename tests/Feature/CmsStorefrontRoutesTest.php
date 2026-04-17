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
            '/shop/faq',
            '/shop/about-us',
            '/shop/contact-us',
        ] as $path) {
            $response = $this->get($path);
            $response->assertOk();
        }
    }

    public function test_blog_routes_follow_vi_canonical_and_en_prefixed_contract(): void
    {
        config(['cms.blog_default_locale' => 'vi']);

        $this->get('/shop/blogs')->assertOk();
        $this->get('/en/shop/blogs')->assertOk();
    }

    public function test_legacy_and_vi_prefixed_blog_urls_redirect_to_vi_canonical(): void
    {
        config(['cms.blog_default_locale' => 'vi']);

        $this->get('/c/blogs')->assertRedirect('/shop/blogs');
        $this->get('/c/blog/sample-10')->assertRedirect('/shop/blog/sample-10');
        $this->get('/vi/shop/blogs')->assertRedirect('/shop/blogs');
        $this->get('/vi/shop/blog/sample-10')->assertRedirect('/shop/blog/sample-10');
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

    public function test_catalog_search_filters_by_name(): void
    {
        $product = $this->resolveStorefrontSearchProduct();
        $searchTerm = trim((string) $product->name);
        if ($searchTerm === '') {
            $this->markTestSkipped('Requires a storefront product with a non-empty name.');
        }

        $response = $this->get('/shop/catalog?s='.urlencode($searchTerm));
        $response->assertOk()->assertSee($product->name, false);
    }

    public function test_catalog_search_filters_by_sku_or_sub_sku(): void
    {
        $product = $this->resolveStorefrontSearchProduct();
        $variation = $product->variations()->first();
        if ($variation === null) {
            $this->markTestSkipped('Requires a storefront product with at least one variation.');
        }

        $originalSubSku = $variation->sub_sku;
        $searchTerm = 'cms-subsku-'.uniqid();
        $variation->sub_sku = $searchTerm;
        $variation->save();

        try {
            $response = $this->get('/shop/catalog?s='.urlencode($searchTerm));
            $response->assertOk()->assertSee($product->name, false);
        } finally {
            $variation->sub_sku = $originalSubSku;
            $variation->save();
        }
    }

    public function test_catalog_search_filters_by_description(): void
    {
        $product = $this->resolveStorefrontSearchProduct();

        $originalDescription = $product->product_description;
        $searchTerm = 'cms-description-'.uniqid();
        $product->product_description = 'Storefront search '.$searchTerm;
        $product->save();

        try {
            $response = $this->get('/shop/catalog?s='.urlencode($searchTerm));
            $response->assertOk()->assertSee($product->name, false);
        } finally {
            $product->product_description = $originalDescription;
            $product->save();
        }
    }

    public function test_catalog_search_persists_when_sort_changes(): void
    {
        $product = $this->resolveStorefrontSearchProduct();
        $searchTerm = trim((string) $product->sku);
        if ($searchTerm === '') {
            $this->markTestSkipped('Requires a storefront product with a non-empty sku.');
        }

        $response = $this->get('/shop/catalog?s='.urlencode($searchTerm).'&sort=name');
        $response->assertOk()
            ->assertSee($product->name, false)
            ->assertSee('name="s"', false)
            ->assertSee('value="'.$searchTerm.'"', false);
    }

    public function test_named_home_route_exists(): void
    {
        $this->get(route('cms.home'))->assertStatus(200);
    }

    private function resolveStorefrontSearchProduct(): Product
    {
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
            $this->markTestSkipped('Requires an active for-sale storefront product.');
        }

        return $product;
    }
}
