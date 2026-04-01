<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasAssetDepreciation;
use Modules\VasAccounting\Entities\VasCloseChecklist;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryException;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;

class VasPeriodCloseService
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected ?OperationsAssetReportUtil $operationsAssetReportUtil = null
    )
    {
    }

    public function blockers(int $businessId, VasAccountingPeriod $period): array
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
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

        $pendingExpenseDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->pendingExpenseDocumentsQuery($businessId, $periodEnd)->count()
            : 0;

        $outstandingExpenseDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->outstandingExpenseDocumentsQuery($businessId, $periodEnd)->count()
            : 0;

        $pendingApprovals = Schema::hasTable('vas_document_approvals')
            ? (int) DB::table('vas_document_approvals')
                ->where('business_id', $businessId)
                ->where('status', 'pending')
                ->count()
            : 0;

        $warehouseSummary = $this->operationsAssetReportUtil()?->warehouseSummary($businessId) ?? [];

        return [
            'posting_map_incomplete' => ! $this->vasUtil->isPostingMapComplete($settings),
            'draft_vouchers' => $draftVouchers,
            'posting_failures' => $postingFailures,
            'pending_depreciation' => $pendingDepreciation,
            'unreconciled_bank_lines' => $unreconciledBankLines,
            'pending_treasury_documents' => (int) $pendingTreasuryDocuments,
            'pending_expense_documents' => (int) $pendingExpenseDocuments,
            'outstanding_expense_documents' => (int) $outstandingExpenseDocuments,
            'pending_approvals' => $pendingApprovals,
            'unposted_inventory_documents' => (int) ($warehouseSummary['unposted_documents'] ?? 0),
            'warehouse_discrepancies' => (int) ($warehouseSummary['warehouse_discrepancies'] ?? 0),
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

    public function expenseCloseInsights(int $businessId, VasAccountingPeriod $period): array
    {
        $periodEnd = optional($period->end_date)->toDateString();

        $pendingDocuments = Schema::hasTable('vas_fin_documents')
            ? $this->pendingExpenseDocumentsQuery($businessId, $periodEnd)
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

        return [
            'pending_documents' => $pendingDocuments,
            'outstanding_documents' => $outstandingDocuments,
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

        $definitions = [
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
            'expenses' => [
                'title' => 'Expense advances and claims resolved',
                'status' => ($blockers['pending_expense_documents'] + $blockers['outstanding_expense_documents']) > 0 ? 'blocked' : 'completed',
                'notes' => ($blockers['pending_expense_documents'] + $blockers['outstanding_expense_documents']) > 0
                    ? $blockers['pending_expense_documents'] . ' expense documents still need workflow action and ' . $blockers['outstanding_expense_documents'] . ' posted advances or claims remain open.'
                    : 'Expense workflow backlog is clear and no advances or claims remain outstanding through period end.',
            ],
            'approvals' => [
                'title' => 'Document approval queue cleared',
                'status' => $blockers['pending_approvals'] > 0 ? 'blocked' : 'completed',
                'notes' => $blockers['pending_approvals'] > 0 ? $blockers['pending_approvals'] . ' approval steps are still pending.' : 'Approval queue is clear.',
            ],
            'warehouse' => [
                'title' => 'Warehouse documents posted and reconciled',
                'status' => ($blockers['unposted_inventory_documents'] + $blockers['warehouse_discrepancies']) > 0 ? 'blocked' : 'completed',
                'notes' => ($blockers['unposted_inventory_documents'] + $blockers['warehouse_discrepancies']) > 0
                    ? $blockers['unposted_inventory_documents'] . ' warehouse documents still need posting and ' . $blockers['warehouse_discrepancies'] . ' warehouse discrepancies remain.'
                    : 'Warehouse documents and warehouse coverage checks are clear.',
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
            || $blockers['draft_vouchers'] > 0
            || $blockers['posting_failures'] > 0
            || $blockers['pending_depreciation'] > 0
            || $blockers['unreconciled_bank_lines'] > 0
            || $blockers['pending_treasury_documents'] > 0
            || $blockers['pending_expense_documents'] > 0
            || $blockers['outstanding_expense_documents'] > 0
            || $blockers['pending_approvals'] > 0
            || $blockers['unposted_inventory_documents'] > 0
            || $blockers['warehouse_discrepancies'] > 0;

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
}
