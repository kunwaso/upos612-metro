<?php

namespace Modules\VasAccounting\Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\VasAccounting\Http\Controllers\DashboardController;
use Modules\VasAccounting\Http\Requests\DashboardUiDataRequest;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class VasAccountingDashboardUiDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('vas_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_location_id')->nullable();
            $table->string('voucher_no')->nullable();
            $table->string('voucher_type')->nullable();
            $table->string('module_area')->nullable();
            $table->string('status')->nullable();
            $table->date('posting_date')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->decimal('total_debit', 24, 4)->default(0);
            $table->decimal('total_credit', 24, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('vas_posting_failures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_location_id')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('vas_posting_failures');
        Schema::dropIfExists('vas_vouchers');
        Mockery::close();

        parent::tearDown();
    }

    public function test_kpis_returns_contract_shape_with_location_scoped_metrics(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.access']));
        $this->mockDashboardDependencies();

        DB::table('vas_vouchers')->insert([
            [
                'business_id' => 44,
                'business_location_id' => 2,
                'voucher_no' => 'JV-1',
                'status' => 'posted',
                'posting_date' => now()->toDateString(),
                'posted_at' => now()->toDateTimeString(),
                'total_debit' => 120.50,
                'total_credit' => 120.50,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'business_id' => 44,
                'business_location_id' => 9,
                'voucher_no' => 'JV-2',
                'status' => 'posted',
                'posting_date' => now()->toDateString(),
                'posted_at' => now()->toDateTimeString(),
                'total_debit' => 340.25,
                'total_credit' => 340.25,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'business_id' => 44,
                'business_location_id' => 2,
                'voucher_no' => 'JV-3',
                'status' => 'posted',
                'posting_date' => now()->subMonthNoOverflow()->toDateString(),
                'posted_at' => now()->subMonthNoOverflow()->toDateTimeString(),
                'total_debit' => 50,
                'total_credit' => 50,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        DB::table('vas_posting_failures')->insert([
            [
                'business_id' => 44,
                'business_location_id' => 2,
                'source_type' => 'voucher',
                'source_id' => 1,
                'error_message' => 'Location 2 outstanding failure',
                'resolved_at' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'business_id' => 44,
                'business_location_id' => 9,
                'source_type' => 'voucher',
                'source_id' => 2,
                'error_message' => 'Other location failure',
                'resolved_at' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        $controller = $this->app->make(DashboardController::class);
        $response = $controller->kpis($this->makeDashboardRequest(['location_id' => 2]));

        $this->assertSame(200, $response->status());
        $payload = $response->getData(true);

        $this->assertArrayHasKey('cards', $payload);
        $this->assertArrayHasKey('updated_at', $payload);
        $this->assertCount(4, $payload['cards']);

        $cards = collect($payload['cards'])->keyBy('key');
        $this->assertSame('1', (string) data_get($cards, 'posted_this_month.value'));
        $this->assertSame('1', (string) data_get($cards, 'posting_failures.value'));

        foreach ($payload['cards'] as $card) {
            $this->assertArrayHasKey('key', $card);
            $this->assertArrayHasKey('label', $card);
            $this->assertArrayHasKey('value', $card);
            $this->assertArrayHasKey('delta', $card);
            $this->assertArrayHasKey('direction', $card);
            $this->assertArrayHasKey('hint', $card);
            $this->assertArrayHasKey('icon', $card);
            $this->assertArrayHasKey('badgeVariant', $card);
        }
    }

    public function test_trends_and_failures_return_location_scoped_payloads(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.access']));
        $this->mockDashboardDependencies();

        DB::table('vas_vouchers')->insert([
            [
                'business_id' => 44,
                'business_location_id' => 2,
                'voucher_no' => 'TR-1',
                'status' => 'posted',
                'posting_date' => now()->toDateString(),
                'posted_at' => now()->toDateTimeString(),
                'total_debit' => 210.75,
                'total_credit' => 210.75,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'business_id' => 44,
                'business_location_id' => 9,
                'voucher_no' => 'TR-2',
                'status' => 'posted',
                'posting_date' => now()->toDateString(),
                'posted_at' => now()->toDateTimeString(),
                'total_debit' => 999.99,
                'total_credit' => 999.99,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        DB::table('vas_posting_failures')->insert([
            [
                'business_id' => 44,
                'business_location_id' => 2,
                'source_type' => 'voucher',
                'source_id' => 88,
                'error_message' => 'Scoped failure',
                'resolved_at' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'business_id' => 44,
                'business_location_id' => 9,
                'source_type' => 'voucher',
                'source_id' => 89,
                'error_message' => 'Out-of-scope failure',
                'resolved_at' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        $controller = $this->app->make(DashboardController::class);
        $trendPayload = $controller->trends($this->makeDashboardRequest(['location_id' => 2, 'range' => 'month']))->getData(true);
        $failurePayload = $controller->failures($this->makeDashboardRequest(['location_id' => 2]))->getData(true);

        $this->assertArrayHasKey('labels', $trendPayload);
        $this->assertArrayHasKey('series', $trendPayload);
        $this->assertArrayHasKey('meta', $trendPayload);
        $this->assertCount(2, $trendPayload['series']);
        $this->assertSame('month', data_get($trendPayload, 'meta.range'));
        $this->assertSame(2, data_get($trendPayload, 'meta.location_id'));
        $this->assertGreaterThan(0, array_sum((array) data_get($trendPayload, 'series.0.data', [])));

        $this->assertArrayHasKey('failures', $failurePayload);
        $this->assertArrayHasKey('updated_at', $failurePayload);
        $this->assertCount(1, $failurePayload['failures']);
        $this->assertSame('voucher:88', data_get($failurePayload, 'failures.0.source'));
    }

    public function test_dashboard_ui_endpoints_require_access_permission(): void
    {
        $this->actingAs($this->makeUser([]));
        $this->mockDashboardDependencies();

        $controller = $this->app->make(DashboardController::class);

        try {
            $controller->kpis($this->makeDashboardRequest(['location_id' => 2]));
            $this->fail('Expected 403 for missing vas_accounting.access permission.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    protected function makeDashboardRequest(array $query = []): DashboardUiDataRequest
    {
        $request = DashboardUiDataRequest::create('/vas-accounting/ui/dashboard/kpis', 'GET', $query);
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('user.business_id', 44);

        return $request;
    }

    protected function mockDashboardDependencies(): void
    {
        $util = Mockery::mock(VasAccountingUtil::class);
        $util->shouldReceive('dashboardMetrics')
            ->andReturn([
                'openPeriods' => 3,
                'postingFailures' => 0,
                'postedThisMonth' => 0,
            ]);
        $util->shouldReceive('metricLabel')
            ->andReturnUsing(static function (string $metric): string {
                return match ($metric) {
                    'open_periods' => 'Open periods',
                    'posting_failures' => 'Posting failures',
                    'inventory_value' => 'Inventory value',
                    'posted_this_month' => 'Posted this month',
                    default => $metric,
                };
            });
        $this->app->instance(VasAccountingUtil::class, $util);

        $inventoryService = Mockery::mock(VasInventoryValuationService::class);
        $inventoryService->shouldReceive('totals')
            ->andReturn(['inventory_value' => 1234.56]);
        $this->app->instance(VasInventoryValuationService::class, $inventoryService);
    }

    protected function makeUser(array $allowedAbilities): User
    {
        return new class($allowedAbilities) extends User
        {
            protected array $allowedAbilities = [];

            public function __construct(array $allowedAbilities)
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 44;
                $this->allowedAbilities = $allowedAbilities;
            }

            public function hasRole($roles, ?string $guard = null): bool
            {
                return false;
            }

            public function hasPermissionTo($permission, $guardName = null): bool
            {
                return in_array((string) $permission, $this->allowedAbilities, true);
            }

            public function checkPermissionTo($permission, $guardName = null): bool
            {
                return $this->hasPermissionTo($permission, $guardName);
            }

            public function can($ability, $arguments = [])
            {
                return in_array((string) $ability, $this->allowedAbilities, true);
            }
        };
    }
}
