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
        $this->assertStringContainsString('Rendered Dataset', $html);
        $this->assertStringContainsString('1111 Cash', $html);
        $this->assertStringContainsString('Open escalated approvals', $html);
        $this->assertStringContainsString('Retry failed dispatches', $html);
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
            ],
            'discrepancyQueue' => collect([
                [
                    'document_id' => 302,
                    'document_no' => 'AP-00302',
                    'document_date' => now()->format('Y-m-d'),
                    'workflow_status' => 'approved',
                    'severity' => 'blocking',
                    'code' => 'amount_variance_exceeded',
                    'message' => 'Supplier invoice line [1] amount variance exceeds tolerance.',
                    'line_no' => 1,
                    'product_id' => 7,
                    'match_status' => 'blocked',
                    'blocking_exception_count' => 1,
                    'warning_count' => 0,
                    'meta' => ['match_key' => 'product:7'],
                ],
            ]),
            'closePeriod' => null,
            'selectedLocationId' => null,
            'locationOptions' => [2 => 'Main Branch'],
            'supplierOptions' => [10 => 'Acme Supplier'],
            'productOptions' => [7 => 'Steel Roll'],
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
