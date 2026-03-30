<?php

namespace Modules\VasAccounting\Tests\Feature;

use Tests\TestCase;

class VasAccountingPageRenderContractTest extends TestCase
{
    /**
     * @dataProvider representativePageProvider
     */
    public function test_page_registry_contains_representative_pages_with_shell_metadata(
        string $routeName,
        string $expectedSection,
        bool $supportsLocationFilter
    ): void {
        $registry = (array) config('vasaccounting.page_registry', []);

        $this->assertArrayHasKey($routeName, $registry);
        $this->assertNotEmpty($registry[$routeName]['title'] ?? null);
        $this->assertNotEmpty($registry[$routeName]['icon'] ?? null);
        $this->assertSame($expectedSection, $registry[$routeName]['section_group'] ?? null);
        $this->assertSame($supportsLocationFilter, (bool) ($registry[$routeName]['supports_location_filter'] ?? false));
    }

    public function representativePageProvider(): array
    {
        return [
            'core dashboard' => ['vasaccounting.dashboard.index', 'core', true],
            'operations cash bank' => ['vasaccounting.cash_bank.index', 'operations', true],
            'operations assets' => ['vasaccounting.assets.index', 'operations', true],
            'planning contracts' => ['vasaccounting.contracts.index', 'planning', true],
            'planning budgets' => ['vasaccounting.budgets.index', 'planning', false],
            'controls cutover' => ['vasaccounting.cutover.index', 'controls', true],
            'controls reports' => ['vasaccounting.reports.index', 'controls', false],
        ];
    }
}
