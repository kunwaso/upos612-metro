<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasReportSnapshot;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class AccountingJourneyService
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected VasPeriodCloseService $periodCloseService
    ) {
    }

    public function state(int $businessId): array
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $bootstrapStatus = $this->vasUtil->bootstrapStatus($businessId);
        $compliance = $this->vasUtil->complianceCompletionStatus($settings);
        $latestPeriod = Schema::hasTable('vas_accounting_periods')
            ? VasAccountingPeriod::query()
                ->where('business_id', $businessId)
                ->orderByDesc('end_date')
                ->first()
            : null;
        $blockers = $latestPeriod ? $this->periodCloseService->blockers($businessId, $latestPeriod) : [];
        $postedVoucherCount = Schema::hasTable('vas_vouchers')
            ? VasVoucher::query()
                ->where('business_id', $businessId)
                ->where('status', 'posted')
                ->count()
            : 0;
        $statementSnapshotCount = Schema::hasTable('vas_report_snapshots')
            ? VasReportSnapshot::query()
                ->where('business_id', $businessId)
                ->where('report_key', 'financial_statements')
                ->where('status', 'ready')
                ->count()
            : 0;

        $steps = collect([
            $this->makeStep(
                'setup',
                'Setup',
                'vasaccounting.setup.index',
                ! $bootstrapStatus['needs_bootstrap'] && (bool) ($compliance['is_complete'] ?? false),
                (array) ($compliance['blockers'] ?? [])
            ),
            $this->makeStep(
                'opening_readiness',
                'Opening readiness',
                'vasaccounting.chart.index',
                ! $bootstrapStatus['needs_bootstrap'] && $this->vasUtil->isPostingMapComplete($settings),
                $this->vasUtil->isPostingMapComplete($settings) ? [] : ['Mandatory posting map is incomplete.']
            ),
            $this->makeStep(
                'transactions',
                'Transactions',
                'vasaccounting.vouchers.index',
                $postedVoucherCount > 0,
                $postedVoucherCount > 0 ? [] : ['No posted vouchers yet.']
            ),
            $this->makeStep(
                'reconciliation',
                'Reconciliation',
                'vasaccounting.cash_bank.index',
                ((int) ($blockers['unreconciled_bank_lines'] ?? 0)) === 0,
                ((int) ($blockers['unreconciled_bank_lines'] ?? 0)) === 0 ? [] : ['Bank reconciliation still has unresolved items.']
            ),
            $this->makeStep(
                'filing',
                'Filing',
                'vasaccounting.tax.index',
                $this->filingReady($settings),
                $this->filingReady($settings) ? [] : ['Provider setup is incomplete for filing requirements.']
            ),
            $this->makeStep(
                'close',
                'Period close',
                'vasaccounting.closing.index',
                $latestPeriod && $latestPeriod->status === 'closed',
                $this->closeBlockersSummary($blockers)
            ),
            $this->makeStep(
                'statements',
                'Financial statements',
                'vasaccounting.reports.financial_statements',
                $statementSnapshotCount > 0,
                $statementSnapshotCount > 0 ? [] : ['No ready financial statement snapshot for the active profile yet.']
            ),
        ]);

        $completed = $steps->where('status', 'completed')->count();
        $total = max(1, $steps->count());

        return [
            'steps' => $steps->values()->all(),
            'summary' => [
                'completed' => $completed,
                'total' => $total,
                'progress_percent' => (int) floor(($completed / $total) * 100),
                'active_step_key' => (string) optional($steps->firstWhere('status', '!=', 'completed'))['key'],
                'profile_key' => (string) ($compliance['profile_key'] ?? ''),
                'profile_label' => (string) ($compliance['profile_label'] ?? ''),
            ],
        ];
    }

    public function nextActions(int $businessId, int $limit = 3): array
    {
        $state = $this->state($businessId);
        $steps = collect((array) ($state['steps'] ?? []));

        return $steps
            ->reject(fn (array $step) => ($step['status'] ?? '') === 'completed')
            ->take(max(1, $limit))
            ->map(function (array $step): array {
                return [
                    'step_key' => (string) ($step['key'] ?? ''),
                    'label' => (string) ($step['label'] ?? ''),
                    'route' => (string) ($step['route'] ?? ''),
                    'url' => (string) ($step['url'] ?? ''),
                    'status' => (string) ($step['status'] ?? 'blocked'),
                    'reason' => (string) collect((array) ($step['blockers'] ?? []))->first(),
                ];
            })
            ->values()
            ->all();
    }

    public function basicNavigation(int $businessId): array
    {
        $state = $this->state($businessId);
        $steps = collect((array) ($state['steps'] ?? []));
        $current = $steps->firstWhere('status', '!=', 'completed');
        $next = $steps
            ->reject(fn (array $step) => ($step['key'] ?? '') === (string) data_get($current, 'key'))
            ->firstWhere('status', '!=', 'completed');

        return collect([$current, $next])
            ->filter()
            ->map(fn (array $step) => [
                'key' => (string) ($step['key'] ?? ''),
                'label' => (string) ($step['label'] ?? ''),
                'route' => (string) ($step['route'] ?? ''),
                'url' => (string) ($step['url'] ?? ''),
                'status' => (string) ($step['status'] ?? ''),
            ])
            ->values()
            ->all();
    }

    protected function makeStep(string $key, string $label, string $routeName, bool $completed, array $blockers = []): array
    {
        $status = $completed ? 'completed' : ($blockers === [] ? 'in_progress' : 'blocked');

        return [
            'key' => $key,
            'label' => $label,
            'route' => $routeName,
            'url' => route($routeName),
            'status' => $status,
            'blockers' => array_values(array_filter($blockers)),
        ];
    }

    protected function filingReady($settings): bool
    {
        $compliance = $this->vasUtil->complianceCompletionStatus($settings);
        $checks = (array) ($compliance['checks'] ?? []);

        return (bool) ($checks['einvoice_provider_ready'] ?? false)
            && (bool) ($checks['tax_export_provider_ready'] ?? false);
    }

    protected function closeBlockersSummary(array $blockers): array
    {
        $keys = [
            'posting_map_incomplete' => 'Posting map is incomplete.',
            'compliance_checks_incomplete' => 'Compliance baseline checks are incomplete.',
            'draft_vouchers' => 'Draft vouchers remain in the period.',
            'posting_failures' => 'Posting failures remain unresolved.',
            'unreconciled_bank_lines' => 'Bank reconciliation blockers remain.',
            'pending_treasury_documents' => 'Treasury documents are pending.',
            'pending_procurement_documents' => 'Procurement workflow is pending.',
            'pending_expense_documents' => 'Expense workflow is pending.',
        ];

        return collect($keys)
            ->filter(function (string $message, string $key) use ($blockers): bool {
                $value = $blockers[$key] ?? 0;

                return is_bool($value) ? $value : ((int) $value) > 0;
            })
            ->values()
            ->all();
    }
}
