<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Entities\VasBudgetLine;
use Modules\VasAccounting\Services\BudgetControlService;
use Tests\TestCase;

class BudgetControlServiceTest extends TestCase
{
    public function test_variance_snapshot_flags_over_budget_lines(): void
    {
        $service = new BudgetControlService();
        $snapshot = $service->varianceSnapshot(new VasBudgetLine([
            'budget_amount' => 1000,
            'committed_amount' => 300,
            'actual_amount' => 800,
        ]));

        $this->assertTrue($snapshot['is_over_budget']);
        $this->assertSame(-100.0, $snapshot['remaining_amount']);
        $this->assertSame(200.0, $snapshot['variance_amount']);
    }
}
