<?php

namespace Modules\VasAccounting\Tests\Unit;

use Tests\TestCase;

class VnChartConfigTest extends TestCase
{
    public function test_vn_chart_contains_the_full_circular_99_seed_set(): void
    {
        $rows = collect(config('vasaccounting.vn_chart', []));
        $codes = $rows->pluck('code');

        $this->assertGreaterThanOrEqual(180, $rows->count());
        $this->assertTrue($codes->contains('1383'));
        $this->assertTrue($codes->contains('215121'));
        $this->assertTrue($codes->contains('332'));
        $this->assertTrue($codes->contains('33311'));
        $this->assertTrue($codes->contains('82112'));
        $this->assertTrue($codes->contains('911'));
    }

    public function test_default_posting_map_codes_exist_in_seeded_chart(): void
    {
        $codes = collect(config('vasaccounting.vn_chart', []))
            ->pluck('code')
            ->flip();

        foreach ((array) config('vasaccounting.default_posting_map_codes', []) as $postingKey => $code) {
            $this->assertTrue(
                $codes->has($code),
                "Posting map code [{$postingKey}] points to missing chart code [{$code}]."
            );
        }
    }

    public function test_parent_codes_in_seeded_chart_resolve_to_existing_accounts(): void
    {
        $rows = collect(config('vasaccounting.vn_chart', []))
            ->keyBy('code');

        foreach ($rows as $code => $row) {
            $parentCode = $row['parent_code'] ?? null;
            if (! $parentCode) {
                $this->assertSame(1, $row['level'], "Root chart code [{$code}] should remain level 1.");

                continue;
            }

            $this->assertTrue($rows->has($parentCode), "Chart code [{$code}] has missing parent [{$parentCode}].");
            $this->assertSame(
                ((int) $rows->get($parentCode)['level']) + 1,
                (int) $row['level'],
                "Chart code [{$code}] has an invalid level hierarchy."
            );
        }
    }
}
