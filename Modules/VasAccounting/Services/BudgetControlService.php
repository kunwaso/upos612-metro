<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Entities\VasBudget;
use Modules\VasAccounting\Entities\VasBudgetLine;

class BudgetControlService
{
    public function syncBudgetActuals(VasBudget $budget): array
    {
        $budget->loadMissing('lines');
        $updatedLines = 0;
        $snapshots = collect();

        foreach ($budget->lines as $line) {
            $line->actual_amount = $this->actualAmountForLine($budget, $line);
            $line->save();
            $updatedLines++;
            $snapshots->push($this->varianceSnapshot($line->fresh()));
        }

        return [
            'updated_lines' => $updatedLines,
            'snapshots' => $snapshots,
        ];
    }

    public function actualAmountForLine(VasBudget $budget, VasBudgetLine $line): float
    {
        return round((float) DB::table('vas_journal_entries')
            ->where('business_id', (int) $budget->business_id)
            ->whereDate('posting_date', '>=', $budget->start_date->toDateString())
            ->whereDate('posting_date', '<=', $budget->end_date->toDateString())
            ->when($line->account_id, fn ($query) => $query->where('account_id', (int) $line->account_id))
            ->when($line->department_id, fn ($query) => $query->where('department_id', (int) $line->department_id))
            ->when($line->cost_center_id, fn ($query) => $query->where('cost_center_id', (int) $line->cost_center_id))
            ->when($line->project_id, fn ($query) => $query->where('project_id', (int) $line->project_id))
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as actual_total')
            ->value('actual_total'), 4);
    }

    public function varianceSnapshot(VasBudgetLine $line): array
    {
        $budgetAmount = round((float) $line->budget_amount, 4);
        $committedAmount = round((float) $line->committed_amount, 4);
        $actualAmount = round((float) $line->actual_amount, 4);

        return [
            'budget_amount' => $budgetAmount,
            'committed_amount' => $committedAmount,
            'actual_amount' => $actualAmount,
            'variance_amount' => round($budgetAmount - $actualAmount, 4),
            'remaining_amount' => round($budgetAmount - $committedAmount - $actualAmount, 4),
            'is_over_budget' => ($committedAmount + $actualAmount) > ($budgetAmount + 0.0001),
        ];
    }
}
