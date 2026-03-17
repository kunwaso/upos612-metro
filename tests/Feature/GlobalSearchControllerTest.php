<?php

namespace Tests\Feature;

use App\Http\Controllers\GlobalSearchController;
use App\Http\Requests\GlobalSearchRequest;
use App\User;
use App\Utils\GlobalSearchUtil;
use App\Utils\ProductUtil;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GlobalSearchControllerTest extends TestCase
{
    public function test_contacts_endpoint_returns_normalized_results_from_util()
    {
        $expected = [
            [
                'id' => 12,
                'text' => 'Acme Trading (C-0012)',
                'subtitle' => '0123456789',
                'url' => 'http://localhost/contacts/12',
                'type' => 'customers',
                'meta' => ['entity_id' => 12],
            ],
        ];

        $mock = \Mockery::mock(GlobalSearchUtil::class);
        $mock->shouldReceive('searchContacts')
            ->once()
            ->with(\Mockery::type(User::class), 7, 'acme', 'customer')
            ->andReturn($expected);
        $this->app->instance(GlobalSearchUtil::class, $mock);

        $user = $this->makeUser([
            'customer.view' => true,
        ]);
        $this->be($user);

        $controller = $this->app->make(GlobalSearchController::class);
        $response = $controller->contacts(
            $this->makeRequest('/global-search/contacts', [
                'user.business_id' => 7,
            ], [
                'q' => 'acme',
                'type' => 'customer',
            ])
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(['results' => $expected], $response->getData(true));
    }

    public function test_products_endpoint_passes_business_id_and_query_to_util()
    {
        $expected = [
            [
                'id' => 44,
                'text' => 'Running Shoe [SKU-44]',
                'subtitle' => 'SKU: SKU-44',
                'url' => 'http://localhost/products/detail/9',
                'type' => 'products',
                'meta' => ['entity_id' => 9, 'variation_id' => 44],
            ],
        ];

        $mock = \Mockery::mock(GlobalSearchUtil::class);
        $mock->shouldReceive('searchProducts')
            ->once()
            ->with(\Mockery::type(User::class), 3, 'shoe')
            ->andReturn($expected);
        $this->app->instance(GlobalSearchUtil::class, $mock);

        $user = $this->makeUser([
            'product.view' => true,
        ]);
        $this->be($user);

        $controller = $this->app->make(GlobalSearchController::class);
        $response = $controller->products(
            $this->makeRequest('/global-search/products', [
                'user.business_id' => 3,
            ], [
                'q' => 'shoe',
            ])
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(['results' => $expected], $response->getData(true));
    }

    public function test_sales_orders_endpoint_returns_empty_results_when_business_is_missing()
    {
        $mock = \Mockery::mock(GlobalSearchUtil::class);
        $mock->shouldNotReceive('searchSalesOrders');
        $this->app->instance(GlobalSearchUtil::class, $mock);

        $user = $this->makeUser([
            'sell.view' => true,
        ]);
        $this->be($user);

        $controller = $this->app->make(GlobalSearchController::class);
        $response = $controller->salesOrders(
            $this->makeRequest('/global-search/sales-orders', [], [
                'q' => 'SO-1001',
            ])
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(['results' => []], $response->getData(true));
    }

    public function test_purchases_endpoint_throws_403_without_purchase_update_permission()
    {
        $user = $this->makeUser([
            'purchase.update' => false,
        ]);
        $this->be($user);

        $util = new GlobalSearchUtil(\Mockery::mock(ProductUtil::class));
        $controller = new GlobalSearchController($util);

        try {
            $controller->purchases(
                $this->makeRequest('/global-search/purchases', [
                    'user.business_id' => 1,
                ], [
                    'q' => 'PO-1001',
                ])
            );

            $this->fail('Expected a 403 HttpException.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_contacts_endpoint_throws_403_when_user_cannot_view_contacts()
    {
        $user = $this->makeUser([
            'customer.view' => false,
            'customer.view_own' => false,
            'supplier.view' => false,
            'supplier.view_own' => false,
        ]);
        $this->be($user);

        $util = new GlobalSearchUtil(\Mockery::mock(ProductUtil::class));
        $controller = new GlobalSearchController($util);

        try {
            $controller->contacts(
                $this->makeRequest('/global-search/contacts', [
                    'user.business_id' => 1,
                ], [
                    'q' => 'Acme',
                    'type' => 'customer',
                ])
            );

            $this->fail('Expected a 403 HttpException.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    protected function makeUser(array $abilities, $permittedLocations = 'all')
    {
        return new class($abilities, $permittedLocations) extends User
        {
            protected array $abilities;
            protected $permittedLocations;

            public function __construct(array $abilities, $permittedLocations)
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 1;
                $this->abilities = $abilities;
                $this->permittedLocations = $permittedLocations;
            }

            public function can($ability, $arguments = [])
            {
                return $this->abilities[$ability] ?? false;
            }

            public function permitted_locations($business_id = null)
            {
                return $this->permittedLocations;
            }
        };
    }

    protected function makeRequest(string $path, array $sessionData = [], array $query = []): GlobalSearchRequest
    {
        $request = GlobalSearchRequest::create($path, 'GET', $query);
        $session = $this->app['session']->driver();
        $session->start();

        foreach ($sessionData as $key => $value) {
            $session->put($key, $value);
        }

        $request->setLaravelSession($session);
        $request->setUserResolver(function () {
            return auth()->user();
        });
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        return $request;
    }
}
