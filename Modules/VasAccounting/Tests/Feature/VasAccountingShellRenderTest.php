<?php

namespace Modules\VasAccounting\Tests\Feature;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalInstance;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalStep;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasCloseChecklist;
use Modules\VasAccounting\Entities\VasReportSnapshot;
use Tests\TestCase;

class VasAccountingShellRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

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
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

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
            'actions' => [
                [
                    'label' => 'Open escalated approvals',
                    'url' => route('vasaccounting.expenses.index', ['focus' => 'escalated_approvals']),
                    'style' => 'light-primary',
                    'method' => 'GET',
                ],
                [
                    'label' => 'Retry failed dispatches',
                    'url' => route('vasaccounting.reports.expense_escalation_audit.retry_failed_dispatches'),
                    'style' => 'light-warning',
                    'method' => 'POST',
                    'confirm' => 'Retry all failed escalation dispatches from this audit report?',
                ],
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
        $this->assertStringContainsString(__('vasaccounting::lang.views.report_table.dataset_title'), $html);
        $this->assertStringContainsString('1111 Cash', $html);
        $this->assertStringContainsString('Open escalated approvals', $html);
        $this->assertStringContainsString('Retry failed dispatches', $html);
        $this->assertStringContainsString('Back to reports', $html);
    }

    public function test_report_table_renders_procurement_ownership_sections_inside_shared_shell(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.reports.view',
        ]));
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $html = view('vasaccounting::reports.table', [
            'title' => 'Procurement Discrepancy Ownership',
            'columns' => ['Invoice', 'Supplier', 'Queue Status', 'Owner', 'Owner Age', 'Severity', 'Code', 'Match Summary'],
            'rows' => [
                ['AP-00043', 'Acme Supplier', 'In Review', 'Tran Procurement Lead', '8 days', 'BLOCKING', 'amount_variance_exceeded', 'Blocked | B2 / W1'],
            ],
            'summary' => [
                ['label' => 'Unassigned', 'value' => 1],
                ['label' => 'Aged > 7 days', 'value' => 1],
                ['label' => 'Older than 14 days', 'value' => 0],
                ['label' => 'Reassignment loopbacks (14 days)', 'value' => 1],
                ['label' => 'Churned documents (14 days)', 'value' => 1],
                ['label' => 'Loopback hotspot documents (14 days)', 'value' => 1],
                ['label' => 'Reason-volatile documents (14 days)', 'value' => 1],
                ['label' => 'Critical reassignment hotspots (14 days)', 'value' => 1],
            ],
            'sections' => [
                [
                    'title' => 'Assignment Aging Trend',
                    'subtitle' => 'Weekly backlog aging trend based on current unresolved discrepancies and when ownership was last assigned.',
                    'columns' => ['Assignment Week', 'Open Rows', 'In Review', 'Aged > 7 Days', 'Aged > 14 Days'],
                    'rows' => [
                        ['2026-03-24', '2', '1', '1', '0'],
                    ],
                ],
                [
                    'title' => 'Owner Aging Trend',
                    'subtitle' => 'Weekly aging trend for the busiest assignee queues so managers can see whose backlog is getting older.',
                    'columns' => ['Owner', 'Assignment Week', 'Open Rows', 'In Review', 'Aged > 7 Days', 'Aged > 14 Days'],
                    'rows' => [
                        ['Tran Procurement Lead', '2026-03-24', '2', '1', '1', '0'],
                    ],
                ],
                [
                    'title' => 'Owner Aging Mix',
                    'subtitle' => 'See where procurement mismatch follow-up is drifting by owner and queue age.',
                    'columns' => ['Owner', 'Aging Bucket', 'Rows'],
                    'rows' => [
                        ['Tran Procurement Lead', '8+ days', '1'],
                    ],
                ],
                [
                    'title' => 'Stale Owner Backlog',
                    'subtitle' => 'Owners currently holding discrepancies older than two days, ranked by stale queue size.',
                    'columns' => ['Owner', 'Open Discrepancies', 'Aged > 2 Days', 'Aged > 7 Days'],
                    'rows' => [
                        ['Tran Procurement Lead', '2', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Unassigned Discrepancies',
                    'subtitle' => 'Exceptions still waiting for an explicit owner assignment.',
                    'columns' => ['Invoice', 'Supplier', 'Severity', 'Code', 'Match Summary'],
                    'rows' => [
                        ['AP-00044', 'Acme Supplier', 'BLOCKING', 'Quantity Variance Exceeded', 'Blocked | B1 / W0'],
                    ],
                ],
                [
                    'title' => 'Ownership Activity Trend',
                    'subtitle' => 'Daily ownership, reassignment, and resolution flow over the last fourteen days from the canonical audit stream.',
                    'columns' => ['Date', 'Claimed', 'Reassigned', 'Resolved'],
                    'rows' => [
                        ['2026-04-01', '1', '0', '1'],
                    ],
                ],
                [
                    'title' => 'Reassignment Trend by Reviewer',
                    'subtitle' => 'Daily procurement discrepancy reassignments grouped by the reviewer who moved the work.',
                    'columns' => ['Reviewer', 'Date', 'Reassignments', 'Documents'],
                    'rows' => [
                        ['Tran Procurement Lead', '2026-04-02', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Reassignment Trend by Assignee',
                    'subtitle' => 'Daily procurement discrepancy reassignments grouped by the assignee receiving the work.',
                    'columns' => ['Assignee', 'Date', 'Reassignments', 'Documents'],
                    'rows' => [
                        ['Le Vendor Review', '2026-04-02', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Reassignment Path Mix',
                    'subtitle' => 'See how procurement discrepancy ownership is moving between previous and new owners across the last fourteen days.',
                    'columns' => ['Previous Owner', 'New Owner', 'Reassignments', 'Documents'],
                    'rows' => [
                        ['Tran Procurement Lead', 'Le Vendor Review', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Reassignment Reason Mix',
                    'subtitle' => 'Top stated reasons for procurement discrepancy reassignments across the last fourteen days.',
                    'columns' => ['Reason', 'Reassignments', 'Documents', 'Reviewers'],
                    'rows' => [
                        ['Moved to vendor specialist', '1', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Reviewer-Assignee Reassignment Matrix',
                    'subtitle' => 'Cross-matrix of which reviewers are routing procurement discrepancies to which assignees.',
                    'columns' => ['Reviewer', 'Assignee', 'Reassignments', 'Documents', 'Reason Types'],
                    'rows' => [
                        ['Tran Procurement Lead', 'Le Vendor Review', '1', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Document Reassignment Churn',
                    'subtitle' => 'Document-level view of reassignment volume and loopback risk in the procurement discrepancy queue.',
                    'columns' => ['Document', 'Current Owner', 'Reassignments', 'Loopbacks', 'Reviewers', 'Reason Types'],
                    'rows' => [
                        ['AP-00043', 'Le Vendor Review', '3', '1', '2', '2'],
                    ],
                ],
                [
                    'title' => 'Loopback Hotspots',
                    'subtitle' => 'Documents that looped back to prior owners, signaling repeated handoff risk in the discrepancy queue.',
                    'columns' => ['Document', 'Loopbacks', 'Reassignments', 'Current Owner', 'Reviewers', 'Reason Types'],
                    'rows' => [
                        ['AP-00043', '1', '3', 'Le Vendor Review', '2', '2'],
                    ],
                ],
                [
                    'title' => 'Reason Volatility Hotspots',
                    'subtitle' => 'Documents reassigned under multiple reasons or reviewers, signaling inconsistent triage rationale.',
                    'columns' => ['Document', 'Reason Types', 'Reviewers', 'Reassignments', 'Loopbacks', 'Current Owner'],
                    'rows' => [
                        ['AP-00043', '2', '2', '3', '1', 'Le Vendor Review'],
                    ],
                ],
                [
                    'title' => 'Critical Reassignment Hotspots',
                    'subtitle' => 'High-churn documents with loopback or triage volatility signals that need active escalation.',
                    'columns' => ['Document', 'Risk Flags', 'Reassignments', 'Loopbacks', 'Reviewers', 'Reason Types', 'Current Owner'],
                    'rows' => [
                        ['AP-00043', 'High churn, Loopback, Multi-reviewer, Multi-reason', '3', '1', '2', '2', 'Le Vendor Review'],
                    ],
                ],
                [
                    'title' => 'Reviewer Throughput',
                    'subtitle' => 'See which reviewers are actively taking ownership, reassigning, or resolving procurement discrepancies.',
                    'columns' => ['Reviewer', 'Claimed', 'Reassigned', 'Resolved', 'Total Actions'],
                    'rows' => [
                        ['Tran Procurement Lead', '1', '1', '1', '3'],
                    ],
                ],
            ],
            'actions' => [
                [
                    'label' => 'Open discrepancy queue',
                    'url' => route('vasaccounting.procurement.index', ['focus' => 'discrepancy_queue']),
                    'style' => 'light-danger',
                    'method' => 'GET',
                ],
                [
                    'label' => 'Assign unassigned to me',
                    'url' => route('vasaccounting.reports.procurement_discrepancy_ownership.assign_unassigned_to_me'),
                    'style' => 'light-primary',
                    'method' => 'POST',
                ],
            ],
            'reportManagement' => [
                'title' => 'Ownership controls',
                'subtitle' => 'Assign every currently unassigned procurement discrepancy to the selected reviewer without leaving the report.',
                'owner_label' => 'Assign unassigned backlog to',
                'owner_placeholder' => 'Select owner',
                'assign_label' => 'Assign unassigned',
                'route' => route('vasaccounting.reports.procurement_discrepancy_ownership.assign_unassigned'),
                'owner_options' => [
                    9 => 'Tran Procurement Lead',
                    14 => 'Le Vendor Review',
                ],
            ],
            'vasAccountingPageMeta' => [
                'title' => 'Procurement Discrepancy Ownership',
                'subtitle' => 'Ownership, queue aging, and assignee backlog for procurement discrepancies.',
                'icon' => 'ki-outline ki-delivery',
                'section_label' => 'Operations',
                'badge_variant' => 'light-warning',
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
        ])->render();

        $this->assertStringContainsString('Procurement Discrepancy Ownership', $html);
        $this->assertStringContainsString('Assignment Aging Trend', $html);
        $this->assertStringContainsString('Owner Aging Trend', $html);
        $this->assertStringContainsString('Owner Aging Mix', $html);
        $this->assertStringContainsString('Stale Owner Backlog', $html);
        $this->assertStringContainsString('Unassigned Discrepancies', $html);
        $this->assertStringContainsString('Ownership Activity Trend', $html);
        $this->assertStringContainsString('Reassignment Trend by Reviewer', $html);
        $this->assertStringContainsString('Reassignment Trend by Assignee', $html);
        $this->assertStringContainsString('Reassignment Path Mix', $html);
        $this->assertStringContainsString('Reassignment Reason Mix', $html);
        $this->assertStringContainsString('Reviewer-Assignee Reassignment Matrix', $html);
        $this->assertStringContainsString('Document Reassignment Churn', $html);
        $this->assertStringContainsString('Reassignment loopbacks (14 days)', $html);
        $this->assertStringContainsString('Churned documents (14 days)', $html);
        $this->assertStringContainsString('Loopback hotspot documents (14 days)', $html);
        $this->assertStringContainsString('Loopback Hotspots', $html);
        $this->assertStringContainsString('Reason-volatile documents (14 days)', $html);
        $this->assertStringContainsString('Reason Volatility Hotspots', $html);
        $this->assertStringContainsString('Critical reassignment hotspots (14 days)', $html);
        $this->assertStringContainsString('Critical Reassignment Hotspots', $html);
        $this->assertStringContainsString('Reviewer Throughput', $html);
        $this->assertStringContainsString('Ownership controls', $html);
        $this->assertStringContainsString('Assign unassigned backlog to', $html);
        $this->assertStringContainsString('Select owner', $html);
        $this->assertStringContainsString('Le Vendor Review', $html);
        $this->assertStringContainsString('Open discrepancy queue', $html);
        $this->assertStringContainsString('Assign unassigned to me', $html);
        $this->assertStringContainsString('Assign unassigned', $html);
        $this->assertStringContainsString('Back to reports', $html);
    }

    public function test_report_snapshot_renders_snapshot_table_inside_shared_shell(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.reports.view',
        ]));
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

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
        $this->assertStringContainsString(__('vasaccounting::lang.views.report_snapshot.dataset_title'), $html);
        $this->assertStringContainsString('Open items', $html);
        $this->assertStringContainsString('Back to reports', $html);
    }

    public function test_report_table_renders_close_packet_procurement_activity_sections_inside_shared_shell(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.reports.view',
        ]));
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $html = view('vasaccounting::reports.table', [
            'title' => 'Close Packet - March 2026',
            'columns' => ['Checklist', 'Status', 'Notes'],
            'rows' => [
                ['Procurement workflow and matching cleared', 'Blocked', 'Receiving backlog still open'],
            ],
            'summary' => [
                ['label' => 'Procurement ownership actions', 'value' => 4],
                ['label' => 'Procurement reassignments', 'value' => 2],
                ['label' => 'Procurement reassignment loopbacks', 'value' => 1],
                ['label' => 'Procurement churned documents', 'value' => 1],
                ['label' => 'Procurement loopback hotspot documents', 'value' => 1],
                ['label' => 'Procurement reason-volatile documents', 'value' => 1],
                ['label' => 'Procurement critical hotspots', 'value' => 1],
                ['label' => 'Active procurement reviewers', 'value' => 2],
                ['label' => 'Procurement discrepancies aged > 14 days', 'value' => 1],
            ],
            'sections' => [
                [
                    'title' => 'Procurement Ownership Actions by Reviewer',
                    'subtitle' => 'Reviewer-level rollup of claimed, reassigned, and resolved procurement discrepancy actions recorded during the close window.',
                    'columns' => ['Reviewer', 'Claimed', 'Reassigned', 'Resolved', 'Documents', 'Total Actions'],
                    'rows' => [
                        ['Tran Procurement Lead', '1', '1', '1', '2', '3'],
                    ],
                ],
                [
                    'title' => 'Procurement Ownership Actions by Assignee',
                    'subtitle' => 'Assignee-level rollup of received and resolved procurement discrepancy work recorded during the close window.',
                    'columns' => ['Assignee', 'Received', 'Resolved', 'Documents', 'Total Actions'],
                    'rows' => [
                        ['Le Vendor Review', '2', '1', '2', '3'],
                    ],
                ],
                [
                    'title' => 'Procurement Ownership Activity Trend',
                    'subtitle' => 'Daily claimed, reassigned, and resolved procurement discrepancy activity across the close window.',
                    'columns' => ['Date', 'Claimed', 'Reassigned', 'Resolved'],
                    'rows' => [
                        ['2026-04-02', '1', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Procurement Ownership Activity',
                    'subtitle' => 'Canonical audit history for procurement discrepancy ownership actions recorded during the close period.',
                    'columns' => ['Date/Time', 'Reviewer', 'Action', 'Document', 'Reason'],
                    'rows' => [
                        ['2026-04-02 09:15', 'Tran Procurement Lead', 'Discrepancy Reassigned', 'AP-00043', 'Moved to vendor specialist'],
                    ],
                ],
                [
                    'title' => 'Procurement Reassignment History',
                    'subtitle' => 'Track procurement discrepancy owner handoffs during the close period, including reviewer and rationale.',
                    'columns' => ['Date/Time', 'Document', 'Previous Owner', 'New Owner', 'Reviewer', 'Reason'],
                    'rows' => [
                        ['2026-04-02 09:15', 'AP-00043', 'Tran Procurement Lead', 'Le Vendor Review', 'Tran Procurement Lead', 'Moved to vendor specialist'],
                    ],
                ],
                [
                    'title' => 'Procurement Reassignment Path Mix',
                    'subtitle' => 'Route-level summary of how procurement discrepancy ownership moved between previous and new owners during close.',
                    'columns' => ['Previous Owner', 'New Owner', 'Reassignments', 'Documents'],
                    'rows' => [
                        ['Tran Procurement Lead', 'Le Vendor Review', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Procurement Reassignment Trend by Reviewer',
                    'subtitle' => 'Daily procurement discrepancy reassignments grouped by the reviewer who moved the work during close.',
                    'columns' => ['Reviewer', 'Date', 'Reassignments', 'Documents'],
                    'rows' => [
                        ['Tran Procurement Lead', '2026-04-02', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Procurement Reassignment Trend by Assignee',
                    'subtitle' => 'Daily procurement discrepancy reassignments grouped by the assignee receiving work during close.',
                    'columns' => ['Assignee', 'Date', 'Reassignments', 'Documents'],
                    'rows' => [
                        ['Le Vendor Review', '2026-04-02', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Procurement Reassignment Reason Mix',
                    'subtitle' => 'Top stated reasons for procurement discrepancy reassignment decisions taken during close.',
                    'columns' => ['Reason', 'Reassignments', 'Documents', 'Reviewers'],
                    'rows' => [
                        ['Moved to vendor specialist', '1', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Procurement Reviewer-Assignee Reassignment Matrix',
                    'subtitle' => 'Cross-matrix of reviewers routing procurement discrepancies to assignees during close.',
                    'columns' => ['Reviewer', 'Assignee', 'Reassignments', 'Documents', 'Reason Types'],
                    'rows' => [
                        ['Tran Procurement Lead', 'Le Vendor Review', '1', '1', '1'],
                    ],
                ],
                [
                    'title' => 'Procurement Reassignment Churn',
                    'subtitle' => 'Document-level view of procurement reassignment volume and loopback risk during close.',
                    'columns' => ['Document', 'Current Owner', 'Reassignments', 'Loopbacks', 'Reviewers', 'Reason Types'],
                    'rows' => [
                        ['AP-00043', 'Le Vendor Review', '3', '1', '2', '2'],
                    ],
                ],
                [
                    'title' => 'Procurement Loopback Hotspots',
                    'subtitle' => 'Documents that looped back to prior owners during close, signaling repeated reassignment risk.',
                    'columns' => ['Document', 'Loopbacks', 'Reassignments', 'Current Owner', 'Reviewers', 'Reason Types'],
                    'rows' => [
                        ['AP-00043', '1', '3', 'Le Vendor Review', '2', '2'],
                    ],
                ],
                [
                    'title' => 'Procurement Reason Volatility Hotspots',
                    'subtitle' => 'Documents reassigned under multiple reasons or reviewers during close, signaling triage inconsistency.',
                    'columns' => ['Document', 'Reason Types', 'Reviewers', 'Reassignments', 'Loopbacks', 'Current Owner'],
                    'rows' => [
                        ['AP-00043', '2', '2', '3', '1', 'Le Vendor Review'],
                    ],
                ],
                [
                    'title' => 'Procurement Critical Reassignment Hotspots',
                    'subtitle' => 'High-churn procurement discrepancy documents with loopback or triage volatility signals during close.',
                    'columns' => ['Document', 'Risk Flags', 'Reassignments', 'Loopbacks', 'Reviewers', 'Reason Types', 'Current Owner'],
                    'rows' => [
                        ['AP-00043', 'High churn, Loopback, Multi-reviewer, Multi-reason', '3', '1', '2', '2', 'Le Vendor Review'],
                    ],
                ],
                [
                    'title' => 'Procurement Ownership Aging Trend',
                    'subtitle' => 'Weekly backlog aging trend for unresolved procurement discrepancies assigned during the close window horizon.',
                    'columns' => ['Assignment Week', 'Open Rows', 'In Review', 'Aged > 7 Days', 'Aged > 14 Days'],
                    'rows' => [
                        ['2026-03-24', '2', '1', '1', '0'],
                    ],
                ],
                [
                    'title' => 'Procurement Ownership Aging Trend by Owner',
                    'subtitle' => 'Export-oriented owner breakdown for the busiest procurement discrepancy queues in the close window.',
                    'columns' => ['Owner', 'Assignment Week', 'Open Rows', 'In Review', 'Aged > 7 Days', 'Aged > 14 Days'],
                    'rows' => [
                        ['Tran Procurement Lead', '2026-03-24', '2', '1', '1', '0'],
                    ],
                ],
            ],
            'actions' => [],
            'vasAccountingPageMeta' => [
                'title' => 'Close Packet - March 2026',
                'subtitle' => 'Period-close checklist and blocker pack.',
                'icon' => 'ki-outline ki-abstract-26',
                'section_label' => 'Operations',
                'badge_variant' => 'light-primary',
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
        ])->render();

        $this->assertStringContainsString('Close Packet - March 2026', $html);
        $this->assertStringContainsString('Active procurement reviewers', $html);
        $this->assertStringContainsString('Procurement reassignment loopbacks', $html);
        $this->assertStringContainsString('Procurement churned documents', $html);
        $this->assertStringContainsString('Procurement loopback hotspot documents', $html);
        $this->assertStringContainsString('Procurement reason-volatile documents', $html);
        $this->assertStringContainsString('Procurement critical hotspots', $html);
        $this->assertStringContainsString('Procurement Ownership Actions by Reviewer', $html);
        $this->assertStringContainsString('Procurement Ownership Actions by Assignee', $html);
        $this->assertStringContainsString('Procurement Ownership Activity Trend', $html);
        $this->assertStringContainsString('Procurement Ownership Activity', $html);
        $this->assertStringContainsString('Procurement Reassignment History', $html);
        $this->assertStringContainsString('Procurement Reassignment Path Mix', $html);
        $this->assertStringContainsString('Procurement Reassignment Trend by Reviewer', $html);
        $this->assertStringContainsString('Procurement Reassignment Trend by Assignee', $html);
        $this->assertStringContainsString('Procurement Reassignment Reason Mix', $html);
        $this->assertStringContainsString('Procurement Reviewer-Assignee Reassignment Matrix', $html);
        $this->assertStringContainsString('Procurement Reassignment Churn', $html);
        $this->assertStringContainsString('Procurement Loopback Hotspots', $html);
        $this->assertStringContainsString('Procurement Reason Volatility Hotspots', $html);
        $this->assertStringContainsString('Procurement Critical Reassignment Hotspots', $html);
        $this->assertStringContainsString('Procurement Ownership Aging Trend', $html);
        $this->assertStringContainsString('Procurement Ownership Aging Trend by Owner', $html);
        $this->assertStringContainsString('Moved to vendor specialist', $html);
        $this->assertStringContainsString('Le Vendor Review', $html);
        $this->assertStringContainsString('Back to reports', $html);
    }

    public function test_closing_board_renders_expense_escalation_panel(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.close.manage',
        ]));
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $period = new VasAccountingPeriod([
            'id' => 7,
            'name' => 'March 2026',
            'status' => 'open',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $html = view('vasaccounting::closing.index', [
            'periods' => collect([$period]),
            'blockers' => [
                7 => [
                    'draft_vouchers' => 0,
                    'posting_failures' => 0,
                    'pending_depreciation' => 0,
                    'unreconciled_bank_lines' => 0,
                    'pending_treasury_documents' => 0,
                    'pending_procurement_documents' => 1,
                    'receiving_procurement_documents' => 1,
                    'matching_procurement_documents' => 1,
                    'pending_expense_documents' => 1,
                    'outstanding_expense_documents' => 1,
                    'escalated_expense_approvals' => 1,
                    'pending_approvals' => 0,
                ],
            ],
            'checklists' => [
                7 => collect([
                    new VasCloseChecklist([
                        'title' => 'Expense advances and claims resolved',
                        'status' => 'blocked',
                    ]),
                ]),
            ],
            'treasuryInsights' => [
                7 => [
                    'pending_documents' => collect(),
                    'exceptions' => collect(),
                ],
            ],
            'procurementInsights' => [
                7 => [
                    'pending_documents' => collect([
                        new FinanceDocument([
                            'id' => 41,
                            'document_no' => 'PR-00041',
                            'document_type' => 'purchase_requisition',
                            'workflow_status' => 'approved',
                            'accounting_status' => 'not_ready',
                            'gross_amount' => 1500000,
                            'currency_code' => 'VND',
                            'document_date' => now(),
                        ]),
                    ]),
                    'receiving_documents' => collect([
                        tap(new FinanceDocument([
                            'id' => 42,
                            'document_no' => 'PO-00042',
                            'document_type' => 'purchase_order',
                            'workflow_status' => 'partially_received',
                            'accounting_status' => 'not_ready',
                            'gross_amount' => 2500000,
                            'currency_code' => 'VND',
                            'document_date' => now(),
                        ]), function (FinanceDocument $document) {
                            $document->setRelation('childLinks', new EloquentCollection([]));
                        }),
                    ]),
                    'matching_documents' => collect([
                        new FinanceDocument([
                            'id' => 43,
                            'document_no' => 'AP-00043',
                            'document_type' => 'supplier_invoice',
                            'workflow_status' => 'approved',
                            'accounting_status' => 'ready_to_post',
                            'gross_amount' => 2750000,
                            'currency_code' => 'VND',
                            'document_date' => now(),
                            'meta' => [
                                'matching' => [
                                    'latest_status' => 'blocked',
                                    'blocking_exception_count' => 2,
                                    'warning_count' => 1,
                                ],
                            ],
                        ]),
                    ]),
                    'discrepancy_exceptions' => collect([
                        tap(new \Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException([
                            'id' => 6001,
                            'document_id' => 43,
                            'status' => 'in_review',
                            'code' => 'amount_variance_exceeded',
                            'severity' => 'blocking',
                            'owner_id' => 99,
                            'owner_assigned_at' => now()->subDays(8),
                        ]), function ($exception) {
                            $exception->setRelation('document', new FinanceDocument([
                                'id' => 43,
                                'document_no' => 'AP-00043',
                            ]));
                            $exception->setRelation('owner', (object) [
                                'id' => 99,
                                'surname' => 'Tran',
                                'first_name' => 'Procurement',
                                'last_name' => 'Lead',
                            ]);
                        }),
                    ]),
                    'owner_summary' => collect([
                        [
                            'owner_id' => 99,
                            'owner_name' => 'Tran Procurement Lead',
                            'open_count' => 2,
                            'aged_over_2_days' => 1,
                            'aged_over_7_days' => 1,
                        ],
                        [
                            'owner_id' => 0,
                            'owner_name' => null,
                            'open_count' => 1,
                            'aged_over_2_days' => 0,
                            'aged_over_7_days' => 0,
                        ],
                    ]),
                ],
            ],
            'expenseInsights' => [
                7 => [
                    'pending_documents' => collect(),
                    'outstanding_documents' => collect(),
                    'escalated_approvals' => collect([
                        (object) [
                            'id' => 55,
                            'document_no' => 'EXP-00055',
                            'document_type' => 'expense_claim',
                            'approval_close_insight' => [
                                'current_step_role_label' => 'Finance manager',
                                'sla_label' => 'Overdue by 12.00h',
                                'escalation_message' => 'Escalate to CFO',
                            ],
                        ],
                    ]),
                ],
            ],
            'procurementAssigneeOptions' => [99 => 'Tran Procurement Lead', 101 => 'Finance Controller'],
            'recentPackets' => collect(),
            'vasAccountingPageMeta' => [
                'title' => 'Closing',
                'subtitle' => 'Period close readiness.',
                'icon' => 'ki-outline ki-lock-2',
                'section_label' => 'Controls',
                'badge_variant' => 'light-warning',
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
            'locationOptions' => [],
            'selectedLocationId' => null,
        ])->render();

        $this->assertStringContainsString('Pending procurement documents', $html);
        $this->assertStringContainsString('Review matching', $html);
        $this->assertStringContainsString('block / warn', $html);
        $this->assertStringContainsString('Discrepancy ownership', $html);
        $this->assertStringContainsString('Assign unassigned', $html);
        $this->assertStringContainsString('Claim unassigned', $html);
        $this->assertStringContainsString('Aged discrepancies', $html);
        $this->assertStringContainsString('Tran Procurement Lead', $html);
        $this->assertStringContainsString('Assign owner...', $html);
        $this->assertStringContainsString('Reassign owner', $html);
        $this->assertStringContainsString('Escalated expense approvals', $html);
        $this->assertStringContainsString('Review escalations', $html);
        $this->assertStringContainsString('Escalate to CFO', $html);
    }

    public function test_expense_page_renders_escalation_action_for_overdue_document(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.expenses.manage',
        ]));

        $document = new FinanceDocument([
            'id' => 91,
            'document_no' => 'EXP-00091',
            'document_type' => 'expense_claim',
            'workflow_status' => 'submitted',
            'accounting_status' => 'not_ready',
            'gross_amount' => 1250000,
            'document_date' => now(),
            'external_reference' => null,
            'meta' => [
                'expense' => [
                    'claimant_name' => 'Jane Doe',
                    'department_id' => null,
                    'cost_center_id' => null,
                ],
                'expense_chain' => [
                    'settlement_status' => 'open',
                ],
            ],
        ]);

        $approvalStep = new FinanceApprovalStep([
            'step_no' => 1,
            'status' => 'pending',
            'approver_role' => 'finance_manager',
        ]);
        $approvalInstance = new FinanceApprovalInstance([
            'policy_code' => 'EXPENSE_CLAIM_MANAGER_REVIEW',
            'current_step_no' => 1,
        ]);
        $approvalInstance->setRelation('steps', new EloquentCollection([$approvalStep]));
        $document->setRelation('approvalInstances', new EloquentCollection([$approvalInstance]));

        $html = view('vasaccounting::expenses.index', [
            'summary' => [
                'documents' => 1,
                'open_workflow' => 1,
                'posted_documents' => 0,
                'escalated_workflow' => 1,
                'high_value_documents' => 0,
                'gross_amount' => 1250000,
            ],
            'approvalInsights' => [
                91 => [
                    'sla_state' => 'overdue',
                    'sla_label' => 'Overdue by 4.00h',
                    'sla_badge_class' => 'badge-light-danger',
                    'current_step_role_label' => 'Finance manager',
                    'current_step_label' => 'Finance review',
                    'escalation_message' => 'Escalate to CFO',
                    'escalation_count' => 1,
                    'last_escalated_at' => '2026-04-01 09:30:00',
                    'last_escalation_reason' => 'Month-end approval is overdue.',
                    'dispatch_status_label' => 'Dispatch queued',
                    'threshold_label' => 'Up to 5,000,000.00 VND',
                ],
            ],
            'documentTypeCounts' => [
                'expense_claim' => 1,
                'advance_request' => 0,
                'advance_settlement' => 0,
                'reimbursement_voucher' => 0,
            ],
            'expenseDocuments' => collect([$document]),
            'employeeOptions' => [1 => 'Jane Doe'],
            'locationOptions' => [],
            'selectedLocationId' => null,
            'closePeriod' => null,
            'workspaceFocus' => 'escalated_approvals',
            'departmentOptions' => [],
            'costCenterOptions' => [],
            'projectOptions' => [],
            'taxCodeOptions' => collect(),
            'chartOptions' => collect(),
            'advanceRequestOptions' => collect(),
            'expenseClaimOptions' => collect(),
            'vasAccountingPageMeta' => [
                'title' => 'Expense Management',
                'subtitle' => 'Native expense workflow.',
                'icon' => 'ki-outline ki-wallet',
                'section_label' => 'Operations',
                'badge_variant' => 'light-info',
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
        ])->render();

        $this->assertStringContainsString('Escalate again', $html);
        $this->assertStringContainsString('Month-end approval is overdue.', $html);
        $this->assertStringContainsString('Last escalated', $html);
        $this->assertStringContainsString('Dispatch queued', $html);
    }

    public function test_expense_page_renders_dispatch_failure_details(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.expenses.manage',
        ]));

        $document = new FinanceDocument([
            'id' => 92,
            'document_no' => 'EXP-00092',
            'document_type' => 'expense_claim',
            'workflow_status' => 'submitted',
            'accounting_status' => 'not_ready',
            'gross_amount' => 980000,
            'document_date' => now(),
            'external_reference' => null,
            'meta' => [
                'expense' => [
                    'claimant_name' => 'Jane Doe',
                    'department_id' => null,
                    'cost_center_id' => null,
                ],
                'expense_chain' => [
                    'settlement_status' => 'open',
                ],
            ],
        ]);

        $approvalStep = new FinanceApprovalStep([
            'step_no' => 1,
            'status' => 'pending',
            'approver_role' => 'finance_manager',
        ]);
        $approvalInstance = new FinanceApprovalInstance([
            'policy_code' => 'EXPENSE_CLAIM_MANAGER_REVIEW',
            'current_step_no' => 1,
        ]);
        $approvalInstance->setRelation('steps', new EloquentCollection([$approvalStep]));
        $document->setRelation('approvalInstances', new EloquentCollection([$approvalInstance]));

        $html = view('vasaccounting::expenses.index', [
            'summary' => [
                'documents' => 1,
                'open_workflow' => 1,
                'posted_documents' => 0,
                'escalated_workflow' => 1,
                'high_value_documents' => 0,
                'gross_amount' => 980000,
            ],
            'approvalInsights' => [
                92 => [
                    'sla_state' => 'overdue',
                    'sla_label' => 'Overdue by 6.00h',
                    'sla_badge_class' => 'badge-light-danger',
                    'current_step_role_label' => 'Finance manager',
                    'current_step_label' => 'Finance review',
                    'escalation_message' => 'Escalate to CFO',
                    'dispatch_status_label' => 'Dispatch failed',
                    'dispatch_status' => 'failed',
                    'dispatch_error' => 'Queue transport unavailable',
                    'threshold_label' => 'Up to 5,000,000.00 VND',
                ],
            ],
            'documentTypeCounts' => [
                'expense_claim' => 1,
                'advance_request' => 0,
                'advance_settlement' => 0,
                'reimbursement_voucher' => 0,
            ],
            'expenseDocuments' => collect([$document]),
            'employeeOptions' => [1 => 'Jane Doe'],
            'locationOptions' => [],
            'selectedLocationId' => null,
            'closePeriod' => null,
            'workspaceFocus' => 'escalated_approvals',
            'departmentOptions' => [],
            'costCenterOptions' => [],
            'projectOptions' => [],
            'taxCodeOptions' => collect(),
            'chartOptions' => collect(),
            'advanceRequestOptions' => collect(),
            'expenseClaimOptions' => collect(),
            'vasAccountingPageMeta' => [
                'title' => 'Expense Management',
                'subtitle' => 'Native expense workflow.',
                'icon' => 'ki-outline ki-wallet',
                'section_label' => 'Operations',
                'badge_variant' => 'light-info',
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
        ])->render();

        $this->assertStringContainsString('Dispatch failed', $html);
        $this->assertStringContainsString('Queue transport unavailable', $html);
        $this->assertStringContainsString('Retry dispatch', $html);
    }

    public function test_procurement_page_renders_matching_and_receiving_actions(): void
    {
        $this->actingAs($this->makeUser([
            'vas_accounting.access',
            'vas_accounting.procurement.manage',
        ]));
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $purchaseOrder = new FinanceDocument([
            'id' => 301,
            'document_no' => 'PO-00301',
            'document_type' => 'purchase_order',
            'workflow_status' => 'approved',
            'accounting_status' => 'not_ready',
            'gross_amount' => 5000000,
            'net_amount' => 5000000,
            'tax_amount' => 0,
            'document_date' => now(),
            'counterparty_id' => 10,
            'business_location_id' => 2,
            'meta' => [],
        ]);
        $purchaseOrder->setRelation('approvalInstances', new EloquentCollection([]));
        $purchaseOrder->setRelation('parentLinks', new EloquentCollection([]));
        $purchaseOrder->setRelation('childLinks', new EloquentCollection([]));
        $purchaseOrder->setRelation('matchRuns', new EloquentCollection([]));

        $supplierInvoice = new FinanceDocument([
            'id' => 302,
            'document_no' => 'AP-00302',
            'document_type' => 'supplier_invoice',
            'workflow_status' => 'approved',
            'accounting_status' => 'ready_to_post',
            'gross_amount' => 5250000,
            'net_amount' => 5000000,
            'tax_amount' => 250000,
            'document_date' => now(),
            'counterparty_id' => 10,
            'business_location_id' => 2,
            'meta' => [
                'matching' => [
                    'latest_status' => 'blocked',
                    'blocking_exception_count' => 1,
                    'warning_count' => 0,
                ],
            ],
        ]);
        $supplierInvoice->setRelation('approvalInstances', new EloquentCollection([]));
        $supplierInvoice->setRelation('parentLinks', new EloquentCollection([]));
        $supplierInvoice->setRelation('childLinks', new EloquentCollection([]));
        $supplierInvoice->setRelation('matchRuns', new EloquentCollection([]));

        $html = view('vasaccounting::procurement.index', [
            'summary' => [
                'documents' => 2,
                'pending_documents' => 2,
                'receiving_queue' => 1,
                'pending_matching' => 1,
                'open_discrepancies' => 1,
                'posted_documents' => 0,
                'gross_amount' => 10250000,
            ],
            'documentTypeCounts' => [
                'purchase_requisition' => 0,
                'purchase_order' => 1,
                'goods_receipt' => 0,
                'supplier_invoice' => 1,
            ],
            'procurementDocuments' => collect([$purchaseOrder, $supplierInvoice]),
            'workspaceFocus' => 'discrepancy_queue',
            'discrepancySummary' => [
                'total' => 1,
                'blocking' => 1,
                'warning' => 0,
                'documents' => 1,
                'in_review' => 1,
                'owned_by_me' => 1,
                'unassigned' => 0,
                'aged_over_2_days' => 0,
                'aged_over_7_days' => 0,
            ],
            'discrepancyQueue' => collect([
                [
                    'exception_id' => 9001,
                    'document_id' => 302,
                    'document_no' => 'AP-00302',
                    'document_date' => now()->format('Y-m-d'),
                    'workflow_status' => 'approved',
                    'status' => 'in_review',
                    'severity' => 'blocking',
                    'code' => 'amount_variance_exceeded',
                    'message' => 'Supplier invoice line [1] amount variance exceeds tolerance.',
                    'line_no' => 1,
                    'product_id' => 7,
                    'match_status' => 'blocked',
                    'blocking_exception_count' => 1,
                    'warning_count' => 0,
                    'owner_id' => 99,
                    'owner_name' => 'Procurement Lead',
                    'owner_assigned_at' => '2026-04-02 09:15',
                    'owner_age_days' => 2,
                    'resolution_note' => '',
                    'meta' => ['match_key' => 'product:7'],
                ],
            ]),
            'closePeriod' => null,
            'selectedLocationId' => null,
            'locationOptions' => [2 => 'Main Branch'],
            'supplierOptions' => [10 => 'Acme Supplier'],
            'productOptions' => [7 => 'Steel Roll'],
            'assigneeOptions' => [99 => 'Procurement Lead', 101 => 'Finance Controller'],
            'taxCodeOptions' => collect(),
            'chartOptions' => collect(),
            'parentDocumentOptions' => collect([$purchaseOrder, $supplierInvoice]),
            'supportedDocumentTypes' => ['purchase_requisition', 'purchase_order', 'goods_receipt', 'supplier_invoice'],
            'vasAccountingPageMeta' => [
                'title' => 'Procurement Workspace',
                'subtitle' => 'Canonical P2P workflow.',
                'icon' => 'ki-outline ki-delivery',
                'section_label' => 'Operations',
                'badge_variant' => 'light-primary',
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
        ])->render();

        $this->assertStringContainsString('Procurement Workspace', $html);
        $this->assertStringContainsString('Review receiving', $html);
        $this->assertStringContainsString('Run match', $html);
        $this->assertStringContainsString('Mark fully received', $html);
        $this->assertStringContainsString('Open discrepancies', $html);
        $this->assertStringContainsString('Procurement discrepancy queue', $html);
        $this->assertStringContainsString('Queue status', $html);
        $this->assertStringContainsString('Owned by me', $html);
        $this->assertStringContainsString('Unassigned', $html);
        $this->assertStringContainsString('Aged &gt; 2 days', $html);
        $this->assertStringContainsString('Open ownership report', $html);
        $this->assertStringContainsString('Procurement Lead', $html);
        $this->assertStringContainsString('Take ownership', $html);
        $this->assertStringContainsString('Reassign owner', $html);
        $this->assertStringContainsString('Finance Controller', $html);
        $this->assertStringContainsString('Resolve with note', $html);
        $this->assertStringContainsString('Re-run match', $html);
        $this->assertStringContainsString('Amount Variance Exceeded', $html);
        $this->assertStringContainsString('Focused procurement review: Discrepancy Queue', $html);
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
