<?php

namespace Modules\VasAccounting\Services;

use Carbon\Carbon;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasTool;
use Modules\VasAccounting\Entities\VasToolAmortization;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class VasToolAmortizationService
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected VasPostingService $postingService
    ) {
    }

    public function run(int $businessId, ?int $periodId, int $userId): array
    {
        $period = $periodId
            ? VasAccountingPeriod::query()->where('business_id', $businessId)->findOrFail($periodId)
            : $this->vasUtil->resolvePeriodForDate($businessId, Carbon::today());

        $tools = VasTool::query()
            ->where('business_id', $businessId)
            ->whereIn('status', ['active', 'issued'])
            ->whereNotNull('start_amortization_at')
            ->whereDate('start_amortization_at', '<=', $period->end_date)
            ->get();

        $created = 0;

        foreach ($tools as $tool) {
            $existing = VasToolAmortization::query()
                ->where('business_id', $businessId)
                ->where('tool_id', $tool->id)
                ->where('accounting_period_id', $period->id)
                ->first();

            if ($existing) {
                continue;
            }

            $amount = min($this->scheduledAmountForTool($tool), (float) $tool->remaining_value);
            if ($amount <= 0) {
                $this->syncToolStatus($tool);
                $tool->save();
                continue;
            }

            $amortization = VasToolAmortization::create([
                'business_id' => $businessId,
                'tool_id' => $tool->id,
                'accounting_period_id' => $period->id,
                'amortization_date' => $period->end_date,
                'amount' => $amount,
                'status' => 'draft',
                'meta' => [
                    'scheduled_amount' => $this->scheduledAmountForTool($tool),
                ],
            ]);

            $voucher = $this->postingService->postVoucherPayload([
                'business_id' => $businessId,
                'voucher_type' => 'tool_amortization',
                'sequence_key' => 'tool_amortization',
                'source_type' => 'tool_amortization',
                'source_id' => (int) $amortization->id,
                'posting_date' => $period->end_date,
                'document_date' => $period->end_date,
                'description' => 'Tool amortization for ' . $tool->tool_code,
                'reference' => $tool->tool_code,
                'status' => 'posted',
                'currency_code' => 'VND',
                'created_by' => $userId,
                'business_location_id' => $tool->business_location_id,
                'meta' => [
                    'tool_id' => (int) $tool->id,
                ],
                'lines' => [
                    [
                        'account_id' => (int) $tool->expense_account_id,
                        'description' => 'Tool amortization expense',
                        'debit' => $amount,
                        'credit' => 0,
                        'business_location_id' => $tool->business_location_id,
                        'department_id' => $tool->department_id,
                        'cost_center_id' => $tool->cost_center_id,
                        'project_id' => $tool->project_id,
                    ],
                    [
                        'account_id' => (int) $tool->asset_account_id,
                        'description' => 'Tool remaining value reduction',
                        'debit' => 0,
                        'credit' => $amount,
                        'business_location_id' => $tool->business_location_id,
                        'department_id' => $tool->department_id,
                        'cost_center_id' => $tool->cost_center_id,
                        'project_id' => $tool->project_id,
                    ],
                ],
            ]);

            $amortization->status = 'posted';
            $amortization->voucher_id = (int) $voucher->id;
            $amortization->posted_at = now();
            $amortization->save();

            $tool->remaining_value = round(max(0, (float) $tool->remaining_value - $amount), 4);
            $this->syncToolStatus($tool);
            $tool->save();
            $created++;
        }

        return [
            'period' => $period,
            'tools_considered' => $tools->count(),
            'amortizations_created' => $created,
        ];
    }

    public function scheduledAmountForTool(VasTool $tool): float
    {
        $months = max(1, (int) ($tool->amortization_months ?: 1));
        $cost = max(0, (float) $tool->original_cost);

        return round($cost / $months, 4);
    }

    protected function syncToolStatus(VasTool $tool): void
    {
        if ((float) $tool->remaining_value <= 0) {
            $tool->remaining_value = 0;
            $tool->status = 'fully_amortized';
        } elseif ($tool->status === 'draft') {
            $tool->status = 'active';
        }
    }
}
