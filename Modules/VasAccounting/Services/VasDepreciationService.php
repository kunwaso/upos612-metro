<?php

namespace Modules\VasAccounting\Services;

use Carbon\Carbon;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasAssetDepreciation;
use Modules\VasAccounting\Entities\VasFixedAsset;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class VasDepreciationService
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

        $assets = VasFixedAsset::query()
            ->where('business_id', $businessId)
            ->whereDate('capitalization_date', '<=', $period->end_date)
            ->where(function ($query) use ($period) {
                $query->whereNull('disposed_at')
                    ->orWhereDate('disposed_at', '>', $period->end_date);
            })
            ->get();

        $created = 0;

        foreach ($assets as $asset) {
            $existing = VasAssetDepreciation::query()
                ->where('business_id', $businessId)
                ->where('fixed_asset_id', $asset->id)
                ->where('accounting_period_id', $period->id)
                ->first();

            if ($existing) {
                continue;
            }

            $amount = round((float) ($asset->monthly_depreciation ?? 0), 4);
            if ($amount <= 0) {
                continue;
            }

            $depreciation = VasAssetDepreciation::create([
                'business_id' => $businessId,
                'fixed_asset_id' => $asset->id,
                'accounting_period_id' => $period->id,
                'depreciation_date' => $period->end_date,
                'amount' => $amount,
                'status' => 'draft',
            ]);

            $voucher = $this->postingService->postVoucherPayload([
                'business_id' => $businessId,
                'voucher_type' => 'depreciation',
                'sequence_key' => 'depreciation',
                'source_type' => 'asset_depreciation',
                'source_id' => (int) $depreciation->id,
                'posting_date' => $period->end_date,
                'document_date' => $period->end_date,
                'description' => 'Monthly depreciation for asset ' . $asset->asset_code,
                'reference' => $asset->asset_code,
                'status' => 'posted',
                'currency_code' => 'VND',
                'created_by' => $userId,
                'lines' => [
                    [
                        'account_id' => (int) $asset->depreciation_expense_account_id,
                        'description' => 'Depreciation expense',
                        'debit' => $amount,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => (int) $asset->accumulated_depreciation_account_id,
                        'description' => 'Accumulated depreciation',
                        'debit' => 0,
                        'credit' => $amount,
                    ],
                ],
            ]);

            $depreciation->status = 'posted';
            $depreciation->voucher_id = (int) $voucher->id;
            $depreciation->posted_at = now();
            $depreciation->save();
            $created++;
        }

        return [
            'period' => $period,
            'assets_considered' => $assets->count(),
            'depreciations_created' => $created,
        ];
    }
}
