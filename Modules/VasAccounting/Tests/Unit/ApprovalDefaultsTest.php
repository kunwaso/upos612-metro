<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class ApprovalDefaultsTest extends TestCase
{
    public function test_default_approval_settings_include_native_document_rollout_defaults(): void
    {
        $util = new VasAccountingUtil();
        $defaults = $util->defaultApprovalSettings();

        $this->assertSame('draft', data_get($defaults, 'default_manual_voucher_status'));
        $this->assertFalse(data_get($defaults, 'require_manual_voucher_approval'));
        $this->assertTrue(data_get($defaults, 'native_document_defaults.invoice.requires_approval'));
        $this->assertTrue(data_get($defaults, 'native_document_defaults.payment.requires_rule'));
        $this->assertTrue(config('vasaccounting.feature_flags.payroll'));
        $this->assertNotEmpty(config('vasaccounting.payroll_bridge_adapters.essentials'));
    }
}
