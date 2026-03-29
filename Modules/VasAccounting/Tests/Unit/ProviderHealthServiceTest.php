<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Services\ProviderHealthService;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class ProviderHealthServiceTest extends TestCase
{
    public function test_provider_health_reflects_registered_profiles(): void
    {
        $util = Mockery::mock(VasAccountingUtil::class);
        $util->shouldReceive('getOrCreateBusinessSettings')
            ->once()
            ->with(7)
            ->andReturn(new VasBusinessSetting([
                'integration_settings' => [
                    'bank_statement_provider' => 'manual',
                    'tax_export_provider' => 'local',
                    'payroll_bridge_provider' => 'essentials',
                ],
                'einvoice_settings' => [
                    'provider' => 'sandbox',
                ],
            ]));

        $service = new ProviderHealthService($util);
        $rows = collect($service->healthForBusiness(7))->keyBy('domain');

        $this->assertFalse($rows['bank_statement_import']['ready']);
        $this->assertFalse($rows['tax_export']['ready']);
        $this->assertFalse($rows['einvoice']['ready']);
        $this->assertTrue($rows['payroll_bridge']['ready']);
    }
}
