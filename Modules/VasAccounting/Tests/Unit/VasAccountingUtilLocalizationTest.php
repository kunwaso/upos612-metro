<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class VasAccountingUtilLocalizationTest extends TestCase
{
    public function test_localized_period_name_translates_fiscal_year_for_vietnamese_locale(): void
    {
        app()->setLocale('vi');

        $util = new VasAccountingUtil();

        $this->assertSame(
            'Năm tài chính 2026-2027',
            $util->localizedPeriodName('2026-2027 Fiscal Year')
        );
    }

    public function test_localized_period_name_preserves_custom_period_names(): void
    {
        app()->setLocale('vi');

        $util = new VasAccountingUtil();

        $this->assertSame(
            'Kỳ điều chỉnh tháng 03/2026',
            $util->localizedPeriodName('Kỳ điều chỉnh tháng 03/2026')
        );
    }
}
