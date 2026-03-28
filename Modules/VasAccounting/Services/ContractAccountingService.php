<?php

namespace Modules\VasAccounting\Services;

use Modules\VasAccounting\Entities\VasContractMilestone;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;

class ContractAccountingService
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected VasPostingService $postingService
    ) {
    }

    public function postMilestone(VasContractMilestone $milestone, int $userId, ?string $postedAt = null): VasVoucher
    {
        if ($milestone->posted_voucher_id) {
            $existingVoucher = VasVoucher::query()
                ->where('business_id', (int) $milestone->business_id)
                ->find((int) $milestone->posted_voucher_id);

            if ($existingVoucher) {
                return $existingVoucher;
            }
        }

        $contract = $milestone->contract;
        if (! $contract) {
            throw new RuntimeException('The selected milestone is not linked to a contract.');
        }

        $settings = $this->vasUtil->getOrCreateBusinessSettings((int) $contract->business_id);
        $receivableAccountId = (int) data_get((array) $settings->posting_map, 'accounts_receivable');
        $revenueAccountId = (int) data_get((array) $settings->posting_map, 'revenue');
        $amount = round((float) $milestone->revenue_amount, 4);

        if ($amount <= 0 || $receivableAccountId <= 0 || $revenueAccountId <= 0) {
            throw new RuntimeException('Contract milestone posting requires a positive revenue amount and complete receivable/revenue posting accounts.');
        }

        $postingDate = $postedAt ?: ($milestone->billing_date?->toDateString() ?: ($milestone->milestone_date?->toDateString() ?: now()->toDateString()));
        $voucher = $this->postingService->postVoucherPayload([
            'business_id' => (int) $contract->business_id,
            'voucher_type' => 'contract_accrual',
            'sequence_key' => 'contract_accrual',
            'source_type' => 'contract_milestone',
            'source_id' => (int) $milestone->id,
            'contact_id' => $contract->contact_id,
            'business_location_id' => $contract->business_location_id,
            'posting_date' => $postingDate,
            'document_date' => $postingDate,
            'description' => 'Contract milestone ' . $milestone->milestone_no . ' for ' . $contract->contract_no,
            'reference' => $milestone->milestone_no,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => $userId,
            'module_area' => 'contracts',
            'document_type' => 'contract_milestone',
            'meta' => [
                'contract_id' => (int) $contract->id,
                'advance_amount' => round((float) $milestone->advance_amount, 4),
                'retention_amount' => round((float) $milestone->retention_amount, 4),
            ],
            'lines' => [
                [
                    'account_id' => $receivableAccountId,
                    'contact_id' => $contract->contact_id,
                    'business_location_id' => $contract->business_location_id,
                    'cost_center_id' => $contract->cost_center_id,
                    'project_id' => $contract->project_id,
                    'contract_id' => $contract->id,
                    'description' => 'Recognize receivable for ' . $milestone->milestone_no,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $revenueAccountId,
                    'contact_id' => $contract->contact_id,
                    'business_location_id' => $contract->business_location_id,
                    'cost_center_id' => $contract->cost_center_id,
                    'project_id' => $contract->project_id,
                    'contract_id' => $contract->id,
                    'description' => 'Recognize contract revenue for ' . $milestone->milestone_no,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ]);

        $milestone->status = 'posted';
        $milestone->posted_voucher_id = (int) $voucher->id;
        $milestone->meta = array_replace((array) $milestone->meta, [
            'posted_by' => $userId,
            'posted_at' => $postingDate,
        ]);
        $milestone->save();

        $recognizedTotal = round((float) $contract->milestones()->where('status', 'posted')->sum('revenue_amount'), 4);
        $contract->status = $recognizedTotal > 0 && $recognizedTotal >= (float) $contract->contract_value && (float) $contract->contract_value > 0
            ? 'completed'
            : ($contract->status === 'draft' ? 'active' : $contract->status);
        $contract->meta = array_replace((array) $contract->meta, [
            'recognized_total' => $recognizedTotal,
            'unrecognized_total' => round(max(0, (float) $contract->contract_value - $recognizedTotal), 4),
            'retention_tracked_total' => round((float) $contract->milestones()->sum('retention_amount'), 4),
        ]);
        $contract->save();

        return $voucher;
    }
}
