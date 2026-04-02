<?php

namespace Modules\VasAccounting\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalInstance;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalStep;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalMonitorService;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalPolicyResolver;
use Tests\TestCase;

class ExpenseApprovalMonitorServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_marks_pending_high_value_steps_as_overdue_and_escalated(): void
    {
        Carbon::setTestNow('2026-04-01 18:00:00');

        $service = new ExpenseApprovalMonitorService(new ExpenseApprovalPolicyResolver());
        $document = new FinanceDocument([
            'id' => 77,
            'document_family' => 'expense_management',
            'document_type' => 'advance_request',
            'gross_amount' => 15000000,
        ]);

        $instance = new FinanceApprovalInstance([
            'policy_code' => 'ADVANCE_REQUEST_HIGH_VALUE',
            'current_step_no' => 1,
            'started_at' => '2026-04-01 06:00:00',
            'meta' => [
                'threshold_min_amount' => 10000000.0001,
                'threshold_max_amount' => null,
            ],
        ]);

        $step = new FinanceApprovalStep([
            'step_no' => 1,
            'status' => 'pending',
            'approver_role' => 'branch_manager',
            'meta' => [
                'label' => 'Branch cash advance review',
                'sla_hours' => 8,
                'warning_hours' => 2,
                'escalation_role' => 'finance_manager',
                'pending_started_at' => '2026-04-01 06:00:00',
            ],
        ]);

        $instance->setRelation('steps', new EloquentCollection([$step]));
        $document->setRelation('approvalInstances', new EloquentCollection([$instance]));

        $insight = $service->buildInsight($document);

        $this->assertTrue($insight['is_high_value']);
        $this->assertSame('Above 10,000,000.00 VND', $insight['threshold_label']);
        $this->assertSame('overdue', $insight['sla_state']);
        $this->assertSame('Finance manager', $insight['escalation_role_label']);
        $this->assertSame('Branch cash advance review', $insight['current_step_label']);
    }

    public function test_it_marks_pending_standard_steps_as_due_soon_before_breach(): void
    {
        Carbon::setTestNow('2026-04-01 21:30:00');

        $service = new ExpenseApprovalMonitorService(new ExpenseApprovalPolicyResolver());
        $document = new FinanceDocument([
            'id' => 78,
            'document_family' => 'expense_management',
            'document_type' => 'expense_claim',
            'gross_amount' => 2500000,
        ]);

        $instance = new FinanceApprovalInstance([
            'policy_code' => 'EXPENSE_CLAIM_STANDARD',
            'current_step_no' => 1,
            'started_at' => '2026-04-01 00:00:00',
            'meta' => [
                'threshold_min_amount' => null,
                'threshold_max_amount' => 5000000,
            ],
        ]);

        $step = new FinanceApprovalStep([
            'step_no' => 1,
            'status' => 'pending',
            'approver_role' => 'branch_manager',
            'meta' => [
                'label' => 'Branch expense review',
                'sla_hours' => 24,
                'warning_hours' => 4,
                'escalation_role' => 'finance_manager',
                'pending_started_at' => '2026-04-01 00:00:00',
            ],
        ]);

        $instance->setRelation('steps', new EloquentCollection([$step]));
        $document->setRelation('approvalInstances', new EloquentCollection([$instance]));

        $insight = $service->buildInsight($document);

        $this->assertSame('Up to 5,000,000.00 VND', $insight['threshold_label']);
        $this->assertSame('due_soon', $insight['sla_state']);
        $this->assertSame('Branch manager', $insight['current_step_role_label']);
        $this->assertSame('Finance manager', $insight['escalation_role_label']);
    }

    public function test_it_exposes_manual_escalation_metadata_when_present(): void
    {
        Carbon::setTestNow('2026-04-01 18:00:00');

        $service = new ExpenseApprovalMonitorService(new ExpenseApprovalPolicyResolver());
        $document = new FinanceDocument([
            'id' => 79,
            'document_family' => 'expense_management',
            'document_type' => 'expense_claim',
            'gross_amount' => 6500000,
        ]);

        $instance = new FinanceApprovalInstance([
            'policy_code' => 'EXPENSE_CLAIM_MANAGER_REVIEW',
            'current_step_no' => 1,
            'started_at' => '2026-04-01 06:00:00',
        ]);

        $step = new FinanceApprovalStep([
            'step_no' => 1,
            'status' => 'pending',
            'approver_role' => 'finance_manager',
            'meta' => [
                'label' => 'Finance manager review',
                'sla_hours' => 8,
                'warning_hours' => 2,
                'escalation_role' => 'cfo',
                'pending_started_at' => '2026-04-01 06:00:00',
                'last_escalated_at' => '2026-04-01 16:15:00',
                'last_escalation_reason' => 'Month-end claim approval still waiting.',
                'escalation_count' => 2,
                'escalation_dispatch_status' => 'sent',
                'last_dispatch_recipient_count' => 3,
            ],
        ]);

        $instance->setRelation('steps', new EloquentCollection([$step]));
        $document->setRelation('approvalInstances', new EloquentCollection([$instance]));

        $insight = $service->buildInsight($document);

        $this->assertSame('overdue', $insight['sla_state']);
        $this->assertSame('2026-04-01 16:15:00', $insight['last_escalated_at']);
        $this->assertSame('Month-end claim approval still waiting.', $insight['last_escalation_reason']);
        $this->assertSame(2, $insight['escalation_count']);
        $this->assertSame('Sent to 3 recipient(s)', $insight['dispatch_status_label']);
    }

    public function test_it_exposes_dispatch_failure_details_when_present(): void
    {
        Carbon::setTestNow('2026-04-01 18:00:00');

        $service = new ExpenseApprovalMonitorService(new ExpenseApprovalPolicyResolver());
        $document = new FinanceDocument([
            'id' => 80,
            'document_family' => 'expense_management',
            'document_type' => 'expense_claim',
            'gross_amount' => 7200000,
        ]);

        $instance = new FinanceApprovalInstance([
            'policy_code' => 'EXPENSE_CLAIM_MANAGER_REVIEW',
            'current_step_no' => 1,
            'started_at' => '2026-04-01 06:00:00',
        ]);

        $step = new FinanceApprovalStep([
            'step_no' => 1,
            'status' => 'pending',
            'approver_role' => 'finance_manager',
            'meta' => [
                'label' => 'Finance manager review',
                'sla_hours' => 8,
                'warning_hours' => 2,
                'escalation_role' => 'cfo',
                'pending_started_at' => '2026-04-01 06:00:00',
                'escalation_dispatch_status' => 'failed',
                'last_dispatch_error' => 'Queue transport unavailable',
            ],
        ]);

        $instance->setRelation('steps', new EloquentCollection([$step]));
        $document->setRelation('approvalInstances', new EloquentCollection([$instance]));

        $insight = $service->buildInsight($document);

        $this->assertSame('Dispatch failed', $insight['dispatch_status_label']);
        $this->assertSame('Queue transport unavailable', $insight['dispatch_error']);
    }
}
