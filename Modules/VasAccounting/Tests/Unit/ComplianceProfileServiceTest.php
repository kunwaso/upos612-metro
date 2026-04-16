<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Services\ComplianceProfileService;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;
use Tests\TestCase;

class ComplianceProfileServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_active_profile_normalizes_legacy_label_to_tt99_profile_key(): void
    {
        $service = new ComplianceProfileService(Mockery::mock(VasAccountingUtil::class));
        $settings = new VasBusinessSetting([
            'compliance_settings' => [
                'standard' => 'Circular 99/2025/TT-BTC',
                'effective_date' => '2026-01-01',
            ],
        ]);

        $profile = $service->activeProfileForSettings($settings);

        $this->assertSame('tt99_2025', $profile['key']);
        $this->assertSame('2026-01-01', $profile['effective_date']);
    }

    public function test_validate_setup_payload_rejects_tt99_dates_before_2026(): void
    {
        $service = new ComplianceProfileService(Mockery::mock(VasAccountingUtil::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TT99 compliance profile requires effective date on or after 2026-01-01.');

        $service->validateSetupPayload([
            'compliance_settings' => [
                'standard' => 'tt99_2025',
                'effective_date' => '2025-12-31',
                'legacy_bridge_enabled' => false,
            ],
        ]);
    }
}

