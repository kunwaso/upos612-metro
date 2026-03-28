<?php

namespace Modules\VasAccounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Essentials\Entities\PayrollGroup;
use Modules\VasAccounting\Entities\VasPayrollBatch;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;

class VasPayrollBridgeService
{
    public function __construct(
        protected PayrollBridgeManager $payrollBridgeManager,
        protected VasPostingService $postingService,
        protected VasAccountingUtil $vasUtil
    ) {
    }

    public function bridgeGroup(int $businessId, int $payrollGroupId, int $userId): array
    {
        [$group, $transactions, $paymentTotal, $batch] = $this->syncBatchRecord($businessId, $payrollGroupId);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $provider = (string) (((array) $settings->integration_settings)['payroll_bridge_provider'] ?? 'essentials');

        $payload = $this->payrollBridgeManager->resolve($provider)->buildVoucherPayload([
            'business_id' => $businessId,
            'source_id' => (int) $batch->id,
            'source_type' => 'payroll_batch',
            'business_location_id' => $batch->business_location_id,
            'posting_date' => $batch->payroll_month?->copy()->endOfMonth()->toDateString() ?: now()->toDateString(),
            'document_date' => $batch->payroll_month?->copy()->endOfMonth()->toDateString() ?: now()->toDateString(),
            'reference' => $batch->reference_no,
            'description' => 'Payroll accrual bridge for ' . $group->name,
            'gross_total' => (float) $batch->gross_total,
            'net_total' => (float) $batch->net_total,
            'currency_code' => 'VND',
            'created_by' => $userId,
        ], [
            'provider' => $provider,
            'source_type' => 'payroll_batch',
            'sequence_key' => 'payroll_accrual',
            'status' => 'posted',
            'salary_expense_account_id' => (int) data_get((array) $settings->posting_map, 'expense'),
            'payroll_payable_account_id' => (int) data_get((array) $settings->posting_map, 'accounts_payable'),
        ]);

        $voucher = $this->postingService->postVoucherPayload($payload);
        $batch->meta = array_replace((array) $batch->meta, [
            'provider' => $provider,
            'accrual_voucher_id' => (int) $voucher->id,
            'last_accrued_at' => now()->toDateTimeString(),
            'last_accrued_by' => $userId,
        ]);
        $batch->status = $this->batchStatus($voucher, $paymentTotal, (float) $batch->net_total);
        $batch->finalized_at = $batch->finalized_at ?: now();
        $batch->save();

        return [
            'group' => $group,
            'transactions' => $transactions,
            'voucher' => $voucher,
            'batch' => $batch->fresh(),
        ];
    }

    public function bridgePayments(int $businessId, int $payrollGroupId, int $userId): array
    {
        [$group, $transactions, $paymentTotal, $batch] = $this->syncBatchRecord($businessId, $payrollGroupId);
        $paymentIds = DB::table('transaction_payments')
            ->whereIn('transaction_id', $transactions->pluck('id')->all())
            ->orderBy('paid_on')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $vouchers = collect();
        foreach ($paymentIds as $paymentId) {
            $vouchers->push($this->postingService->processSourceDocument('transaction_payment', $paymentId, [
                'business_id' => $businessId,
                'source_context' => 'payroll_bridge',
            ]));
        }

        $accrualVoucher = null;
        $accrualVoucherId = (int) data_get((array) $batch->meta, 'accrual_voucher_id', 0);
        if ($accrualVoucherId > 0) {
            $accrualVoucher = VasVoucher::query()->where('business_id', $businessId)->find($accrualVoucherId);
        }

        $batch->meta = array_replace((array) $batch->meta, [
            'payment_total' => $paymentTotal,
            'payment_voucher_ids' => $vouchers->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'payment_bridge_count' => $paymentIds->count(),
            'last_payment_bridge_at' => now()->toDateTimeString(),
            'last_payment_bridge_by' => $userId,
        ]);
        $batch->status = $this->batchStatus($accrualVoucher, $paymentTotal, (float) $batch->net_total);
        $batch->save();

        return [
            'group' => $group,
            'transactions' => $transactions,
            'payments_bridged' => $paymentIds->count(),
            'batch' => $batch->fresh(),
        ];
    }

    public function syncBatchRecord(int $businessId, int $payrollGroupId): array
    {
        $group = PayrollGroup::query()
            ->where('business_id', $businessId)
            ->findOrFail($payrollGroupId);

        $transactions = $group->payrollGroupTransactions()
            ->where('transactions.business_id', $businessId)
            ->where('transactions.type', 'payroll')
            ->orderBy('transactions.transaction_date')
            ->get();

        if ($transactions->isEmpty()) {
            throw new RuntimeException('No finalized Essentials payroll transactions were found for this payroll group.');
        }

        $payrollMonth = Carbon::parse((string) optional($transactions->first())->transaction_date)->startOfMonth();
        $grossTotal = round((float) $transactions->sum(fn ($transaction) => (float) ($transaction->total_before_tax ?: $transaction->final_total)), 4);
        $netTotal = round((float) $transactions->sum('final_total'), 4);
        $paymentTotal = round((float) DB::table('transaction_payments')
            ->whereIn('transaction_id', $transactions->pluck('id')->all())
            ->sum('amount'), 4);

        $existingBatch = VasPayrollBatch::query()
            ->where('business_id', $businessId)
            ->where('payroll_group_id', (int) $group->id)
            ->first();

        $batch = VasPayrollBatch::updateOrCreate(
            [
                'business_id' => $businessId,
                'payroll_group_id' => (int) $group->id,
            ],
            [
                'business_location_id' => $group->location_id,
                'reference_no' => 'PAYROLL-' . str_pad((string) $group->id, 4, '0', STR_PAD_LEFT) . '-' . $payrollMonth->format('Ym'),
                'payroll_month' => $payrollMonth->toDateString(),
                'gross_total' => $grossTotal,
                'net_total' => $netTotal,
                'status' => 'finalized',
                'finalized_at' => now(),
                'meta' => array_replace((array) optional($existingBatch)->meta, [
                    'group_name' => $group->name,
                    'employee_count' => $transactions->pluck('expense_for')->filter()->unique()->count(),
                    'transaction_ids' => $transactions->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                    'payment_total' => $paymentTotal,
                ]),
            ]
        );

        return [$group, $transactions, $paymentTotal, $batch];
    }

    protected function batchStatus(?VasVoucher $accrualVoucher, float $paymentTotal, float $netTotal): string
    {
        if ($accrualVoucher && $paymentTotal >= $netTotal && $netTotal > 0) {
            return 'paid';
        }

        if ($accrualVoucher && $paymentTotal > 0) {
            return 'partial';
        }

        if ($accrualVoucher) {
            return 'posted';
        }

        return 'finalized';
    }
}
