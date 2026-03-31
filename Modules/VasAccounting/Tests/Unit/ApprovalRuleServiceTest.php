<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Services\ApprovalRuleService;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class ApprovalRuleServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_document_family_for_context_uses_source_mapping(): void
    {
        $service = new ApprovalRuleService(Mockery::mock(VasAccountingUtil::class));

        $this->assertSame('payment', $service->documentFamilyForContext(['source_type' => 'native_payment']));
        $this->assertSame('manual', $service->documentFamilyForContext(['source_type' => 'manual']));
    }

    public function test_default_status_uses_manual_voucher_policy_from_business_settings(): void
    {
        $settings = new VasBusinessSetting([
            'approval_settings' => [
                'default_manual_voucher_status' => 'pending_approval',
                'require_manual_voucher_approval' => true,
            ],
        ]);

        $util = Mockery::mock(VasAccountingUtil::class);
        $util->shouldReceive('getOrCreateBusinessSettings')->once()->with(5)->andReturn($settings);

        $service = new ApprovalRuleService($util);

        $this->assertSame('pending_approval', $service->defaultStatus(5, 'manual', ['source_type' => 'manual']));
    }

    public function test_requires_approval_uses_native_defaults_when_no_rule_matches(): void
    {
        $settings = new VasBusinessSetting(['approval_settings' => []]);

        $util = Mockery::mock(VasAccountingUtil::class);
        $util->shouldReceive('getOrCreateBusinessSettings')->once()->with(5)->andReturn($settings);

        $service = Mockery::mock(ApprovalRuleService::class, [$util])->makePartial();
        $service->shouldReceive('resolveRule')->once()->andReturn(null);

        $this->assertTrue($service->requiresApproval(5, 'invoice', ['source_type' => 'native_invoice']));
    }

    public function test_requires_approval_honors_explicit_context_flag(): void
    {
        $service = new ApprovalRuleService(Mockery::mock(VasAccountingUtil::class));

        $this->assertFalse($service->requiresApproval(5, 'payment', ['requires_approval' => false]));
        $this->assertTrue($service->requiresApproval(5, 'payment', ['requires_approval' => true]));
    }
}
