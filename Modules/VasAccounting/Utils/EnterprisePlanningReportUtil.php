<?php

namespace Modules\VasAccounting\Utils;

use App\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasBudget;
use Modules\VasAccounting\Entities\VasBudgetLine;
use Modules\VasAccounting\Entities\VasContract;
use Modules\VasAccounting\Entities\VasContractMilestone;
use Modules\VasAccounting\Entities\VasCostCenter;
use Modules\VasAccounting\Entities\VasDepartment;
use Modules\VasAccounting\Entities\VasLoan;
use Modules\VasAccounting\Entities\VasLoanRepaymentSchedule;
use Modules\VasAccounting\Entities\VasPayrollBatch;
use Modules\VasAccounting\Entities\VasProject;
use Modules\VasAccounting\Services\BudgetControlService;

class EnterprisePlanningReportUtil
{
    public function __construct(protected BudgetControlService $budgetControlService)
    {
    }

    public function payrollSummary(int $businessId): array
    {
        $groupRows = $this->payrollGroupRows($businessId, 500);
        $batches = Schema::hasTable('vas_payroll_batches')
            ? VasPayrollBatch::query()->where('business_id', $businessId)->get()
            : collect();

        return [
            'payroll_groups' => $groupRows->count(),
            'bridged_batches' => $batches->count(),
            'accrued_batches' => $batches->filter(fn (VasPayrollBatch $batch) => ! empty(data_get((array) $batch->meta, 'accrual_voucher_id')))->count(),
            'payment_vouchers' => DB::table('vas_vouchers')
                ->where('business_id', $businessId)
                ->where('module_area', 'payroll')
                ->where('voucher_type', 'payroll_payment')
                ->count(),
        ];
    }

    public function payrollGroupRows(int $businessId, int $limit = 30): Collection
    {
        if (! Schema::hasTable('essentials_payroll_groups') || ! Schema::hasTable('essentials_payroll_group_transactions')) {
            return collect();
        }

        $paymentTotals = DB::table('transaction_payments')
            ->selectRaw('transaction_id, SUM(amount) as paid_total')
            ->groupBy('transaction_id');

        $rows = DB::table('essentials_payroll_groups as pg')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'pg.location_id')
            ->leftJoin('essentials_payroll_group_transactions as pgt', 'pgt.payroll_group_id', '=', 'pg.id')
            ->leftJoin('transactions as t', function ($join) {
                $join->on('t.id', '=', 'pgt.transaction_id')
                    ->where('t.type', 'payroll');
            })
            ->leftJoinSub($paymentTotals, 'pt', function ($join) {
                $join->on('pt.transaction_id', '=', 't.id');
            })
            ->where('pg.business_id', $businessId)
            ->groupBy('pg.id', 'pg.name', 'pg.status', 'pg.location_id', 'bl.name')
            ->selectRaw("
                pg.id,
                pg.name,
                pg.status as payroll_group_status,
                pg.location_id,
                bl.name as location_name,
                MIN(t.transaction_date) as payroll_month,
                COUNT(DISTINCT t.expense_for) as employee_count,
                COALESCE(SUM(COALESCE(t.total_before_tax, t.final_total)), 0) as gross_total,
                COALESCE(SUM(COALESCE(t.final_total, 0)), 0) as net_total,
                COALESCE(SUM(COALESCE(pt.paid_total, 0)), 0) as paid_total
            ")
            ->orderByDesc('payroll_month')
            ->orderByDesc('pg.id')
            ->limit($limit)
            ->get();

        $batches = Schema::hasTable('vas_payroll_batches')
            ? VasPayrollBatch::query()->where('business_id', $businessId)->get()->keyBy('payroll_group_id')
            : collect();

        return $rows->map(function ($row) use ($batches) {
            $batch = $batches->get((int) $row->id);

            return [
                'payroll_group_id' => (int) $row->id,
                'group_name' => $row->name,
                'location_name' => $row->location_name ?: '-',
                'payroll_group_status' => $row->payroll_group_status,
                'payroll_month' => $row->payroll_month,
                'employee_count' => (int) $row->employee_count,
                'gross_total' => round((float) $row->gross_total, 4),
                'net_total' => round((float) $row->net_total, 4),
                'paid_total' => round((float) $row->paid_total, 4),
                'batch' => $batch,
                'batch_status' => $batch?->status,
                'accrual_voucher_id' => $batch ? (int) data_get((array) $batch->meta, 'accrual_voucher_id', 0) : null,
            ];
        });
    }

    public function payrollBatchRows(int $businessId, int $limit = 20): Collection
    {
        if (! Schema::hasTable('vas_payroll_batches')) {
            return collect();
        }

        return VasPayrollBatch::query()
            ->with('businessLocation')
            ->where('business_id', $businessId)
            ->latest('payroll_month')
            ->latest('id')
            ->take($limit)
            ->get()
            ->map(function (VasPayrollBatch $batch) {
                return [
                    'batch' => $batch,
                    'accrual_voucher_id' => (int) data_get((array) $batch->meta, 'accrual_voucher_id', 0),
                    'payment_voucher_ids' => collect((array) data_get((array) $batch->meta, 'payment_voucher_ids', []))
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->values(),
                ];
            });
    }

    public function contractSummary(int $businessId): array
    {
        $contracts = Schema::hasTable('vas_contracts')
            ? VasContract::query()->where('business_id', $businessId)->get()
            : collect();
        $milestones = Schema::hasTable('vas_contract_milestones')
            ? VasContractMilestone::query()->where('business_id', $businessId)->get()
            : collect();

        return [
            'contract_count' => $contracts->count(),
            'active_contracts' => $contracts->whereIn('status', ['active', 'completed'])->count(),
            'due_milestones' => $milestones->whereIn('status', ['draft', 'planned'])->filter(fn ($milestone) => ! empty($milestone->billing_date) && $milestone->billing_date->lte(now()))->count(),
            'recognized_revenue' => round((float) $milestones->where('status', 'posted')->sum('revenue_amount'), 4),
        ];
    }

    public function contractRows(int $businessId, int $limit = 20): Collection
    {
        if (! Schema::hasTable('vas_contracts')) {
            return collect();
        }

        $milestoneStats = Schema::hasTable('vas_contract_milestones')
            ? VasContractMilestone::query()
                ->where('business_id', $businessId)
                ->selectRaw('contract_id, COUNT(*) as milestone_count, SUM(CASE WHEN status = "posted" THEN revenue_amount ELSE 0 END) as recognized_total, SUM(retention_amount) as retention_total, MAX(billing_date) as latest_billing_date')
                ->groupBy('contract_id')
                ->get()
                ->keyBy('contract_id')
            : collect();

        return VasContract::query()
            ->with(['contact', 'project', 'costCenter', 'businessLocation'])
            ->where('business_id', $businessId)
            ->latest('signed_at')
            ->latest('id')
            ->take($limit)
            ->get()
            ->map(function (VasContract $contract) use ($milestoneStats) {
                $stats = $milestoneStats->get($contract->id);

                return [
                    'contract' => $contract,
                    'milestone_count' => (int) ($stats->milestone_count ?? 0),
                    'recognized_total' => round((float) ($stats->recognized_total ?? 0), 4),
                    'retention_total' => round((float) ($stats->retention_total ?? 0), 4),
                    'remaining_value' => round(max(0, (float) $contract->contract_value - (float) ($stats->recognized_total ?? 0)), 4),
                    'latest_billing_date' => $stats->latest_billing_date ?? null,
                ];
            });
    }

    public function contractMilestoneRows(int $businessId, int $limit = 30): Collection
    {
        if (! Schema::hasTable('vas_contract_milestones')) {
            return collect();
        }

        return VasContractMilestone::query()
            ->with(['contract.contact', 'contract.project', 'postedVoucher'])
            ->where('business_id', $businessId)
            ->orderByDesc('billing_date')
            ->orderByDesc('id')
            ->take($limit)
            ->get();
    }

    public function loanSummary(int $businessId): array
    {
        $loanRows = $this->loanRows($businessId, 200);
        $scheduleRows = $this->loanScheduleRows($businessId, 500);

        return [
            'loan_count' => $loanRows->count(),
            'active_loans' => $loanRows->filter(fn ($row) => in_array($row['loan']->status, ['active', 'settled'], true))->count(),
            'outstanding_principal' => round((float) $loanRows->sum('outstanding_principal'), 4),
            'due_schedules' => $scheduleRows->whereIn('status', ['planned', 'due', 'overdue'])->filter(fn ($schedule) => $schedule->due_date && $schedule->due_date->lte(now()))->count(),
        ];
    }

    public function loanRows(int $businessId, int $limit = 20): Collection
    {
        if (! Schema::hasTable('vas_loans')) {
            return collect();
        }

        $scheduleStats = Schema::hasTable('vas_loan_repayment_schedules')
            ? VasLoanRepaymentSchedule::query()
                ->where('business_id', $businessId)
                ->selectRaw('loan_id, SUM(CASE WHEN status = "paid" THEN principal_due ELSE 0 END) as principal_paid, SUM(CASE WHEN status = "paid" THEN interest_due ELSE 0 END) as interest_paid, MIN(CASE WHEN status != "paid" THEN due_date ELSE NULL END) as next_due_date')
                ->groupBy('loan_id')
                ->get()
                ->keyBy('loan_id')
            : collect();

        return VasLoan::query()
            ->with(['bankAccount', 'contract'])
            ->where('business_id', $businessId)
            ->latest('disbursement_date')
            ->latest('id')
            ->take($limit)
            ->get()
            ->map(function (VasLoan $loan) use ($scheduleStats) {
                $stats = $scheduleStats->get($loan->id);

                return [
                    'loan' => $loan,
                    'principal_paid' => round((float) ($stats->principal_paid ?? 0), 4),
                    'interest_paid' => round((float) ($stats->interest_paid ?? 0), 4),
                    'outstanding_principal' => round(max(0, (float) $loan->principal_amount - (float) ($stats->principal_paid ?? 0)), 4),
                    'next_due_date' => $stats->next_due_date ?? null,
                ];
            });
    }

    public function loanScheduleRows(int $businessId, int $limit = 25): Collection
    {
        if (! Schema::hasTable('vas_loan_repayment_schedules')) {
            return collect();
        }

        return VasLoanRepaymentSchedule::query()
            ->with(['loan.bankAccount', 'settledVoucher'])
            ->where('business_id', $businessId)
            ->orderBy('due_date')
            ->orderBy('id')
            ->take($limit)
            ->get();
    }

    public function costingSummary(int $businessId): array
    {
        return [
            'departments' => Schema::hasTable('vas_departments') ? VasDepartment::query()->where('business_id', $businessId)->count() : 0,
            'cost_centers' => Schema::hasTable('vas_cost_centers') ? VasCostCenter::query()->where('business_id', $businessId)->count() : 0,
            'projects' => Schema::hasTable('vas_projects') ? VasProject::query()->where('business_id', $businessId)->count() : 0,
            'dimensioned_entries' => Schema::hasTable('vas_journal_entries')
                ? DB::table('vas_journal_entries')
                    ->where('business_id', $businessId)
                    ->where(function ($query) {
                        $query->whereNotNull('department_id')
                            ->orWhereNotNull('cost_center_id')
                            ->orWhereNotNull('project_id');
                    })
                    ->count()
                : 0,
        ];
    }

    public function departmentRows(int $businessId): Collection
    {
        if (! Schema::hasTable('vas_departments')) {
            return collect();
        }

        $activityByDepartment = Schema::hasTable('vas_journal_entries')
            ? DB::table('vas_journal_entries')
                ->where('business_id', $businessId)
                ->whereNotNull('department_id')
                ->selectRaw('department_id, SUM(debit - credit) as actual_total')
                ->groupBy('department_id')
                ->pluck('actual_total', 'department_id')
            : collect();
        $costCenterCounts = Schema::hasTable('vas_cost_centers')
            ? VasCostCenter::query()->where('business_id', $businessId)->get()->groupBy('department_id')->map->count()
            : collect();

        return VasDepartment::query()
            ->with('businessLocation')
            ->where('business_id', $businessId)
            ->orderBy('code')
            ->get()
            ->map(function (VasDepartment $department) use ($activityByDepartment, $costCenterCounts) {
                return [
                    'department' => $department,
                    'cost_center_count' => (int) ($costCenterCounts->get($department->id, 0)),
                    'actual_total' => round((float) ($activityByDepartment[$department->id] ?? 0), 4),
                ];
            });
    }

    public function costCenterRows(int $businessId): Collection
    {
        if (! Schema::hasTable('vas_cost_centers')) {
            return collect();
        }

        $activityByCostCenter = Schema::hasTable('vas_journal_entries')
            ? DB::table('vas_journal_entries')
                ->where('business_id', $businessId)
                ->whereNotNull('cost_center_id')
                ->selectRaw('cost_center_id, SUM(debit - credit) as actual_total')
                ->groupBy('cost_center_id')
                ->pluck('actual_total', 'cost_center_id')
            : collect();
        $projectCounts = Schema::hasTable('vas_projects')
            ? VasProject::query()->where('business_id', $businessId)->get()->groupBy('cost_center_id')->map->count()
            : collect();

        return VasCostCenter::query()
            ->with('department')
            ->where('business_id', $businessId)
            ->orderBy('code')
            ->get()
            ->map(function (VasCostCenter $costCenter) use ($activityByCostCenter, $projectCounts) {
                return [
                    'cost_center' => $costCenter,
                    'project_count' => (int) ($projectCounts->get($costCenter->id, 0)),
                    'actual_total' => round((float) ($activityByCostCenter[$costCenter->id] ?? 0), 4),
                ];
            });
    }

    public function projectRows(int $businessId): Collection
    {
        if (! Schema::hasTable('vas_projects')) {
            return collect();
        }

        $activityByProject = Schema::hasTable('vas_journal_entries')
            ? DB::table('vas_journal_entries')
                ->where('business_id', $businessId)
                ->whereNotNull('project_id')
                ->selectRaw('project_id, SUM(debit - credit) as actual_total')
                ->groupBy('project_id')
                ->pluck('actual_total', 'project_id')
            : collect();

        return VasProject::query()
            ->with(['contact', 'costCenter'])
            ->where('business_id', $businessId)
            ->orderBy('project_code')
            ->get()
            ->map(function (VasProject $project) use ($activityByProject) {
                return [
                    'project' => $project,
                    'actual_total' => round((float) ($activityByProject[$project->id] ?? 0), 4),
                ];
            });
    }

    public function budgetSummary(int $businessId): array
    {
        $varianceRows = $this->budgetVarianceRows($businessId);

        return [
            'budget_count' => Schema::hasTable('vas_budgets') ? VasBudget::query()->where('business_id', $businessId)->count() : 0,
            'active_budgets' => Schema::hasTable('vas_budgets') ? VasBudget::query()->where('business_id', $businessId)->whereIn('status', ['active', 'revised'])->count() : 0,
            'total_budget' => round((float) $varianceRows->sum('budget_amount'), 4),
            'total_actual' => round((float) $varianceRows->sum('actual_amount'), 4),
            'over_budget_lines' => $varianceRows->where('is_over_budget', true)->count(),
        ];
    }

    public function budgetRows(int $businessId): Collection
    {
        if (! Schema::hasTable('vas_budgets')) {
            return collect();
        }

        return VasBudget::query()
            ->with(['department', 'costCenter', 'project', 'lines'])
            ->where('business_id', $businessId)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (VasBudget $budget) {
                $snapshots = $budget->lines->map(fn ($line) => $this->budgetControlService->varianceSnapshot($line));

                return [
                    'budget' => $budget,
                    'line_count' => $budget->lines->count(),
                    'budget_total' => round((float) $budget->lines->sum('budget_amount'), 4),
                    'committed_total' => round((float) $budget->lines->sum('committed_amount'), 4),
                    'actual_total' => round((float) $budget->lines->sum('actual_amount'), 4),
                    'remaining_total' => round((float) $snapshots->sum('remaining_amount'), 4),
                    'over_budget_lines' => $snapshots->where('is_over_budget', true)->count(),
                ];
            });
    }

    public function budgetVarianceRows(int $businessId, ?int $budgetId = null): Collection
    {
        if (! Schema::hasTable('vas_budget_lines')) {
            return collect();
        }

        $lines = DB::table('vas_budget_lines as line')
            ->join('vas_budgets as budget', 'budget.id', '=', 'line.budget_id')
            ->leftJoin('vas_accounts as account', 'account.id', '=', 'line.account_id')
            ->leftJoin('vas_departments as department', 'department.id', '=', 'line.department_id')
            ->leftJoin('vas_cost_centers as cost_center', 'cost_center.id', '=', 'line.cost_center_id')
            ->leftJoin('vas_projects as project', 'project.id', '=', 'line.project_id')
            ->where('line.business_id', $businessId)
            ->when($budgetId, fn ($query) => $query->where('line.budget_id', $budgetId))
            ->select([
                'line.id',
                'line.budget_id',
                'budget.budget_code',
                'budget.name as budget_name',
                'account.account_code',
                'account.account_name',
                'department.name as department_name',
                'cost_center.name as cost_center_name',
                'project.name as project_name',
                'line.budget_amount',
                'line.committed_amount',
                'line.actual_amount',
            ])
            ->orderBy('budget.budget_code')
            ->orderBy('line.id')
            ->get();

        return $lines->map(function ($line) {
            $snapshot = $this->budgetControlService->varianceSnapshot(new VasBudgetLine([
                'budget_amount' => $line->budget_amount,
                'committed_amount' => $line->committed_amount,
                'actual_amount' => $line->actual_amount,
            ]));

            return array_merge((array) $line, $snapshot);
        });
    }

    public function contactOptions(int $businessId): Collection
    {
        return Contact::query()
            ->where('business_id', $businessId)
            ->where('contact_status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'supplier_business_name'])
            ->mapWithKeys(function (Contact $contact) {
                $label = trim($contact->name . ($contact->supplier_business_name ? ' (' . $contact->supplier_business_name . ')' : ''));

                return [$contact->id => $label];
            });
    }
}
