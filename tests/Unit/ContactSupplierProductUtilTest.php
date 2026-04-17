<?php

namespace Tests\Unit;

use App\Utils\ContactSupplierProductUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContactSupplierProductUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index();
            $table->string('type', 30)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('type', 30)->index();
            $table->timestamps();
        });

        Schema::create('contact_supplier_products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index();
            $table->integer('contact_id')->index();
            $table->integer('product_id')->index();
            $table->timestamps();
            $table->unique(
                ['business_id', 'contact_id', 'product_id'],
                'contact_supplier_products_business_contact_product_unique'
            );
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contact_supplier_products');
        Schema::dropIfExists('products');
        Schema::dropIfExists('contacts');
        parent::tearDown();
    }

    public function test_assert_supplier_contact_rejects_non_supplier_contact(): void
    {
        $this->createContact(100, 1, 'customer');
        $util = new ContactSupplierProductUtil();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not a supplier');

        $util->assertSupplierContact(1, 100);
    }

    public function test_get_query_for_datatable_excludes_modifier_and_cross_tenant_products(): void
    {
        $this->createContact(101, 1, 'supplier');
        $this->createContact(102, 1, 'supplier');

        $this->createProduct(201, 1, 'Valid Product', 'SKU-VALID', 'single');
        $this->createProduct(202, 1, 'Modifier Product', 'SKU-MOD', 'modifier');
        $this->createProduct(203, 2, 'Cross Tenant Product', 'SKU-CROSS', 'single');

        $this->createLink(1, 101, 201);
        $this->createLink(1, 101, 202);
        $this->createLink(1, 101, 203);
        $this->createLink(1, 102, 201);

        $util = new ContactSupplierProductUtil();
        $rows = $util->getQueryForDatatable(1, 101)->get();

        $this->assertCount(1, $rows);
        $this->assertSame(201, (int) $rows[0]->product_id);
        $this->assertSame('Valid Product', $rows[0]->product_name);
        $this->assertSame('SKU-VALID', $rows[0]->sku);
        $this->assertNotNull($rows[0]->created_at);
    }

    public function test_attach_products_is_idempotent_and_ignores_invalid_ids(): void
    {
        $this->createContact(103, 1, 'supplier');

        $this->createProduct(204, 1, 'Attachable Product', 'SKU-204', 'single');
        $this->createProduct(205, 1, 'Modifier Product', 'SKU-205', 'modifier');
        $this->createProduct(206, 2, 'Other Tenant Product', 'SKU-206', 'single');

        $util = new ContactSupplierProductUtil();
        $result_first = $util->attachProducts(1, 103, [204, 204, 0, -5, 'abc', 205, 206]);

        $this->assertSame([
            'attached_count' => 1,
            'ignored_count' => 6,
            'total_requested' => 7,
        ], $result_first);
        $this->assertSame(
            1,
            DB::table('contact_supplier_products')
                ->where('business_id', 1)
                ->where('contact_id', 103)
                ->where('product_id', 204)
                ->count()
        );

        $result_second = $util->attachProducts(1, 103, [204, 204]);

        $this->assertSame([
            'attached_count' => 0,
            'ignored_count' => 2,
            'total_requested' => 2,
        ], $result_second);
        $this->assertSame(
            1,
            DB::table('contact_supplier_products')
                ->where('business_id', 1)
                ->where('contact_id', 103)
                ->where('product_id', 204)
                ->count()
        );
    }

    public function test_detach_product_only_deletes_scoped_row(): void
    {
        $this->createContact(104, 1, 'supplier');
        $this->createContact(105, 1, 'supplier');
        $this->createContact(106, 2, 'supplier');

        $this->createProduct(207, 1, 'Scoped Product', 'SKU-207', 'single');
        $this->createProduct(208, 2, 'Tenant Two Product', 'SKU-208', 'single');

        $this->createLink(1, 104, 207);
        $this->createLink(1, 105, 207);
        $this->createLink(2, 106, 208);

        $util = new ContactSupplierProductUtil();
        $deleted = $util->detachProduct(1, 104, 207);

        $this->assertSame(1, $deleted);
        $this->assertSame(
            0,
            DB::table('contact_supplier_products')
                ->where('business_id', 1)
                ->where('contact_id', 104)
                ->where('product_id', 207)
                ->count()
        );
        $this->assertSame(
            1,
            DB::table('contact_supplier_products')
                ->where('business_id', 1)
                ->where('contact_id', 105)
                ->where('product_id', 207)
                ->count()
        );
        $this->assertSame(
            1,
            DB::table('contact_supplier_products')
                ->where('business_id', 2)
                ->where('contact_id', 106)
                ->where('product_id', 208)
                ->count()
        );
    }

    protected function createContact(int $id, int $business_id, string $type): void
    {
        DB::table('contacts')->insert([
            'id' => $id,
            'business_id' => $business_id,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createProduct(int $id, int $business_id, string $name, string $sku, string $type): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'business_id' => $business_id,
            'name' => $name,
            'sku' => $sku,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createLink(int $business_id, int $contact_id, int $product_id): void
    {
        DB::table('contact_supplier_products')->insert([
            'business_id' => $business_id,
            'contact_id' => $contact_id,
            'product_id' => $product_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
