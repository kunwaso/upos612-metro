<?php

namespace Modules\VasAccounting\Services\Adapters;

use Modules\VasAccounting\Contracts\PayrollBridgeInterface;

class EssentialsPayrollBridgeAdapter implements PayrollBridgeInterface
{
    public function buildVoucherPayload(array $payrollData, array $context = []): array
    {
        $salaryExpenseAccountId = (int) ($context['salary_expense_account_id'] ?? 0);
        $payrollPayableAccountId = (int) ($context['payroll_payable_account_id'] ?? 0);
        $netTotal = round((float) ($payrollData['net_total'] ?? 0), 4);

        return [
            'business_id' => (int) ($payrollData['business_id'] ?? 0),
            'voucher_type' => 'payroll',
            'sequence_key' => (string) ($context['sequence_key'] ?? 'payroll_accrual'),
            'source_type' => (string) ($context['source_type'] ?? ($payrollData['source_type'] ?? 'payroll')),
            'source_id' => (int) ($payrollData['source_id'] ?? 0),
            'business_location_id' => $payrollData['business_location_id'] ?? null,
            'posting_date' => (string) ($payrollData['posting_date'] ?? now()->toDateString()),
            'document_date' => (string) ($payrollData['document_date'] ?? now()->toDateString()),
            'description' => (string) ($payrollData['description'] ?? 'Payroll bridge posting'),
            'reference' => $payrollData['reference'] ?? null,
            'status' => (string) ($context['status'] ?? 'draft'),
            'currency_code' => (string) ($payrollData['currency_code'] ?? 'VND'),
            'created_by' => $payrollData['created_by'] ?? null,
            'module_area' => 'payroll',
            'document_type' => 'payroll_batch',
            'meta' => [
                'bridge_provider' => 'essentials',
                'gross_total' => round((float) ($payrollData['gross_total'] ?? 0), 4),
            ],
            'lines' => array_filter([
                $salaryExpenseAccountId > 0 ? [
                    'account_id' => $salaryExpenseAccountId,
                    'description' => 'Payroll gross expense',
                    'business_location_id' => $payrollData['business_location_id'] ?? null,
                    'debit' => $netTotal,
                    'credit' => 0,
                ] : null,
                $payrollPayableAccountId > 0 ? [
                    'account_id' => $payrollPayableAccountId,
                    'description' => 'Payroll payable',
                    'business_location_id' => $payrollData['business_location_id'] ?? null,
                    'debit' => 0,
                    'credit' => $netTotal,
                ] : null,
            ]),
        ];
    }
}
