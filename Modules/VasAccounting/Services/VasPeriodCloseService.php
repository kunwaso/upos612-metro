<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasAssetDepreciation;
use Modules\VasAccounting\Entities\VasCloseChecklist;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryException;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalMonitorService;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;

class VasPeriodCloseService
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected ?OperationsAssetReportUtil $operationsAssetReportUtil = null,
        protected ?ExpenseApprovalMonitorService $expenseApprovalMonitorService = null
    )
    {
    }

    public function blockers(int $businessId, VasAccountingPeriod $period): array
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $complianceCompletion = $this->vasUtil->complianceCompletionStatus($settings);
        $periodEnd = optional($period->end_date)->toDateString();

        $draftVouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('accounting_period_id', $period->id)
            ->whereIn('status', ['draft', 'pending_approval', 'approved'])
            ->count();

        $postingFailures = VasPostingFailure::query()
            ->where('business_id', $businessId)
            ->whereNull('resolved_at')
            ->count();

        $pendingDepreciation = VasAssetDepreciation::query()
            ->where('business_id', $businessId)
            ->where('accounting_period_id', $period->id)
            ->where('status', '!=', 'posted')
            ->count();

        $unreconciledBankLines = Schema::hasTable('vas_fin_treasury_exceptions')
            ? (int) FinanceTreasuryException::query()
                ->where('business_id', $businessId)
                ->whereIn('status', ['open', 'suggested'])
                ->whereHas('statementLine', function ($query) use ($periodEnd) {
                    if ($periodEnd) {
                        $query->whereDate('transaction_date', '<=', $periodEnd);
                    }
                })
                ->count()
            : (Schema::hasTable('vas_bank_statement_lines')
                ? (int) DB::table('vas_bank_statement_lines')
                    ->where('business_id', $businessId)
                    ->where('match_status', 'unmatched')
                ->when($periodEnd, fn ($query) => $query->whereDate('transaction_date', '<=', $periodEnd))
                ->count()
            : 0);

        $pendingTreasuryDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->pendingTreasuryDocumentsQuery($businessId, $periodEnd)->count()
            : 0;

        $pendingProcurementDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->pendingProcurementDocumentsQuery($businessId, $periodEnd)->count()
            : 0;

        $receivingProcurementDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->receivingProcurementDocumentsQuery($businessId, $periodEnd)->count()
            : 0;

        $matchingProcurementDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->matchingProcurementDocumentsQuery($businessId, $periodEnd)->count()
            : 0;

        $pendingExpenseDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->pendingExpenseDocumentsQuery($businessId, $periodEnd)->count()
            : 0;

        $outstandingExpenseDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->outstandingExpenseDocumentsQuery($businessId, $periodEnd)->count()
            : 0;

        $escalatedExpenseApprovals = Schema::hasTable('vas_fin_documents')
            ? $this->escalatedExpenseApprovalDocuments($businessId, $periodEnd)->count()
            : 0;

        $pendingApprovals = Schema::hasTable('vas_document_approvals')
            ? (int) DB::table('vas_document_approvals')
                ->where('business_id', $businessId)
                ->where('status', 'pending')
                ->count()
            : 0;

        $warehouseSummary = $this->operationsAssetReportUtil()?->warehouseSummary($businessId, $periodEnd) ?? [];

        return [
            'posting_map_incomplete' => ! $this->vasUtil->isPostingMapComplete($settings),
            'compliance_checks_incomplete' => ! ((bool) ($complianceCompletion['is_complete'] ?? false)),
            'compliance_completion' => (int) ($complianceCompletion['completion_percent'] ?? 0),
            'draft_vouchers' => $draftVouchers,
            'posting_failures' => $postingFailures,
            'pending_depreciation' => $pendingDepreciation,
            'unreconciled_bank_lines' => $unreconciledBankLines,
            'pending_treasury_documents' => (int) $pendingTreasuryDocuments,
            'pending_procurement_documents' => (int) $pendingProcurementDocuments,
            'receiving_procurement_documents' => (int) $receivingProcurementDocuments,
            'matching_procurement_documents' => (int) $matchingProcurementDocuments,
            'pending_expense_documents' => (int) $pendingExpenseDocuments,
            'outstanding_expense_documents' => (int) $outstandingExpenseDocuments,
            'escalated_expense_approvals' => (int) $escalatedExpenseApprovals,
            'pending_approvals' => $pendingApprovals,
            'unposted_inventory_documents' => (int) ($warehouseSummary['unposted_documents'] ?? 0),
            'warehouse_discrepancies' => (int) ($warehouseSummary['warehouse_discrepancies'] ?? 0),
            'storage_sync_pending' => (int) ($warehouseSummary['storage_sync_pending'] ?? 0),
            'storage_sync_errors' => (int) ($warehouseSummary['storage_sync_errors'] ?? 0),
            'storage_reconcile_errors' => (int) ($warehouseSummary['storage_reconcile_errors'] ?? 0),
        ];
    }

    public function treasuryCloseInsights(int $businessId, VasAccountingPeriod $period): array
    {
        $periodEnd = optional($period->end_date)->toDateString();

        $pendingDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->pendingTreasuryDocumentsQuery($businessId, $periodEnd)
                ->orderByDesc('document_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get([
                    'id',
                    'document_no',
                    'document_type',
                    'workflow_status',
                    'accounting_status',
                    'gross_amount',
                    'currency_code',
                    'document_date',
                    'posting_date',
                ])
            : collect();

        $exceptions = Schema::hasTable('vas_fin_treasury_exceptions')
            ? $this->unresolvedTreasuryExceptionsQuery($businessId, $periodEnd)
                ->with(['statementLine', 'recommendedDocument'])
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : collect();

        return [
            'pending_documents' => $pendingDocuments,
            'exceptions' => $exceptions,
        ];
    }

    public function procurementCloseInsights(int $businessId, VasAccountingPeriod $period): array
    {
        $periodEnd = optional($period->end_date)->toDateString();

        $pendingDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->pendingProcurementDocumentsQuery($businessId, $periodEnd)
                ->with(['parentLinks.parentDocument'])
                ->orderByDesc('document_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get([
                    'id',
                    'document_no',
                    'document_family',
                    'document_type',
                    'workflow_status',
                    'accounting_status',
                    'gross_amount',
                    'currency_code',
                    'document_date',
                    'posting_date',
                    'meta',
                ])
            : collect();

        $receivingDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->receivingProcurementDocumentsQuery($businessId, $periodEnd)
                ->with(['childLinks.childDocument'])
                ->orderByDesc('document_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get([
                    'id',
                    'document_no',
                    'document_family',
                    'document_type',
                    'workflow_status',
                    'accounting_status',
                    'gross_amount',
                    'currency_code',
                    'document_date',
                    'posting_date',
                    'meta',
                ])
            : collect();

        $matchingDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->matchingProcurementDocumentsQuery($businessId, $periodEnd)
                ->with(['parentLinks.parentDocument', 'childLinks.childDocument'])
                ->orderByDesc('document_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get([
                    'id',
                    'document_no',
                    'document_family',
                    'document_type',
                    'workflow_status',
                    'accounting_status',
                    'gross_amount',
                    'currency_code',
                    'document_date',
                    'posting_date',
                    'meta',
                ])
            : collect();

        $discrepancyExceptions = Schema::hasTable('vas_fin_match_exceptions')
            ? $this->unresolvedProcurementDiscrepanciesQuery($businessId, $periodEnd)
                ->with(['document', 'owner'])
                ->get([
                    'id',
                    'business_id',
                    'document_id',
                    'status',
                    'code',
                    'severity',
                    'message',
                    'owner_id',
                    'owner_assigned_at',
                    'resolved_at',
                ])
                ->sort(function (FinanceMatchException $left, FinanceMatchException $right) {
                    $leftUnassigned = $left->owner_id ? 1 : 0;
                    $rightUnassigned = $right->owner_id ? 1 : 0;

                    if ($leftUnassigned !== $rightUnassigned) {
                        return $leftUnassigned <=> $rightUnassigned;
                    }

                    $leftAge = $left->owner_assigned_at ? $left->owner_assigned_at->timestamp : 0;
                    $rightAge = $right->owner_assigned_at ? $right->owner_assigned_at->timestamp : 0;

                    return $leftAge <=> $rightAge;
                })
                ->values()
            : collect();

        $ownerSummary = $discrepancyExceptions
            ->groupBy(fn (FinanceMatchException $exception) => (int) ($exception->owner_id ?: 0))
            ->map(function ($exceptions, $ownerId) {
                $owner = $exceptions->first()?->owner;

                return [
                    'owner_id' => (int) $ownerId,
                    'owner_name' => $ownerId > 0
                        ? trim((string) ($owner?->surname . ' ' . $owner?->first_name . ' ' . $owner?->last_name))
                        : null,
                    'open_count' => $exceptions->count(),
                    'aged_over_2_days' => $exceptions->filter(fn (FinanceMatchException $exception) => $exception->owner_assigned_at && $exception->owner_assigned_at->diffInDays(now()) > 2)->count(),
                    'aged_over_7_days' => $exceptions->filter(fn (FinanceMatchException $exception) => $exception->owner_assigned_at && $exception->owner_assigned_at->diffInDays(now()) > 7)->count(),
                ];
            })
            ->sort(function (array $left, array $right) {
                return [$right['aged_over_7_days'], $right['aged_over_2_days'], $right['open_count']]
                    <=> [$left['aged_over_7_days'], $left['aged_over_2_days'], $left['open_count']];
            })
            ->values();

        return [
            'pending_documents' => $pendingDocuments,
            'receiving_documents' => $receivingDocuments,
            'matching_documents' => $matchingDocuments,
            'discrepancy_exceptions' => $discrepancyExceptions->take(5)->values(),
            'owner_summary' => $ownerSummary->take(5)->values(),
        ];
    }

    public function unassignedProcurementDiscrepancies(int $businessId, VasAccountingPeriod $period)
    {
        $periodEnd = optional($period->end_date)->toDateString();

        return Schema::hasTable('vas_fin_match_exceptions')
            ? $this->unresolvedProcurementDiscrepanciesQuery($businessId, $periodEnd)
                ->where('owner_id', 0)
                ->orderBy('id')
                ->get()
            : collect();
    }

    public function expenseCloseInsights(int $businessId, VasAccountingPeriod $period): array
    {
        $periodEnd = optional($period->end_date)->toDateString();

        $pendingDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->pendingExpenseDocumentsQuery($businessId, $periodEnd)
                ->with('approvalInstances.steps')
                ->orderByDesc('document_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get([
                    'id',
                    'document_no',
                    'document_type',
                    'workflow_status',
                    'accounting_status',
                    'gross_amount',
                    'open_amount',
                    'currency_code',
                    'document_date',
                    'posting_date',
                    'meta',
                ])
            : collect();

        $outstandingDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->outstandingExpenseDocumentsQuery($businessId, $periodEnd)
                ->orderByDesc(DB::raw('COALESCE(posting_date, document_date)'))
                ->orderByDesc('id')
                ->limit(5)
                ->get([
                    'id',
                    'document_no',
                    'document_type',
                    'workflow_status',
                    'accounting_status',
                    'gross_amount',
                    'open_amount',
                    'currency_code',
                    'document_date',
                    'posting_date',
                    'meta',
                ])
            : collect();

        $escalatedApprovals = Schema::hasTable('vas_fin_documents')
            ? $this->escalatedExpenseApprovalDocuments($businessId, $periodEnd, 5)
            : collect();

        return [
            'pending_documents' => $pendingDocuments,
            'outstanding_documents' => $outstandingDocuments,
            'escalated_approvals' => $escalatedApprovals,
        ];
    }

    public function checklistForPeriod(int $businessId, VasAccountingPeriod $period)
    {
        $this->syncChecklist($businessId, $period);

        return VasCloseChecklist::query()
            ->where('business_id', $businessId)
            ->where('accounting_period_id', $period->id)
            ->orderBy('id')
            ->get();
    }

    public function syncChecklist(int $businessId, VasAccountingPeriod $period, ?int $userId = null)
    {
        $blockers = $this->blockers($businessId, $period);
        $warehouseSyncBlockers = (int) $blockers['storage_sync_pending']
            + (int) $blockers['storage_sync_errors']
            + (int) $blockers['storage_reconcile_errors'];
        $warehouseBlockers = (int) $blockers['unposted_inventory_documents']
            + (int) $blockers['warehouse_discrepancies']
            + $warehouseSyncBlockers;

        $definitions = [
            'compliance_profile' => [
                'title' => 'Compliance profile checks completed',
                'status' => $blockers['compliance_checks_incomplete'] ? 'blocked' : 'completed',
                'notes' => $blockers['compliance_checks_incomplete']
                    ? 'Compliance baseline checks are incomplete.'
                    : 'Compliance baseline checks are complete.',
            ],
            'posting_map' => [
                'title' => 'Mandatory posting map completed',
                'status' => $blockers['posting_map_incomplete'] ? 'blocked' : 'completed',
                'notes' => $blockers['posting_map_incomplete'] ? __('vasaccounting::lang.posting_map_incomplete') : 'Posting accounts are fully mapped.',
            ],
            'voucher_workflow' => [
                'title' => 'Draft and approval backlog cleared',
                'status' => $blockers['draft_vouchers'] > 0 ? 'blocked' : 'completed',
                'notes' => $blockers['draft_vouchers'] > 0 ? $blockers['draft_vouchers'] . ' vouchers still need approval or posting.' : 'No draft or approval-backlog vouchers remain.',
            ],
            'posting_failures' => [
                'title' => 'Posting failure queue resolved',
                'status' => $blockers['posting_failures'] > 0 ? 'blocked' : 'completed',
                'notes' => $blockers['posting_failures'] > 0 ? $blockers['posting_failures'] . ' posting failures remain unresolved.' : 'No unresolved posting failures remain.',
            ],
            'depreciation' => [
                'title' => 'Depreciation and amortization completed',
                'status' => $blockers['pending_depreciation'] > 0 ? 'blocked' : 'completed',
                'notes' => $blockers['pending_depreciation'] > 0 ? $blockers['pending_depreciation'] . ' depreciation rows are not posted yet.' : 'Depreciation postings are complete.',
            ],
            'bank_reconciliation' => [
                'title' => 'Bank reconciliation exceptions reviewed',
                'status' => $blockers['unreconciled_bank_lines'] > 0 ? 'blocked' : 'completed',
                'notes' => $blockers['unreconciled_bank_lines'] > 0 ? $blockers['unreconciled_bank_lines'] . ' treasury reconciliation exceptions remain unresolved.' : 'No unresolved treasury reconciliation exceptions remain.',
            ],
            'treasury_documents' => [
                'title' => 'Native treasury documents posted or reversed',
                'status' => $blockers['pending_treasury_documents'] > 0 ? 'blocked' : 'completed',
                'notes' => $blockers['pending_treasury_documents'] > 0 ? $blockers['pending_treasury_documents'] . ' native treasury documents still need posting or reversal review.' : 'All native treasury documents through the period end are posted or reversed.',
            ],
            'procurement' => [
                'title' => 'Procurement workflow and matching cleared',
                'status' => ($blockers['pending_procurement_documents'] + $blockers['receiving_procurement_documents'] + $blockers['matching_procurement_documents']) > 0 ? 'blocked' : 'completed',
                'notes' => ($blockers['pending_procurement_documents'] + $blockers['receiving_procurement_documents'] + $blockers['matching_procurement_documents']) > 0
                    ? $blockers['pending_procurement_documents'] . ' procurement documents still need workflow action, '
                        . $blockers['receiving_procurement_documents'] . ' purchase orders still need receiving progression, and '
                        . $blockers['matching_procurement_documents'] . ' supplier invoices still need matching attention.'
                    : 'Procurement workflow backlog is clear and supplier invoice matching is ready through period end.',
            ],
            'expenses' => [
                'title' => 'Expense advances and claims resolved',
                'status' => ($blockers['pending_expense_documents'] + $blockers['outstanding_expense_documents'] + $blockers['escalated_expense_approvals']) > 0 ? 'blocked' : 'completed',
                'notes' => ($blockers['pending_expense_documents'] + $blockers['outstanding_expense_documents'] + $blockers['escalated_expense_approvals']) > 0
                    ? $blockers['pending_expense_documents'] . ' expense documents still need workflow action, '
                        . $blockers['outstanding_expense_documents'] . ' posted advances or claims remain open, and '
                        . $blockers['escalated_expense_approvals'] . ' approvals are overdue.'
                    : 'Expense workflow backlog is clear and no advances or claims remain outstanding through period end.',
            ],
            'approvals' => [
                'title' => 'Document approval queue cleared',
                'status' => $blockers['pending_approvals'] > 0 ? 'blocked' : 'completed',
                'notes' => $blockers['pending_approvals'] > 0 ? $blockers['pending_approvals'] . ' approval steps are still pending.' : 'Approval queue is clear.',
            ],
            'warehouse' => [
                'title' => 'Warehouse documents posted and reconciled',
                'status' => $warehouseBlockers > 0 ? 'blocked' : 'completed',
                'notes' => $warehouseBlockers > 0
                    ? $blockers['unposted_inventory_documents'] . ' warehouse documents still need posting, '
                        . $blockers['warehouse_discrepancies'] . ' warehouse discrepancies remain, and '
                        . $warehouseSyncBlockers . ' storage execution sync/reconciliation blockers remain.'
                    : 'Warehouse documents, sync checks, and warehouse coverage checks are clear.',
            ],
        ];

        foreach ($definitions as $checklistKey => $definition) {
            VasCloseChecklist::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'accounting_period_id' => $period->id,
                    'checklist_key' => $checklistKey,
                ],
                [
                    'title' => $definition['title'],
                    'status' => $definition['status'],
                    'is_required' => true,
                    'completed_at' => $definition['status'] === 'completed' ? now() : null,
                    'completed_by' => $definition['status'] === 'completed' ? $userId : null,
                    'notes' => $definition['notes'],
                    'meta' => ['blockers' => $blockers],
                ]
            );
        }

        return VasCloseChecklist::query()
            ->where('business_id', $businessId)
            ->where('accounting_period_id', $period->id)
            ->orderBy('id')
            ->get();
    }

    public function softLockPeriod(VasAccountingPeriod $period, int $userId): VasAccountingPeriod
    {
        if ($period->status === 'closed') {
            throw new RuntimeException('Closed periods must be reopened before soft-lock changes can be applied.');
        }

        $period->status = 'soft_locked';
        $period->meta = array_replace((array) $period->meta, [
            'soft_locked_at' => now()->toDateTimeString(),
            'soft_locked_by' => $userId,
        ]);
        $period->save();

        $this->syncChecklist((int) $period->business_id, $period, $userId);

        return $period->fresh();
    }

    public function closePeriod(VasAccountingPeriod $period, int $userId): VasAccountingPeriod
    {
        $blockers = $this->blockers((int) $period->business_id, $period);
        $this->syncChecklist((int) $period->business_id, $period, $userId);

        $hasBlockers = $blockers['posting_map_incomplete']
            || $blockers['compliance_checks_incomplete']
            || $blockers['draft_vouchers'] > 0
            || $blockers['posting_failures'] > 0
            || $blockers['pending_depreciation'] > 0
            || $blockers['unreconciled_bank_lines'] > 0
            || $blockers['pending_treasury_documents'] > 0
            || $blockers['pending_procurement_documents'] > 0
            || $blockers['receiving_procurement_documents'] > 0
            || $blockers['matching_procurement_documents'] > 0
            || $blockers['pending_expense_documents'] > 0
            || $blockers['outstanding_expense_documents'] > 0
            || $blockers['escalated_expense_approvals'] > 0
            || $blockers['pending_approvals'] > 0
            || $blockers['unposted_inventory_documents'] > 0
            || $blockers['warehouse_discrepancies'] > 0
            || $blockers['storage_sync_pending'] > 0
            || $blockers['storage_sync_errors'] > 0
            || $blockers['storage_reconcile_errors'] > 0;

        if ($hasBlockers) {
            throw new RuntimeException('VAS accounting period cannot be closed while close-center blockers remain.');
        }

        $period->status = 'closed';
        $period->closed_at = now();
        $period->closed_by = $userId;
        $period->meta = array_replace((array) $period->meta, [
            'last_closed_at' => now()->toDateTimeString(),
            'last_closed_by' => $userId,
        ]);
        $period->save();

        return $period->fresh();
    }

    public function reopenPeriod(VasAccountingPeriod $period, int $userId, string $reason): VasAccountingPeriod
    {
        $history = (array) data_get((array) $period->meta, 'reopen_history', []);
        $history[] = [
            'reopened_at' => now()->toDateTimeString(),
            'reopened_by' => $userId,
            'reason' => $reason,
        ];

        $period->status = 'open';
        $period->closed_at = null;
        $period->closed_by = null;
        $period->meta = array_replace((array) $period->meta, [
            'reopen_history' => $history,
        ]);
        $period->save();

        $this->syncChecklist((int) $period->business_id, $period, $userId);

        return $period->fresh();
    }

    protected function operationsAssetReportUtil(): ?OperationsAssetReportUtil
    {
        return $this->operationsAssetReportUtil ?: app(OperationsAssetReportUtil::class);
    }

    protected function pendingTreasuryDocumentsQuery(int $businessId, ?string $periodEnd)
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->where('document_family', 'cash_bank')
            ->whereIn('document_type', ['cash_transfer', 'bank_transfer', 'petty_cash_expense'])
            ->whereIn('workflow_status', ['draft', 'submitted', 'approved'])
            ->when($periodEnd, function ($query) use ($periodEnd) {
                $query->where(function ($dateQuery) use ($periodEnd) {
                    $dateQuery->whereDate('posting_date', '<=', $periodEnd)
                        ->orWhere(function ($fallbackQuery) use ($periodEnd) {
                            $fallbackQuery->whereNull('posting_date')
                                ->whereDate('document_date', '<=', $periodEnd);
                        });
                });
            });
    }

    protected function unresolvedTreasuryExceptionsQuery(int $businessId, ?string $periodEnd)
    {
        return FinanceTreasuryException::query()
            ->where('business_id', $businessId)
            ->whereIn('status', ['open', 'suggested'])
            ->whereHas('statementLine', function ($query) use ($periodEnd) {
                if ($periodEnd) {
                    $query->whereDate('transaction_date', '<=', $periodEnd);
                }
            });
    }

    protected function pendingExpenseDocumentsQuery(int $businessId, ?string $periodEnd)
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->where('document_family', 'expense_management')
            ->whereIn('document_type', ['expense_claim', 'advance_request', 'advance_settlement', 'reimbursement_voucher'])
            ->whereIn('workflow_status', ['draft', 'submitted', 'approved'])
            ->when($periodEnd, function ($query) use ($periodEnd) {
                $query->where(function ($dateQuery) use ($periodEnd) {
                    $dateQuery->whereDate('posting_date', '<=', $periodEnd)
                        ->orWhere(function ($fallbackQuery) use ($periodEnd) {
                            $fallbackQuery->whereNull('posting_date')
                                ->whereDate('document_date', '<=', $periodEnd);
                        });
                });
            });
    }

    protected function pendingProcurementDocumentsQuery(int $businessId, ?string $periodEnd)
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->where(function ($query) {
                $query->where(function ($documentQuery) {
                    $documentQuery->where('document_type', 'purchase_requisition')
                        ->whereIn('workflow_status', ['draft', 'submitted', 'approved']);
                })->orWhere(function ($documentQuery) {
                    $documentQuery->where('document_type', 'purchase_order')
                        ->whereIn('workflow_status', ['draft', 'submitted']);
                })->orWhere(function ($documentQuery) {
                    $documentQuery->where('document_type', 'goods_receipt')
                        ->whereIn('workflow_status', ['draft', 'submitted', 'approved']);
                })->orWhere(function ($documentQuery) {
                    $documentQuery->where('document_type', 'supplier_invoice')
                        ->whereIn('workflow_status', ['draft', 'submitted']);
                });
            })
            ->when($periodEnd, function ($query) use ($periodEnd) {
                $query->where(function ($dateQuery) use ($periodEnd) {
                    $dateQuery->whereDate('posting_date', '<=', $periodEnd)
                        ->orWhere(function ($fallbackQuery) use ($periodEnd) {
                            $fallbackQuery->whereNull('posting_date')
                                ->whereDate('document_date', '<=', $periodEnd);
                        });
                });
            });
    }

    protected function receivingProcurementDocumentsQuery(int $businessId, ?string $periodEnd)
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'purchase_order')
            ->whereIn('workflow_status', ['approved', 'ordered', 'partially_received'])
            ->when($periodEnd, function ($query) use ($periodEnd) {
                $query->where(function ($dateQuery) use ($periodEnd) {
                    $dateQuery->whereDate('posting_date', '<=', $periodEnd)
                        ->orWhere(function ($fallbackQuery) use ($periodEnd) {
                            $fallbackQuery->whereNull('posting_date')
                                ->whereDate('document_date', '<=', $periodEnd);
                        });
                });
            });
    }

    protected function matchingProcurementDocumentsQuery(int $businessId, ?string $periodEnd)
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'supplier_invoice')
            ->where(function ($query) {
                $query->where('workflow_status', 'approved')
                    ->orWhereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.matching.blocking_exception_count')), '0') + 0 > 0")
                    ->orWhereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.matching.warning_count')), '0') + 0 > 0");
            })
            ->when($periodEnd, function ($query) use ($periodEnd) {
                $query->where(function ($dateQuery) use ($periodEnd) {
                    $dateQuery->whereDate('posting_date', '<=', $periodEnd)
                        ->orWhere(function ($fallbackQuery) use ($periodEnd) {
                            $fallbackQuery->whereNull('posting_date')
                                ->whereDate('document_date', '<=', $periodEnd);
                        });
                });
            });
    }

    protected function unresolvedProcurementDiscrepanciesQuery(int $businessId, ?string $periodEnd)
    {
        return FinanceMatchException::query()
            ->where('business_id', $businessId)
            ->whereIn('status', FinanceMatchException::unresolvedStatuses())
            ->whereHas('document', function ($query) use ($periodEnd) {
                $query->where('document_type', 'supplier_invoice')
                    ->when($periodEnd, function ($documentQuery) use ($periodEnd) {
                        $documentQuery->where(function ($dateQuery) use ($periodEnd) {
                            $dateQuery->whereDate('posting_date', '<=', $periodEnd)
                                ->orWhere(function ($fallbackQuery) use ($periodEnd) {
                                    $fallbackQuery->whereNull('posting_date')
                                        ->whereDate('document_date', '<=', $periodEnd);
                                });
                        });
                    });
            });
    }

    protected function outstandingExpenseDocumentsQuery(int $businessId, ?string $periodEnd)
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->where('document_family', 'expense_management')
            ->whereIn('document_type', ['expense_claim', 'advance_request'])
            ->whereNotIn('workflow_status', ['cancelled', 'reversed', 'closed'])
            ->where('open_amount', '>', 0)
            ->when($periodEnd, function ($query) use ($periodEnd) {
                $query->where(function ($dateQuery) use ($periodEnd) {
                    $dateQuery->whereDate('posting_date', '<=', $periodEnd)
                        ->orWhere(function ($fallbackQuery) use ($periodEnd) {
                            $fallbackQuery->whereNull('posting_date')
                                ->whereDate('document_date', '<=', $periodEnd);
                        });
                });
            });
    }

    protected function escalatedExpenseApprovalDocuments(int $businessId, ?string $periodEnd, ?int $limit = null)
    {
        $documents = $this->pendingExpenseDocumentsQuery($businessId, $periodEnd)
            ->with('approvalInstances.steps')
            ->orderByDesc('document_date')
            ->orderByDesc('id');

        if ($limit !== null) {
            $documents->limit($limit * 4);
        }

        $documents = $documents->get([
            'id',
            'document_no',
            'document_type',
            'workflow_status',
            'accounting_status',
            'gross_amount',
            'open_amount',
            'currency_code',
            'document_date',
            'posting_date',
            'meta',
        ]);

        $insights = $this->expenseApprovalMonitorService()->buildInsights($documents);

        $documents = $documents
            ->filter(function (FinanceDocument $document) use ($insights) {
                return data_get($insights, $document->id . '.sla_state') === 'overdue';
            })
            ->values()
            ->map(function (FinanceDocument $document) use ($insights) {
                $document->setAttribute('approval_close_insight', $insights[$document->id] ?? []);

                return $document;
            });

        if ($limit !== null) {
            return $documents->take($limit)->values();
        }

        return $documents;
    }

    protected function expenseApprovalMonitorService(): ExpenseApprovalMonitorService
    {
        return $this->expenseApprovalMonitorService ?: app(ExpenseApprovalMonitorService::class);
    }
}
