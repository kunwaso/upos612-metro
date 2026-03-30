<?php

namespace Modules\VasAccounting\Tests\Feature;

use App\User;
use Modules\VasAccounting\Entities\VasReportSnapshot;
use Tests\TestCase;

class VasAccountingShellRenderTest extends TestCase
{
    public function test_header_renders_shared_shell_navigation_and_quick_actions(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.reports.view',
        ]));

        $html = view('vasaccounting::partials.header', [
            'title' => 'Accounting Dashboard',
            'subtitle' => 'Posting health and close readiness.',
            'vasAccountingPageMeta' => [
                'title' => 'Accounting Dashboard',
                'subtitle' => 'Posting health and close readiness.',
                'icon' => 'ki-outline ki-element-11',
                'section_label' => 'Core Ledger',
                'badge_variant' => 'light-primary',
                'supports_location_filter' => true,
                'quick_actions' => [
                    ['route' => 'vasaccounting.reports.index', 'label' => 'Open reports', 'style' => 'light-primary'],
                ],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => [
                'name' => 'FY 2026',
                'status' => 'open',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ],
            'vasAccountingNavConfig' => [
                'navigation_groups' => [
                    [
                        'label' => 'Core Ledger',
                        'badge_variant' => 'light-primary',
                        'items' => [
                            [
                                'route' => 'vasaccounting.dashboard.index',
                                'label' => 'Dashboard',
                                'active' => 'vasaccounting.dashboard.*',
                                'permission' => 'vas_accounting.access',
                                'icon' => 'ki-outline ki-element-11',
                            ],
                        ],
                    ],
                ],
            ],
            'locationOptions' => [5 => 'Main Branch'],
            'selectedLocationId' => 5,
        ])->render();

        $this->assertStringContainsString('Accounting Dashboard', $html);
        $this->assertStringContainsString('Demo Business', $html);
        $this->assertStringContainsString('FY 2026', $html);
        $this->assertStringContainsString('Open reports', $html);
        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('name="location_id"', $html);
    }

    public function test_report_table_renders_dataset_inside_shared_shell(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.reports.view',
        ]));

        $html = view('vasaccounting::reports.table', [
            'title' => 'Trial Balance',
            'columns' => ['Account', 'Debit', 'Credit'],
            'rows' => [
                ['1111 Cash', '100.00', '0.00'],
                ['4111 Revenue', '0.00', '100.00'],
            ],
            'summary' => [
                ['label' => 'Accounts', 'value' => 2],
            ],
            'vasAccountingPageMeta' => [
                'title' => 'Trial Balance',
                'subtitle' => 'Trial balance output.',
                'icon' => 'ki-outline ki-chart-line',
                'section_label' => 'Controls',
                'badge_variant' => 'light-warning',
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
        ])->render();

        $this->assertStringContainsString('Trial Balance', $html);
        $this->assertStringContainsString('Rendered Dataset', $html);
        $this->assertStringContainsString('1111 Cash', $html);
        $this->assertStringContainsString('Back to reports', $html);
    }

    public function test_report_snapshot_renders_snapshot_table_inside_shared_shell(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.reports.view',
        ]));

        $snapshot = new VasReportSnapshot([
            'snapshot_name' => 'March Close Pack',
            'report_key' => 'close_packet',
            'status' => 'ready',
            'generated_at' => now(),
            'payload' => [
                'summary' => [
                    ['label' => 'Rows', 'value' => 1],
                ],
                'columns' => ['Metric', 'Value'],
                'rows' => [
                    ['Open items', '0'],
                ],
            ],
        ]);

        $html = view('vasaccounting::reports.snapshot', [
            'snapshot' => $snapshot,
            'payload' => (array) $snapshot->payload,
            'vasAccountingPageMeta' => [
                'title' => 'Report Snapshot',
                'subtitle' => 'Stored reporting output.',
                'icon' => 'ki-outline ki-document',
                'section_label' => 'Controls',
                'badge_variant' => 'light-warning',
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
        ])->render();

        $this->assertStringContainsString('March Close Pack', $html);
        $this->assertStringContainsString('Snapshot Dataset', $html);
        $this->assertStringContainsString('Open items', $html);
        $this->assertStringContainsString('Back to reports', $html);
    }

    protected function makeUser(array $allowedAbilities): User
    {
        return new class($allowedAbilities) extends User
        {
            protected array $allowedAbilities = [];

            public function __construct(array $allowedAbilities)
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 44;
                $this->allowedAbilities = $allowedAbilities;
            }

            public function hasRole($roles, ?string $guard = null): bool
            {
                return false;
            }

            public function hasPermissionTo($permission, $guardName = null): bool
            {
                return in_array((string) $permission, $this->allowedAbilities, true);
            }

            public function checkPermissionTo($permission, $guardName = null): bool
            {
                return $this->hasPermissionTo($permission, $guardName);
            }

            public function can($ability, $arguments = [])
            {
                return in_array((string) $ability, $this->allowedAbilities, true);
            }
        };
    }
}
