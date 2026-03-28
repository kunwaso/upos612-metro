<?php

namespace Modules\VasAccounting\Services;

use Modules\VasAccounting\Entities\VasLoan;
use Modules\VasAccounting\Entities\VasLoanRepaymentSchedule;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;

class LoanAccountingService
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected VasPostingService $postingService
    ) {
    }

    public function disburse(VasLoan $loan, int $userId, ?string $disbursedAt = null): VasVoucher
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings((int) $loan->business_id);
        $bankAccountId = (int) ($loan->bankAccount?->ledger_account_id ?: data_get((array) $settings->posting_map, 'bank'));
        $liabilityAccountId = (int) data_get((array) $settings->posting_map, 'accounts_payable');
        $amount = round((float) $loan->principal_amount, 4);

        if ($amount <= 0 || $bankAccountId <= 0 || $liabilityAccountId <= 0) {
            throw new RuntimeException('Loan disbursement requires a positive principal and complete bank/liability posting accounts.');
        }

        $postingDate = $disbursedAt ?: ($loan->disbursement_date?->toDateString() ?: now()->toDateString());
        $voucher = $this->postingService->postVoucherPayload([
            'business_id' => (int) $loan->business_id,
            'voucher_type' => 'loan_disbursement',
            'sequence_key' => 'loan_disbursement',
            'source_type' => 'loan_disbursement',
            'source_id' => (int) $loan->id,
            'posting_date' => $postingDate,
            'document_date' => $postingDate,
            'description' => 'Loan disbursement for ' . $loan->loan_no,
            'reference' => $loan->loan_no,
            'status' => 'posted',
            'currency_code' => $loan->bankAccount?->currency_code ?: 'VND',
            'created_by' => $userId,
            'module_area' => 'loans',
            'document_type' => 'loan_disbursement',
            'meta' => [
                'loan_id' => (int) $loan->id,
                'contract_id' => $loan->contract_id,
            ],
            'lines' => [
                [
                    'account_id' => $bankAccountId,
                    'contract_id' => $loan->contract_id,
                    'description' => 'Loan proceeds received',
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $liabilityAccountId,
                    'contract_id' => $loan->contract_id,
                    'description' => 'Recognize loan liability',
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ]);

        $loan->status = $loan->status === 'settled' ? 'settled' : 'active';
        $loan->meta = array_replace((array) $loan->meta, [
            'disbursement_voucher_id' => (int) $voucher->id,
            'disbursed_at' => $postingDate,
            'disbursed_by' => $userId,
        ]);
        $loan->save();

        return $voucher;
    }

    public function settleSchedule(VasLoanRepaymentSchedule $schedule, int $userId, ?string $settledAt = null): VasVoucher
    {
        if ($schedule->settled_voucher_id) {
            $existingVoucher = VasVoucher::query()
                ->where('business_id', (int) $schedule->business_id)
                ->find((int) $schedule->settled_voucher_id);

            if ($existingVoucher) {
                return $existingVoucher;
            }
        }

        $loan = $schedule->loan;
        if (! $loan) {
            throw new RuntimeException('The repayment schedule is not linked to a loan.');
        }

        $settings = $this->vasUtil->getOrCreateBusinessSettings((int) $loan->business_id);
        $cashOrBankAccountId = (int) ($loan->bankAccount?->ledger_account_id ?: data_get((array) $settings->posting_map, 'bank'));
        $liabilityAccountId = (int) data_get((array) $settings->posting_map, 'accounts_payable');
        $interestExpenseAccountId = (int) data_get((array) $settings->posting_map, 'expense');
        $principalAmount = round((float) $schedule->principal_due, 4);
        $interestAmount = round((float) $schedule->interest_due, 4);
        $totalAmount = round($principalAmount + $interestAmount, 4);

        if ($totalAmount <= 0 || $cashOrBankAccountId <= 0 || $liabilityAccountId <= 0) {
            throw new RuntimeException('Loan repayment requires a positive repayment amount and complete bank/liability posting accounts.');
        }

        $lines = [];
        if ($principalAmount > 0) {
            $lines[] = [
                'account_id' => $liabilityAccountId,
                'contract_id' => $loan->contract_id,
                'description' => 'Loan principal repayment',
                'debit' => $principalAmount,
                'credit' => 0,
            ];
        }

        if ($interestAmount > 0) {
            if ($interestExpenseAccountId <= 0) {
                throw new RuntimeException('Loan repayment interest requires an expense posting account.');
            }

            $lines[] = [
                'account_id' => $interestExpenseAccountId,
                'contract_id' => $loan->contract_id,
                'description' => 'Loan interest expense',
                'debit' => $interestAmount,
                'credit' => 0,
            ];
        }

        $lines[] = [
            'account_id' => $cashOrBankAccountId,
            'contract_id' => $loan->contract_id,
            'description' => 'Cash or bank settlement for loan repayment',
            'debit' => 0,
            'credit' => $totalAmount,
        ];

        $postingDate = $settledAt ?: ($schedule->due_date?->toDateString() ?: now()->toDateString());
        $voucher = $this->postingService->postVoucherPayload([
            'business_id' => (int) $loan->business_id,
            'voucher_type' => 'loan_repayment',
            'sequence_key' => 'loan_repayment',
            'source_type' => 'loan_repayment_schedule',
            'source_id' => (int) $schedule->id,
            'posting_date' => $postingDate,
            'document_date' => $postingDate,
            'description' => 'Loan repayment for ' . $loan->loan_no,
            'reference' => $loan->loan_no,
            'status' => 'posted',
            'currency_code' => $loan->bankAccount?->currency_code ?: 'VND',
            'created_by' => $userId,
            'module_area' => 'loans',
            'document_type' => 'loan_repayment',
            'meta' => [
                'loan_id' => (int) $loan->id,
                'contract_id' => $loan->contract_id,
            ],
            'lines' => $lines,
        ]);

        $schedule->status = 'paid';
        $schedule->settled_voucher_id = (int) $voucher->id;
        $schedule->meta = array_replace((array) $schedule->meta, [
            'settled_at' => $postingDate,
            'settled_by' => $userId,
        ]);
        $schedule->save();

        $loan->status = $this->outstandingPrincipal($loan->fresh('repaymentSchedules')) <= 0 ? 'settled' : 'active';
        $loan->meta = array_replace((array) $loan->meta, [
            'principal_paid' => round((float) $loan->repaymentSchedules()->where('status', 'paid')->sum('principal_due'), 4),
            'interest_paid' => round((float) $loan->repaymentSchedules()->where('status', 'paid')->sum('interest_due'), 4),
        ]);
        $loan->save();

        return $voucher;
    }

    public function outstandingPrincipal(VasLoan $loan): float
    {
        $paidPrincipal = round((float) $loan->repaymentSchedules()->where('status', 'paid')->sum('principal_due'), 4);

        return round(max(0, (float) $loan->principal_amount - $paidPrincipal), 4);
    }
}
