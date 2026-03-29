<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Modules\VasAccounting\Services\CutoverParityService;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use App\Utils\TransactionUtil;
use Tests\TestCase;

class CutoverParityServiceTest extends TestCase
{
    public function test_build_returns_expected_section_keys_when_tables_are_unavailable(): void
    {
        $inventoryValuationService = Mockery::mock(VasInventoryValuationService::class);
        $inventoryValuationService->shouldIgnoreMissing();
        $inventoryValuationService->shouldReceive('summaries')->andReturn(collect());

        $transactionUtil = Mockery::mock(TransactionUtil::class);
        $transactionUtil->shouldIgnoreMissing();
        $transactionUtil->shouldReceive('getSellTotals')->andReturn(['invoice_due' => 0]);
        $transactionUtil->shouldReceive('getPurchaseTotals')->andReturn(['purchase_due' => 0]);
        $transactionUtil->shouldReceive('getOpeningClosingStock')->andReturn(0);

        $service = new CutoverParityService(
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(EnterpriseFinanceReportUtil::class),
            $inventoryValuationService,
            $transactionUtil
        );

        $report = $service->build(1, '2026-03');

        $this->assertSame('2026-03', $report['period']['token']);
        $this->assertCount(5, $report['sections']);
        $this->assertSame(
            ['gl_activity', 'treasury_balance', 'receivables', 'payables', 'inventory_value'],
            collect($report['sections'])->pluck('key')->all()
        );
        $this->assertIsArray($report['branches']);
    }
}
