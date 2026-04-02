<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalPolicyResolver;
use Tests\TestCase;

class ExpenseApprovalPolicyResolverTest extends TestCase
{
    public function test_it_resolves_single_step_policy_for_standard_expense_claims(): void
    {
        $resolver = new ExpenseApprovalPolicyResolver();
        $document = new FinanceDocument([
            'document_family' => 'expense_management',
            'document_type' => 'expense_claim',
            'gross_amount' => 2500000,
        ]);

        $policy = $resolver->resolve($document);

        $this->assertSame('EXPENSE_CLAIM_STANDARD', $policy['policy_code']);
        $this->assertTrue($policy['maker_checker']);
        $this->assertCount(1, $policy['steps']);
        $this->assertSame('branch_manager', $policy['steps'][0]['approver_role']);
        $this->assertSame(24, $policy['steps'][0]['sla_hours']);
        $this->assertSame(4, $policy['steps'][0]['warning_hours']);
        $this->assertSame('finance_manager', $policy['steps'][0]['escalation_role']);
        $this->assertSame(5000000.0, $policy['threshold_max_amount']);
    }

    public function test_it_resolves_multi_step_policy_for_high_value_advance_requests(): void
    {
        $resolver = new ExpenseApprovalPolicyResolver();
        $document = new FinanceDocument([
            'document_family' => 'expense_management',
            'document_type' => 'advance_request',
            'gross_amount' => 15000000,
        ]);

        $policy = $resolver->resolve($document);

        $this->assertSame('ADVANCE_REQUEST_HIGH_VALUE', $policy['policy_code']);
        $this->assertCount(2, $policy['steps']);
        $this->assertSame('branch_manager', $policy['steps'][0]['approver_role']);
        $this->assertSame('finance_manager', $policy['steps'][1]['approver_role']);
        $this->assertSame(8, $policy['steps'][0]['sla_hours']);
        $this->assertSame(12, $policy['steps'][1]['sla_hours']);
        $this->assertSame(10000000.0001, $policy['threshold_min_amount']);
        $this->assertNull($policy['threshold_max_amount']);
    }
}
