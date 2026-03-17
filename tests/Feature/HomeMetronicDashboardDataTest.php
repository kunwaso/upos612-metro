<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\HomeMetronicDashboardUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\RestaurantUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tests\TestCase;

class HomeMetronicDashboardDataTest extends TestCase
{
    public function test_it_returns_safe_empty_payload_when_user_lacks_dashboard_permission()
    {
        $expected = [
            'meta' => ['generated_at' => null],
            'kpis' => [],
            'charts' => [],
            'recent_orders_tabs' => [],
            'product_orders' => [],
            'delivery_feed' => [],
            'stock_rows' => [],
        ];

        $mock = \Mockery::mock(HomeMetronicDashboardUtil::class);
        $mock->shouldReceive('emptyPayload')->once()->andReturn($expected);
        $mock->shouldNotReceive('getDashboardData');
        $this->app->instance(HomeMetronicDashboardUtil::class, $mock);

        $user = $this->makeUser(false, 'all');
        $this->be($user);

        $controller = $this->app->make(HomeController::class);
        $response = $controller->getMetronicDashboardData($this->makeRequest(['user.business_id' => 1]));

        $this->assertSame(200, $response->status());
        $this->assertSame($expected, $response->getData(true));
    }

    public function test_it_returns_dashboard_payload_for_authorized_user()
    {
        $payload = [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'currency' => ['symbol' => '$', 'code' => 'USD', 'precision' => 2],
                'date' => ['format' => 'Y-m-d', 'timezone' => 'UTC'],
                'scope' => ['location_id' => 5, 'permitted_locations' => [5]],
                'range' => [
                    'range' => 'month',
                    'label' => 'This month',
                    'current_start' => '2026-03-01',
                    'current_end' => '2026-03-09',
                    'previous_start' => '2026-02-20',
                    'previous_end' => '2026-02-28',
                ],
            ],
            'kpis' => [
                'expected_earnings' => ['value' => 120.5, 'delta_percent' => 5.2, 'is_positive_delta' => true],
                'sales_summary' => [
                    'value' => 380.25,
                    'delta_percent' => 11.2,
                    'is_positive_delta' => true,
                    'range_label' => 'This month',
                    'breakdown' => [
                        ['label' => 'Total purchase', 'value' => 140.10],
                        ['label' => 'Invoice due', 'value' => 30.15],
                        ['label' => 'Total Sell Return', 'value' => 10.0],
                    ],
                ],
                'orders_this_month' => ['count' => 15, 'goal' => 20, 'remaining' => 5, 'progress_percent' => 75, 'range_label' => 'This month'],
                'average_daily_sales' => ['value' => 102.25, 'delta_percent' => 2.1, 'is_positive_delta' => true, 'range_label' => 'This month'],
                'new_customers_this_month' => ['count' => 6, 'heroes' => [], 'range_label' => 'This month'],
                'sales_this_month' => ['value' => 2400, 'previous_month_goal' => 3200, 'goal_gap' => 800, 'range' => 'month', 'range_label' => 'This month'],
                'discounted_product_sales' => ['value' => 150, 'delta_percent' => 4.3, 'is_positive_delta' => false, 'range_label' => 'This month'],
            ],
            'charts' => [
                'expected_earnings_breakdown' => ['labels' => ['Sell', 'Due', 'Expense'], 'series' => [100, 20, 10]],
                'average_daily_sales' => ['labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 'series' => [1, 2, 3, 4, 5, 6, 7]],
                'sales_this_month' => ['labels' => ['1', '2'], 'series' => [100, 200]],
                'discounted_product_sales' => ['labels' => ['1', '2'], 'series' => [10, 20]],
            ],
            'recent_orders_tabs' => [[], [], [], [], []],
            'product_orders' => [],
            'delivery_feed' => [],
            'stock_rows' => [],
        ];

        $mock = \Mockery::mock(HomeMetronicDashboardUtil::class);
        $mock->shouldReceive('getDashboardData')->once()->with(1, 5, [
            'range' => 'month',
            'start_date' => null,
            'end_date' => null,
        ])->andReturn($payload);
        $mock->shouldNotReceive('emptyPayload');
        $this->app->instance(HomeMetronicDashboardUtil::class, $mock);

        $user = $this->makeUser(true, [5]);
        $this->be($user);

        $controller = $this->app->make(HomeController::class);
        $request = $this->makeRequest(['user.business_id' => 1], ['location_id' => 5]);
        $response = $controller->getMetronicDashboardData($request);

        $this->assertSame(200, $response->status());
        $decoded = $response->getData(true);
        $this->assertArrayHasKey('meta', $decoded);
        $this->assertArrayHasKey('kpis', $decoded);
        $this->assertArrayHasKey('charts', $decoded);
        $this->assertArrayHasKey('recent_orders_tabs', $decoded);
        $this->assertArrayHasKey('product_orders', $decoded);
        $this->assertArrayHasKey('delivery_feed', $decoded);
        $this->assertArrayHasKey('stock_rows', $decoded);
        $this->assertArrayHasKey('range', $decoded['meta']);
        $this->assertSame('month', $decoded['meta']['range']['range']);
        $this->assertArrayHasKey('sales_summary', $decoded['kpis']);
        $this->assertCount(3, $decoded['kpis']['sales_summary']['breakdown']);
        $this->assertSame('This month', $decoded['kpis']['sales_summary']['range_label']);
        $this->assertSame('This month', $decoded['kpis']['orders_this_month']['range_label']);
        $this->assertSame('This month', $decoded['kpis']['average_daily_sales']['range_label']);
        $this->assertSame('This month', $decoded['kpis']['new_customers_this_month']['range_label']);
        $this->assertSame('This month', $decoded['kpis']['discounted_product_sales']['range_label']);
        $this->assertSame(15, $decoded['kpis']['orders_this_month']['count']);
        $this->assertCount(7, $decoded['charts']['average_daily_sales']['series']);
    }

    public function test_it_returns_safe_empty_payload_when_business_id_is_missing()
    {
        $expected = [
            'meta' => ['generated_at' => null],
            'kpis' => [],
            'charts' => [],
            'recent_orders_tabs' => [],
            'product_orders' => [],
            'delivery_feed' => [],
            'stock_rows' => [],
        ];

        $mock = \Mockery::mock(HomeMetronicDashboardUtil::class);
        $mock->shouldReceive('emptyPayload')->once()->andReturn($expected);
        $mock->shouldNotReceive('getDashboardData');
        $this->app->instance(HomeMetronicDashboardUtil::class, $mock);

        $user = $this->makeUser(true, 'all');
        $this->be($user);

        $controller = $this->app->make(HomeController::class);
        $response = $controller->getMetronicDashboardData($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame($expected, $response->getData(true));
    }

    public function test_it_passes_sales_chart_filter_to_dashboard_util()
    {
        $payload = [
            'meta' => [],
            'kpis' => [],
            'charts' => [],
            'recent_orders_tabs' => [],
            'product_orders' => [],
            'delivery_feed' => [],
            'stock_rows' => [],
        ];

        $mock = \Mockery::mock(HomeMetronicDashboardUtil::class);
        $mock->shouldReceive('getDashboardData')->once()->with(1, 5, [
            'range' => 'custom',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ])->andReturn($payload);
        $mock->shouldNotReceive('emptyPayload');
        $this->app->instance(HomeMetronicDashboardUtil::class, $mock);

        $user = $this->makeUser(true, [5]);
        $this->be($user);

        $controller = $this->app->make(HomeController::class);
        $request = $this->makeRequest(['user.business_id' => 1], [
            'location_id' => 5,
            'sales_chart_range' => 'custom',
            'sales_chart_start_date' => '2026-01-01',
            'sales_chart_end_date' => '2026-01-31',
        ]);
        $response = $controller->getMetronicDashboardData($request);

        $this->assertSame(200, $response->status());
        $this->assertSame($payload, $response->getData(true));
    }

    public function test_empty_payload_contains_sales_summary_shape()
    {
        $transaction_util = \Mockery::mock(TransactionUtil::class);
        $product_util = \Mockery::mock(ProductUtil::class);
        $util = new HomeMetronicDashboardUtil($transaction_util, $product_util);

        $payload = $util->emptyPayload();

        $this->assertArrayHasKey('kpis', $payload);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('range', $payload['meta']);
        $this->assertArrayHasKey('range', $payload['meta']['range']);
        $this->assertArrayHasKey('label', $payload['meta']['range']);
        $this->assertArrayHasKey('sales_summary', $payload['kpis']);
        $this->assertArrayHasKey('value', $payload['kpis']['sales_summary']);
        $this->assertArrayHasKey('delta_percent', $payload['kpis']['sales_summary']);
        $this->assertArrayHasKey('is_positive_delta', $payload['kpis']['sales_summary']);
        $this->assertArrayHasKey('range_label', $payload['kpis']['sales_summary']);
        $this->assertArrayHasKey('breakdown', $payload['kpis']['sales_summary']);
        $this->assertCount(3, $payload['kpis']['sales_summary']['breakdown']);

        foreach ($payload['kpis']['sales_summary']['breakdown'] as $row) {
            $this->assertArrayHasKey('label', $row);
            $this->assertArrayHasKey('value', $row);
        }
    }

    public function test_index_passes_initial_dashboard_payload_to_view_for_authorized_user()
    {
        $initial_payload = [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'currency' => ['symbol' => '$', 'code' => 'USD', 'precision' => 2],
                'date' => ['format' => 'Y-m-d', 'timezone' => 'UTC'],
                'scope' => ['location_id' => null, 'permitted_locations' => []],
                'range' => [
                    'range' => 'month',
                    'label' => 'This month',
                    'current_start' => '2026-03-01',
                    'current_end' => '2026-03-09',
                    'previous_start' => '2026-02-20',
                    'previous_end' => '2026-02-28',
                ],
            ],
            'kpis' => [
                'expected_earnings' => ['value' => 0, 'delta_percent' => 0, 'is_positive_delta' => true, 'range_label' => 'This month', 'breakdown' => []],
                'sales_summary' => ['value' => 500, 'delta_percent' => 5, 'is_positive_delta' => true, 'range_label' => 'This month', 'breakdown' => []],
                'orders_this_month' => ['count' => 10, 'goal' => 20, 'remaining' => 10, 'progress_percent' => 50, 'delta_percent' => 2, 'is_positive_delta' => true, 'range_label' => 'This month'],
                'average_daily_sales' => ['value' => 50, 'delta_percent' => 2, 'is_positive_delta' => true, 'range_label' => 'This month'],
                'new_customers_this_month' => ['count' => 3, 'heroes' => [], 'range_label' => 'This month'],
                'sales_this_month' => ['value' => 1200, 'previous_month_goal' => 1500, 'goal_gap' => 300, 'range' => 'month', 'range_label' => 'This month'],
                'discounted_product_sales' => ['value' => 25, 'delta_percent' => 1, 'is_positive_delta' => true, 'range_label' => 'This month'],
            ],
            'charts' => [
                'expected_earnings_breakdown' => ['labels' => [], 'series' => []],
                'average_daily_sales' => ['labels' => [], 'series' => []],
                'sales_this_month' => ['labels' => [], 'series' => []],
                'discounted_product_sales' => ['labels' => [], 'series' => []],
            ],
            'recent_orders_tabs' => [[], [], [], [], []],
            'product_orders' => [],
            'delivery_feed' => [],
            'stock_rows' => [],
        ];

        $empty_payload = [
            'meta' => ['generated_at' => null, 'range' => ['range' => 'month', 'label' => 'This month']],
            'kpis' => [],
            'charts' => [],
            'recent_orders_tabs' => [],
            'product_orders' => [],
            'delivery_feed' => [],
            'stock_rows' => [],
        ];

        $dashboard_util = \Mockery::mock(HomeMetronicDashboardUtil::class);
        $dashboard_util->shouldReceive('emptyPayload')->once()->andReturn($empty_payload);
        $dashboard_util->shouldReceive('getDashboardData')->once()->with(1, null, ['range' => 'month'])->andReturn($initial_payload);
        $this->app->instance(HomeMetronicDashboardUtil::class, $dashboard_util);

        $business_util = \Mockery::mock(BusinessUtil::class);
        $business_util->shouldReceive('is_admin')->andReturn(true);
        $business_util->shouldReceive('getCurrentFinancialYear')->once()->with(1)->andReturn([
            'start' => '2026-01-01',
            'end' => '2026-12-31',
        ]);
        $this->app->instance(BusinessUtil::class, $business_util);

        $transaction_util = \Mockery::mock(TransactionUtil::class);
        $transaction_util->shouldReceive('isModuleEnabled')->once()->with('service_staff')->andReturn(false);
        $transaction_util->shouldReceive('getSellsCurrentFy')->once()->andReturn(collect([]));
        $this->app->instance(TransactionUtil::class, $transaction_util);

        $module_util = \Mockery::mock(ModuleUtil::class);
        $module_util->shouldReceive('getModuleData')->once()->with('dashboard_widget')->andReturn([]);
        $this->app->instance(ModuleUtil::class, $module_util);

        $this->app->instance(Util::class, \Mockery::mock(Util::class));
        $this->app->instance(RestaurantUtil::class, \Mockery::mock(RestaurantUtil::class));
        $this->app->instance(ProductUtil::class, \Mockery::mock(ProductUtil::class));

        $currency_alias = \Mockery::mock('alias:App\Currency');
        $currency_alias->shouldReceive('where')->once()->with('id', \Mockery::any())->andReturnSelf();
        $currency_alias->shouldReceive('first')->once()->andReturn((object) ['code' => 'USD']);

        $location_alias = \Mockery::mock('alias:App\BusinessLocation');
        $location_alias->shouldReceive('forDropdown')->once()->with(1)->andReturn(collect([]));

        $user = $this->makeUser(true, 'all');
        $user->user_type = 'user';
        $this->be($user);

        $request = Request::create('/home', 'GET');
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('user.business_id', 1);
        $session->put('business.currency_id', 1);
        $session->put('business', (object) [
            'currency_id' => 1,
            'custom_labels' => null,
            'common_settings' => [],
        ]);
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);

        $controller = $this->app->make(HomeController::class);
        $response = $controller->index();

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame($initial_payload, $response->getData()['dashboardData']);
        $this->assertSame($initial_payload['meta'], $response->getData()['dashboardMeta']);
        $this->assertSame($initial_payload['kpis'], $response->getData()['dashboardKpis']);
    }

    protected function makeUser($can_dashboard_data, $permitted_locations)
    {
        return new class($can_dashboard_data, $permitted_locations) extends User
        {
            protected $canDashboardData;
            protected $permittedLocations;

            public function __construct($can_dashboard_data, $permitted_locations)
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 1;
                $this->canDashboardData = $can_dashboard_data;
                $this->permittedLocations = $permitted_locations;
            }

            public function can($ability, $arguments = [])
            {
                if ($ability === 'dashboard.data') {
                    return $this->canDashboardData;
                }

                return false;
            }

            public function permitted_locations($business_id = null)
            {
                return $this->permittedLocations;
            }
        };
    }

    protected function makeRequest(array $session_data = [], array $query = [])
    {
        $request = Request::create('/home/metronic-dashboard-data', 'GET', $query);
        $session = $this->app['session']->driver();
        $session->start();
        foreach ($session_data as $key => $value) {
            $session->put($key, $value);
        }
        $request->setLaravelSession($session);

        return $request;
    }
}
