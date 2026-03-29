<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasAssetDepreciation;
use Modules\VasAccounting\Entities\VasCloseChecklist;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasVoucher;
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

        $unreconciledBankLines = Schema::hasTable('vas_bank_statement_lines')
            ? (int) DB::table('vas_bank_statement_lines')
                ->where('business_id', $businessId)
                ->where('match_status', 'unmatched')
                ->when($periodEnd, fn ($query) => $query->whereDate('transaction_date', '<=', $periodEnd))
                ->count()
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
            'pending_approvals' => $pendingApprovals,
            'unposted_inventory_documents' => (int) ($warehouseSummary['unposted_documents'] ?? 0),
            'warehouse_discrepancies' => (int) ($warehouseSummary['warehouse_discrepancies'] ?? 0),
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
                'notes' => $blockers['unreconciled_bank_lines'] > 0 ? $blockers['unreconciled_bank_lines'] . ' bank statement lines remain unmatched.' : 'No unmatched bank statement lines remain.',
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
}
