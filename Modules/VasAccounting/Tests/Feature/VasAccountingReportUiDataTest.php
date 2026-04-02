<?php

namespace Modules\VasAccounting\Tests\Feature;

use App\User;
use Mockery;
use Modules\VasAccounting\Http\Controllers\ReportController;
use Modules\VasAccounting\Http\Requests\ReportDatatableRequest;
use Modules\VasAccounting\Services\EnterpriseReportingService;
use Modules\VasAccounting\Services\ReportSnapshotService;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalEscalationDispatchService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class VasAccountingReportUiDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_report_datatable_returns_expected_contract_shape(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.reports.view']));

        $reporting = Mockery::mock(EnterpriseReportingService::class);
        $reporting->shouldReceive('supports')->once()->with('trial_balance')->andReturn(true);
        $reporting->shouldReceive('buildDataset')->once()->andReturn([
            'columns' => ['Account', 'Debit', 'Credit'],
            'rows' => [
                ['1111 Cash', '100.00', '0.00'],
                ['4111 Revenue', '0.00', '100.00'],
                ['1311 Receivable', '75.00', '0.00'],
            ],
        ]);

        $controller = new ReportController(
            $reporting,
            Mockery::mock(ReportSnapshotService::class),
            Mockery::mock(ExpenseApprovalEscalationDispatchService::class)
        );

        $request = $this->makeDatatableRequest([
            'draw' => 3,
            'start' => 0,
            'length' => 2,
            'search' => ['value' => '1'],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]);
        $request->setValidator(validator($request->all(), $request->rules()));

        $response = $controller->datatable($request, 'trial_balance');
        $payload = $response->getData(true);

        $this->assertSame(200, $response->status());
        $this->assertSame(3, $payload['draw']);
        $this->assertArrayHasKey('recordsTotal', $payload);
        $this->assertArrayHasKey('recordsFiltered', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertSame(3, $payload['recordsTotal']);
        $this->assertGreaterThan(0, $payload['recordsFiltered']);
        $this->assertCount(2, $payload['data']);
        $this->assertCount(3, $payload['data'][0]);
    }

    public function test_report_datatable_requires_reports_permission(): void
    {
        $this->actingAs($this->makeUser([]));

        $reporting = Mockery::mock(EnterpriseReportingService::class);
        $reporting->shouldReceive('supports')->never();

        $controller = new ReportController(
            $reporting,
            Mockery::mock(ReportSnapshotService::class),
            Mockery::mock(ExpenseApprovalEscalationDispatchService::class)
        );

        try {
            $controller->datatable($this->makeDatatableRequest(), 'trial_balance');
            $this->fail('Expected 403 for missing vas_accounting.reports.view permission.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_report_datatable_rejects_unknown_report_key(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.reports.view']));

        $reporting = Mockery::mock(EnterpriseReportingService::class);
        $reporting->shouldReceive('supports')->once()->with('unknown_report')->andReturn(false);

        $controller = new ReportController(
            $reporting,
            Mockery::mock(ReportSnapshotService::class),
            Mockery::mock(ExpenseApprovalEscalationDispatchService::class)
        );

        $this->expectException(NotFoundHttpException::class);
        $controller->datatable($this->makeDatatableRequest(), 'unknown_report');
    }

    protected function makeDatatableRequest(array $input = []): ReportDatatableRequest
    {
        $request = ReportDatatableRequest::create('/vas-accounting/ui/reports/trial_balance/datatable', 'GET', $input);
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
