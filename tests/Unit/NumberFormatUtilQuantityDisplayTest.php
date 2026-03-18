<?php

namespace Tests\Unit;

use App\Utils\NumberFormatUtil;
use Tests\TestCase;

class NumberFormatUtilQuantityDisplayTest extends TestCase
{
    public function test_format_quantity_grouped_keeps_integer_trailing_zeros_when_precision_zero(): void
    {
        $util = new NumberFormatUtil();

        $this->assertSame('13,500', $util->formatQuantityGrouped(13500.0, 0, '.', ','));
        $this->assertSame('100', $util->formatQuantityGrouped(100.0, 0, '.', ','));
    }

    public function test_format_quantity_grouped_trims_fractional_zeros_only(): void
    {
        $util = new NumberFormatUtil();

        $this->assertSame('13,500', $util->formatQuantityGrouped(13500.0, 2, '.', ','));
        $this->assertSame('13,500.5', $util->formatQuantityGrouped(13500.5, 2, '.', ','));
    }

    public function test_format_quantity_grouped_european_separators(): void
    {
        $util = new NumberFormatUtil();

        $this->assertSame('13.500', $util->formatQuantityGrouped(13500.0, 2, ',', '.'));
        $this->assertSame('13.500,5', $util->formatQuantityGrouped(13500.5, 2, ',', '.'));
    }

    public function test_format_currency_amount_display_groups_with_us_separators(): void
    {
        $util = new NumberFormatUtil();

        $this->assertSame('12,345.67', $util->formatCurrencyAmountDisplay(12345.67, null));
    }

    public function test_format_currency_code_display_appends_code(): void
    {
        $util = new NumberFormatUtil();

        $this->assertSame('1,000.00 USD', $util->formatCurrencyCodeDisplay(1000.0, null, 'USD'));
        $this->assertSame('500.00', $util->formatCurrencyCodeDisplay(500.0, null, ''));
    }
}
