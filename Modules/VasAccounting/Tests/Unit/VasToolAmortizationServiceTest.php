<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Modules\VasAccounting\Entities\VasTool;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Services\VasToolAmortizationService;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class VasToolAmortizationServiceTest extends TestCase
{
    public function test_scheduled_amount_uses_original_cost_and_months(): void
    {
        $service = $this->makeService();
        $tool = new VasTool([
            'original_cost' => 1200,
            'remaining_value' => 1200,
            'amortization_months' => 12,
        ]);

        $this->assertSame(100.0, $service->scheduledAmountForTool($tool));
    }

    public function test_scheduled_amount_defaults_to_one_month_when_value_is_invalid(): void
    {
        $service = $this->makeService();
        $tool = new VasTool([
            'original_cost' => 800,
            'remaining_value' => 800,
            'amortization_months' => 0,
        ]);

        $this->assertSame(800.0, $service->scheduledAmountForTool($tool));
    }

    protected function makeService(): VasToolAmortizationService
    {
        return new VasToolAmortizationService(
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(VasPostingService::class)
        );
    }
}
