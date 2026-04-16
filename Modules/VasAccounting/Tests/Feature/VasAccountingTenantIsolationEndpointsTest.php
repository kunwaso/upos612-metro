<?php

namespace Modules\VasAccounting\Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\VasAccounting\Http\Controllers\DashboardController;
use Modules\VasAccounting\Http\Controllers\ReportController;
use Modules\VasAccounting\Services\AccountingJourneyService;
use Modules\VasAccounting\Services\ComplianceProfileService;
use Modules\VasAccounting\Services\EnterpriseReportingService;
use Modules\VasAccounting\Services\ReportSnapshotService;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalEscalationDispatchService;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class VasAccountingTenantIsolationEndpointsTest extends TestCase
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

        Schema::create('vas_accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('vas_accounting_periods');
        Mockery::close();

        parent::tearDown();
    }

    public function test_journey_state_uses_session_business_id_when_cross_tenant_business_id_is_supplied(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.access']));

        $journeyService = Mockery::mock(AccountingJourneyService::class);
        $journeyService->shouldReceive('state')
            ->once()
            ->with(44)
            ->andReturn([
                'steps' => [],
                'summary' => ['completed' => 0, 'total' => 0, 'progress_percent' => 0],
            ]);

        $controller = new DashboardController(
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(VasInventoryValuationService::class),
            $journeyService
        );

        $response = $controller->journeyState(
            $this->makeRequest('/vas-accounting/ui/journey/state', [
                'business_id' => 99,
            ])
        );

        $payload = $response->getData(true);

        $this->assertArrayHasKey('summary', $payload);
    }

    public function test_journey_next_actions_uses_session_business_id_when_cross_tenant_business_id_is_supplied(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.access']));

        $journeyService = Mockery::mock(AccountingJourneyService::class);
        $journeyService->shouldReceive('nextActions')
            ->once()
            ->with(44, 5)
            ->andReturn([
                [
                    'step_key' => 'setup',
                    'label' => 'Setup',
                    'route' => 'vasaccounting.setup.index',
                    'url' => 'http://localhost/vas-accounting/setup',
                    'status' => 'blocked',
                    'reason' => 'Missing setup',
                ],
            ]);

        $controller = new DashboardController(
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(VasInventoryValuationService::class),
            $journeyService
        );

        $response = $controller->journeyNextActions(
            $this->makeRequest('/vas-accounting/ui/journey/next-actions', [
                'business_id' => 99,
            ])
        );

        $payload = $response->getData(true);

        $this->assertCount(1, (array) data_get($payload, 'actions', []));
    }

    public function test_financial_statements_uses_session_business_id_for_dataset_and_period_options(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.reports.view']));

        DB::table('vas_accounting_periods')->insert([
            [
                'id' => 101,
                'business_id' => 44,
                'name' => 'FY2026-Q1',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'id' => 202,
                'business_id' => 99,
                'name' => 'OTHER-FY2026-Q1',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        $reportingService = Mockery::mock(EnterpriseReportingService::class);
        $reportingService->shouldReceive('buildDataset')
            ->once()
            ->with('financial_statements', 44, Mockery::type('array'))
            ->andReturn([
                'title' => 'Financial Statements',
                'columns' => ['Line', 'Description', 'Current', 'Comparative'],
                'rows' => [['A01', 'Total assets', '100', '90']],
                'summary' => [['label' => 'Lines', 'value' => '1']],
                'standard_profile' => 'tt99_2025',
                'statement' => 'balance_sheet',
                'period_id' => null,
                'comparative_period_id' => null,
            ]);
        $reportingService->shouldReceive('reportDefinitions')->andReturn([]);

        $complianceService = Mockery::mock(ComplianceProfileService::class);

        $controller = new ReportController(
            $reportingService,
            Mockery::mock(ReportSnapshotService::class),
            Mockery::mock(ExpenseApprovalEscalationDispatchService::class),
            null,
            $complianceService
        );

        $view = $controller->financialStatements(
            $this->makeRequest('/vas-accounting/reports/financial-statements', [
                'business_id' => 99,
                'statement' => 'balance_sheet',
            ])
        );

        $this->assertSame('vasaccounting::reports.financial_statements', $view->getName());

        $periodOptions = collect($view->getData()['periodOptions'])->pluck('id')->all();
        $this->assertSame([101], $periodOptions);
    }

    protected function makeRequest(string $uri, array $query = []): Request
    {
        $request = Request::create($uri, 'GET', $query);
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('user.business_id', 44);

        return $request;
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
