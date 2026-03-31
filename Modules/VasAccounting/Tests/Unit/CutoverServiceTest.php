<?php

namespace Modules\VasAccounting\Tests\Unit;

use Illuminate\Http\Request;
use Mockery;
use Modules\VasAccounting\Services\CutoverService;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class CutoverServiceTest extends TestCase
{
    public function test_merge_cutover_settings_fills_missing_persona_statuses(): void
    {
        $util = Mockery::mock(VasAccountingUtil::class);
        $util->shouldReceive('defaultCutoverSettings')->once()->andReturn([
            'legacy_routes_mode' => 'observe',
            'hide_legacy_accounting_menu' => false,
            'parallel_run_status' => 'not_started',
            'parallel_run_notes' => null,
            'uat_statuses' => [],
            'last_parity_check_at' => null,
            'last_legacy_redirect_at' => null,
            'family_modes' => [
                'payments' => 'legacy',
                'purchase_ap' => 'legacy',
                'sales_ar' => 'legacy',
                'payroll' => 'bridge',
            ],
        ]);

        $service = new CutoverService($util);
        $merged = $service->mergeCutoverSettings([
            'legacy_routes_mode' => 'redirect',
            'uat_statuses' => ['accountant' => true],
        ]);

        $this->assertSame('redirect', $merged['legacy_routes_mode']);
        $this->assertFalse($merged['hide_legacy_accounting_menu']);
        $this->assertTrue($merged['uat_statuses']['accountant']);
        $this->assertFalse($merged['uat_statuses']['cashier']);
        $this->assertArrayHasKey('finance_manager', $merged['uat_statuses']);
        $this->assertSame('legacy', $merged['family_modes']['payments']);
        $this->assertSame('bridge', $merged['family_modes']['payroll']);
    }

    public function test_legacy_route_mappings_expose_replacement_destinations(): void
    {
        $service = new CutoverService(Mockery::mock(VasAccountingUtil::class));

        $mappings = collect($service->legacyRouteMappings());
        $trialBalance = $mappings->firstWhere('legacy_key', 'trial-balance');
        $accountTypes = $mappings->firstWhere('legacy_key', 'account-types');
        $paymentAccounts = $mappings->firstWhere('legacy_key', 'payment-account');

        $this->assertSame('VAS Trial Balance', $trialBalance['target_label']);
        $this->assertStringContainsString('vas-accounting/reports/trial-balance', $trialBalance['route_url']);
        $this->assertSame('VAS Chart of Accounts', $accountTypes['target_label']);
        $this->assertSame('VAS Cash & Bank', $paymentAccounts['target_label']);
    }

    public function test_merge_rollout_settings_normalizes_branch_ids(): void
    {
        $util = Mockery::mock(VasAccountingUtil::class);
        $util->shouldReceive('defaultRolloutSettings')->once()->andReturn([
            'status' => 'pilot',
            'target_go_live_date' => null,
            'support_owner' => null,
            'training_notes' => null,
            'enabled_branch_ids' => [],
            'enabled_document_families' => [],
            'rollout_notes' => null,
        ]);

        $service = new CutoverService($util);
        $merged = $service->mergeRolloutSettings([
            'status' => 'staged',
            'enabled_branch_ids' => ['1', '', 4, 0, '9'],
            'enabled_document_families' => ['invoice', '', 'payment'],
        ]);

        $this->assertSame('staged', $merged['status']);
        $this->assertSame([1, 4, 9], $merged['enabled_branch_ids']);
        $this->assertSame(['invoice', 'payment'], $merged['enabled_document_families']);
    }

    public function test_legacy_route_action_returns_null_in_observe_mode(): void
    {
        $service = Mockery::mock(CutoverService::class, [Mockery::mock(VasAccountingUtil::class)])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('legacyRoutesMode')
            ->once()
            ->with(1)
            ->andReturn('observe');

        $this->assertNull($service->legacyRouteAction(1, Request::create('/account/trial-balance', 'GET')));
    }

    public function test_legacy_route_action_maps_payment_account_resource_to_vas_cash_bank(): void
    {
        $service = Mockery::mock(CutoverService::class, [Mockery::mock(VasAccountingUtil::class)])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('legacyRoutesMode')
            ->once()
            ->with(9)
            ->andReturn('redirect');

        $action = $service->legacyRouteAction(9, Request::create('/payment-account', 'GET'));

        $this->assertIsArray($action);
        $this->assertSame('redirect', $action['mode']);
        $this->assertSame('VAS Cash & Bank', $action['target_label']);
        $this->assertStringContainsString('vas-accounting/cash-bank', $action['target_url']);
    }
}
