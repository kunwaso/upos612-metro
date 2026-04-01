<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Services\Expense\ExpenseSettlementService;
use Tests\TestCase;

class ExpenseSettlementServiceTest extends TestCase
{
    public function test_it_calculates_remaining_advance_amount_from_posted_settlements(): void
    {
        $service = new ExpenseSettlementService();
        $advanceRequest = new FinanceDocument([
            'document_type' => 'advance_request',
            'gross_amount' => 1000000,
        ]);

        $summary = $service->calculateAdvanceRequestSummary(
            $advanceRequest,
            [
                new FinanceDocument(['document_type' => 'expense_claim', 'workflow_status' => 'posted', 'gross_amount' => 600000]),
            ],
            [
                new FinanceDocument(['document_type' => 'advance_settlement', 'workflow_status' => 'posted', 'gross_amount' => 400000]),
                new FinanceDocument(['document_type' => 'advance_settlement', 'workflow_status' => 'approved', 'gross_amount' => 100000]),
            ]
        );

        $this->assertSame(1, $summary['linked_claim_count']);
        $this->assertSame('600000.0000', $summary['linked_claim_amount']);
        $this->assertSame('400000.0000', $summary['posted_settlement_amount']);
        $this->assertSame('100000.0000', $summary['pending_settlement_amount']);
        $this->assertSame('600000.0000', $summary['remaining_advance_amount']);
        $this->assertSame('partially_settled', $summary['settlement_status']);
    }

    public function test_it_calculates_claim_outstanding_after_settlement_and_reimbursement(): void
    {
        $service = new ExpenseSettlementService();
        $expenseClaim = new FinanceDocument([
            'document_type' => 'expense_claim',
            'gross_amount' => 1200000,
        ]);

        $summary = $service->calculateExpenseClaimSummary(
            $expenseClaim,
            [
                new FinanceDocument(['document_type' => 'advance_settlement', 'workflow_status' => 'posted', 'gross_amount' => 700000]),
            ],
            [
                new FinanceDocument(['document_type' => 'reimbursement_voucher', 'workflow_status' => 'posted', 'gross_amount' => 300000]),
                new FinanceDocument(['document_type' => 'reimbursement_voucher', 'workflow_status' => 'approved', 'gross_amount' => 100000]),
            ],
            [
                new FinanceDocument(['document_type' => 'advance_request', 'document_no' => 'ADV-2026-001', 'workflow_status' => 'posted']),
            ]
        );

        $this->assertSame('ADV-2026-001', $summary['linked_advance_document_no']);
        $this->assertSame('700000.0000', $summary['posted_settlement_amount']);
        $this->assertSame('300000.0000', $summary['posted_reimbursement_amount']);
        $this->assertSame('100000.0000', $summary['pending_reimbursement_amount']);
        $this->assertSame('200000.0000', $summary['outstanding_amount']);
        $this->assertSame('partially_settled', $summary['settlement_status']);
    }
}
