<?php

namespace Tests\Feature;

use App\Http\Controllers\ContactController;
use App\Http\Requests\AttachContactSupplierProductsRequest;
use App\Http\Requests\DetachContactSupplierProductRequest;
use App\User;
use App\Utils\ContactFeedUtil;
use App\Utils\ContactSupplierProductUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ContactSupplierProductsControllerTest extends TestCase
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
            $table->integer('created_by')->nullable();
            $table->string('type', 30)->nullable();
            $table->string('name')->nullable();
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

    public function test_attach_happy_path_is_idempotent_and_ignores_ineligible_products(): void
    {
        $this->be($this->makeUser([
            'supplier.update' => true,
            'supplier.view' => true,
        ]));

        $this->createContact(11, 1, 'supplier', 7);
        $this->createProduct(21, 1, 'Valid Product', 'SKU-VALID', 'single');
        $this->createProduct(22, 1, 'Modifier Product', 'SKU-MOD', 'modifier');
        $this->createProduct(23, 2, 'Tenant 2 Product', 'SKU-T2', 'single');

        $controller = $this->makeController();
        $request = $this->makeAttachRequest(11, [
            'product_ids' => [21, 21, 22, 23, 999],
        ]);

        $response = $controller->attachContactSupplierProducts($request, 11);
        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame(1, $payload['attached_count']);
        $this->assertSame(
            1,
            DB::table('contact_supplier_products')
                ->where('business_id', 1)
                ->where('contact_id', 11)
                ->where('product_id', 21)
                ->count()
        );

        $second = $controller->attachContactSupplierProducts(
            $this->makeAttachRequest(11, ['product_ids' => [21]]),
            11
        )->getData(true);

        $this->assertTrue($second['success']);
        $this->assertSame(0, $second['attached_count']);
    }

    public function test_detach_happy_path_removes_scoped_row(): void
    {
        $this->be($this->makeUser([
            'supplier.update' => true,
            'supplier.view' => true,
        ]));

        $this->createContact(12, 1, 'supplier', 7);
        $this->createProduct(24, 1, 'Detach Product', 'SKU-DETACH', 'single');
        $this->createLink(1, 12, 24);

        $controller = $this->makeController();
        $request = $this->makeDetachRequest(12, 24);

        $payload = $controller->detachContactSupplierProduct($request, 12, 24)->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame(
            0,
            DB::table('contact_supplier_products')
                ->where('business_id', 1)
                ->where('contact_id', 12)
                ->where('product_id', 24)
                ->count()
        );
    }

    public function test_attach_requires_supplier_update_permission(): void
    {
        $this->be($this->makeUser([
            'supplier.update' => false,
            'supplier.view' => true,
        ]));

        $this->createContact(13, 1, 'supplier', 7);

        $this->expectException(AuthorizationException::class);
        $this->makeAttachRequest(13, ['product_ids' => [21]]);
    }

    public function test_attach_rejects_cross_tenant_contact(): void
    {
        $this->be($this->makeUser([
            'supplier.update' => true,
            'supplier.view' => true,
        ]));

        $this->createContact(14, 2, 'supplier', 7);

        $this->expectException(ValidationException::class);
        $this->makeAttachRequest(14, ['product_ids' => [21]]);
    }

    public function test_attach_rejects_non_supplier_contact(): void
    {
        $this->be($this->makeUser([
            'supplier.update' => true,
            'supplier.view' => true,
        ]));

        $this->createContact(15, 1, 'customer', 7);

        $this->expectException(ValidationException::class);
        $this->makeAttachRequest(15, ['product_ids' => [21]]);
    }

    public function test_index_lists_only_eligible_linked_products_and_read_only_actions(): void
    {
        $this->be($this->makeUser([
            'supplier.view' => true,
            'supplier.view_own' => false,
            'customer.view' => false,
            'customer.view_own' => false,
            'supplier.update' => false,
        ]));

        $this->createContact(16, 1, 'supplier', 7);
        $this->createProduct(25, 1, 'Eligible Product', 'SKU-25', 'single');
        $this->createProduct(26, 1, 'Modifier Product', 'SKU-26', 'modifier');
        $this->createProduct(27, 2, 'Cross Tenant Product', 'SKU-27', 'single');
        $this->createLink(1, 16, 25);
        $this->createLink(1, 16, 26);
        $this->createLink(1, 16, 27);

        $controller = $this->makeController();
        $request = $this->makeIndexRequest(16);

        $payload = $controller->getContactSupplierProducts($request, 16)->getData(true);

        $this->assertCount(1, $payload['data']);
        $this->assertSame('Eligible Product', $payload['data'][0]['product_name']);
        $this->assertSame('', $payload['data'][0]['action']);
    }

    public function test_index_respects_view_own_visibility_rules(): void
    {
        $this->be($this->makeUser([
            'supplier.view' => false,
            'supplier.view_own' => true,
            'customer.view' => false,
            'customer.view_own' => false,
            'supplier.update' => false,
        ]));

        $this->createContact(17, 1, 'supplier', 999);

        $controller = $this->makeController();
        $request = $this->makeIndexRequest(17);

        try {
            $controller->getContactSupplierProducts($request, 17);
            $this->fail('Expected 403 for unauthorized own-view access.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    protected function makeController(): ContactController
    {
        return new ContactController(
            \Mockery::mock(Util::class),
            \Mockery::mock(ModuleUtil::class),
            \Mockery::mock(TransactionUtil::class),
            \Mockery::mock(NotificationUtil::class),
            \Mockery::mock(ContactUtil::class),
            \Mockery::mock(ContactFeedUtil::class),
            new ContactSupplierProductUtil()
        );
    }

    protected function makeAttachRequest(int $contact_id, array $payload): AttachContactSupplierProductsRequest
    {
        $request = AttachContactSupplierProductsRequest::create(
            '/contacts/'.$contact_id.'/supplier-products',
            'POST',
            $payload
        );
        $request = $this->bindSessionAndUser($request);
        $request->setRouteResolver(function () use ($contact_id) {
            return new class($contact_id)
            {
                protected $contact_id;

                public function __construct($contact_id)
                {
                    $this->contact_id = $contact_id;
                }

                public function parameter($key, $default = null)
                {
                    if ($key === 'contact') {
                        return $this->contact_id;
                    }

                    return $default;
                }
            };
        });
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->validateResolved();

        return $request;
    }

    protected function makeDetachRequest(int $contact_id, int $product_id): DetachContactSupplierProductRequest
    {
        $request = DetachContactSupplierProductRequest::create(
            '/contacts/'.$contact_id.'/supplier-products/'.$product_id,
            'DELETE',
            []
        );
        $request = $this->bindSessionAndUser($request);
        $request->setRouteResolver(function () use ($contact_id, $product_id) {
            return new class($contact_id, $product_id)
            {
                protected $contact_id;

                protected $product_id;

                public function __construct($contact_id, $product_id)
                {
                    $this->contact_id = $contact_id;
                    $this->product_id = $product_id;
                }

                public function parameter($key, $default = null)
                {
                    if ($key === 'contact') {
                        return $this->contact_id;
                    }

                    if ($key === 'product') {
                        return $this->product_id;
                    }

                    return $default;
                }
            };
        });
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->validateResolved();

        return $request;
    }

    protected function makeIndexRequest(int $contact_id): Request
    {
        $request = Request::create('/contacts/'.$contact_id.'/supplier-products', 'GET', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        return $this->bindSessionAndUser($request);
    }

    protected function bindSessionAndUser(Request $request): Request
    {
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('user.business_id', 1);

        $request->setLaravelSession($session);
        $request->setUserResolver(function () {
            return auth()->user();
        });

        return $request;
    }

    protected function makeUser(array $abilities): User
    {
        return new class($abilities) extends User
        {
            protected $abilities;

            public function __construct(array $abilities)
            {
                parent::__construct();
                $this->id = 7;
                $this->business_id = 1;
                $this->selected_contacts = 0;
                $this->abilities = $abilities;
            }

            public function can($ability, $arguments = [])
            {
                return $this->abilities[$ability] ?? false;
            }
        };
    }

    protected function createContact(int $id, int $business_id, string $type, int $created_by): void
    {
        DB::table('contacts')->insert([
            'id' => $id,
            'business_id' => $business_id,
            'created_by' => $created_by,
            'type' => $type,
            'name' => 'Contact '.$id,
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
