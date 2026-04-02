<?php

namespace Modules\VasAccounting\Services\WorkflowApproval;

use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Application\DTOs\ApprovalStateView;
use Modules\VasAccounting\Contracts\ApprovalWorkflowServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalInstance;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalStep;
use RuntimeException;

class ApprovalWorkflowService implements ApprovalWorkflowServiceInterface
{
    public function __construct(
        protected MakerCheckerGuard $makerCheckerGuard,
        protected ExpenseApprovalPolicyResolver $expenseApprovalPolicyResolver,
        protected ExpenseApprovalEscalationDispatchService $expenseApprovalEscalationDispatchService
    )
    {
    }

    public function start(FinanceDocument $document, ActionContext $context): FinanceApprovalInstance
    {
        return DB::transaction(function () use ($document, $context) {
            $document = FinanceDocument::query()->findOrFail($document->id);
            $existing = FinanceApprovalInstance::query()
                ->with('steps')
                ->where('document_id', $document->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            $policy = $this->resolvePolicy($document);

            $instance = FinanceApprovalInstance::query()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'policy_code' => $policy['policy_code'],
                'status' => 'pending',
                'current_step_no' => 1,
                'started_at' => now(),
                'meta' => [
                    'document_family' => $document->document_family,
                    'document_type' => $document->document_type,
                    'maker_checker' => $policy['maker_checker'],
                    'threshold_max_amount' => $policy['threshold_max_amount'],
                    'threshold_min_amount' => $policy['threshold_min_amount'],
                ],
            ]);

            foreach ($policy['steps'] as $index => $step) {
                FinanceApprovalStep::query()->create([
                    'business_id' => $document->business_id,
                    'approval_instance_id' => $instance->id,
                    'step_no' => (int) $step['step_no'],
                    'approver_role' => $step['approver_role'],
                    'permission_code' => $step['permission_code'],
                    'status' => $index === 0 ? 'pending' : 'queued',
                    'meta' => [
                        'submitted_by' => $context->userId(),
                        'label' => $step['label'],
                        'sla_hours' => $step['sla_hours'] ?? null,
                        'warning_hours' => $step['warning_hours'] ?? null,
                        'escalation_role' => $step['escalation_role'] ?? null,
                        'pending_started_at' => $index === 0 ? now()->toDateTimeString() : null,
                    ],
                ]);
            }

            $this->recordAudit($document, 'workflow.started', $context, null, [
                'approval_instance_id' => $instance->id,
                'status' => 'pending',
            ]);

            return $instance->fresh('steps');
        });
    }

    public function approve(FinanceDocument $document, ActionContext $context): FinanceApprovalInstance
    {
        return DB::transaction(function () use ($document, $context) {
            $document = FinanceDocument::query()->findOrFail($document->id);
            $instance = FinanceApprovalInstance::query()
                ->with('steps')
                ->where('document_id', $document->id)
                ->latest('id')
                ->first();

            if (! $instance) {
                if (config('vasaccounting.approval_defaults.finance_document_defaults.auto_approve_without_steps', false)) {
                    $instance = $this->start($document, $context);
                } else {
                    throw new RuntimeException('Finance document approval has not been started.');
                }
            }

            $step = $instance->steps->firstWhere('status', 'pending');
            if (! $step) {
                return $instance;
            }

            $this->makerCheckerGuard->assertCanApprove($document, $context->userId());

            $step->status = 'approved';
            $step->decision = 'approved';
            $step->reason = $context->reason();
            $step->acted_at = now();
            $step->approver_user_id = $step->approver_user_id ?: $context->userId();
            $step->save();

            $nextStep = $instance->steps->firstWhere('status', 'queued');

            if ($nextStep) {
                $nextStepMeta = (array) $nextStep->meta;
                $nextStep->status = 'pending';
                $nextStep->meta = array_merge($nextStepMeta, [
                    'pending_started_at' => now()->toDateTimeString(),
                ]);
                $nextStep->save();

                $instance->status = 'in_progress';
                $instance->current_step_no = $nextStep->step_no;
                $instance->completed_at = null;
            } else {
                $instance->status = 'approved';
                $instance->current_step_no = $step->step_no;
                $instance->completed_at = now();
            }

            $instance->save();

            $this->recordAudit($document, 'workflow.approved', $context, [
                'approval_instance_id' => $instance->id,
                'previous_status' => 'pending',
            ], [
                'approval_instance_id' => $instance->id,
                'status' => $instance->status,
                'approved_step_no' => $step->step_no,
                'next_step_no' => $nextStep?->step_no,
            ]);

            return $instance->fresh('steps');
        });
    }

    public function reject(FinanceDocument $document, ActionContext $context): FinanceApprovalInstance
    {
        return DB::transaction(function () use ($document, $context) {
            $document = FinanceDocument::query()->findOrFail($document->id);
            $instance = FinanceApprovalInstance::query()
                ->with('steps')
                ->where('document_id', $document->id)
                ->latest('id')
                ->first();

            if (! $instance) {
                throw new RuntimeException('Finance document approval has not been started.');
            }

            if (in_array($instance->status, ['approved', 'rejected'], true)) {
                return $instance;
            }

            $step = $instance->steps->firstWhere('status', 'pending');
            if (! $step) {
                throw new RuntimeException('Finance document does not have a pending approval step to reject.');
            }

            $step->status = 'rejected';
            $step->decision = 'rejected';
            $step->reason = $context->reason();
            $step->acted_at = now();
            $step->approver_user_id = $step->approver_user_id ?: $context->userId();
            $step->save();

            $instance->steps
                ->filter(fn (FinanceApprovalStep $queuedStep) => $queuedStep->status === 'queued')
                ->each(function (FinanceApprovalStep $queuedStep) {
                    $queuedStep->status = 'cancelled';
                    $queuedStep->decision = 'cancelled';
                    $queuedStep->acted_at = now();
                    $queuedStep->save();
                });

            $instance->status = 'rejected';
            $instance->current_step_no = $step->step_no;
            $instance->completed_at = now();
            $instance->meta = array_merge((array) $instance->meta, [
                'last_rejected_step_no' => $step->step_no,
                'last_rejected_by' => $context->userId(),
                'last_rejected_reason' => $context->reason(),
            ]);
            $instance->save();

            $this->recordAudit($document, 'workflow.rejected', $context, [
                'approval_instance_id' => $instance->id,
                'previous_status' => 'in_progress',
            ], [
                'approval_instance_id' => $instance->id,
                'status' => 'rejected',
                'rejected_step_no' => $step->step_no,
                'reason' => $context->reason(),
            ]);

            return $instance->fresh('steps');
        });
    }

    public function escalate(FinanceDocument $document, ActionContext $context): FinanceApprovalInstance
    {
        return DB::transaction(function () use ($document, $context) {
            $document = FinanceDocument::query()->findOrFail($document->id);
            $instance = FinanceApprovalInstance::query()
                ->with('steps')
                ->where('document_id', $document->id)
                ->latest('id')
                ->first();

            if (! $instance) {
                throw new RuntimeException('Finance document approval has not been started.');
            }

            if (! in_array($instance->status, ['pending', 'in_progress'], true)) {
                throw new RuntimeException('Only active approval workflows can be escalated.');
            }

            $step = $instance->steps->firstWhere('status', 'pending');
            if (! $step) {
                throw new RuntimeException('Finance document does not have a pending approval step to escalate.');
            }

            $stepMeta = (array) $step->meta;
            $escalationCount = (int) ($stepMeta['escalation_count'] ?? 0) + 1;
            $step->meta = array_merge($stepMeta, [
                'escalation_count' => $escalationCount,
                'last_escalated_at' => now()->toDateTimeString(),
                'last_escalated_by' => $context->userId(),
                'last_escalation_reason' => $context->reason(),
                'escalation_status' => 'manual_escalation_requested',
                'escalation_dispatch_status' => 'queued',
                'last_dispatch_attempt_at' => null,
                'last_dispatch_recipient_count' => 0,
                'last_dispatch_reason' => $context->reason(),
            ]);
            $step->save();

            $instance->meta = array_merge((array) $instance->meta, [
                'last_escalated_step_no' => $step->step_no,
                'last_escalated_at' => now()->toDateTimeString(),
                'last_escalated_by' => $context->userId(),
                'last_escalation_reason' => $context->reason(),
            ]);
            $instance->save();

            $this->recordAudit($document, 'workflow.escalated', $context, [
                'approval_instance_id' => $instance->id,
                'step_no' => $step->step_no,
                'previous_escalation_count' => $escalationCount - 1,
            ], [
                'approval_instance_id' => $instance->id,
                'step_no' => $step->step_no,
                'escalation_count' => $escalationCount,
                'escalation_role' => data_get($step->meta, 'escalation_role'),
                'reason' => $context->reason(),
            ]);

            $this->expenseApprovalEscalationDispatchService->queueEscalation(
                $document,
                $instance,
                $step,
                $context
            );

            return $instance->fresh('steps');
        });
    }

    public function currentState(FinanceDocument $document): ApprovalStateView
    {
        $instance = FinanceApprovalInstance::query()
            ->with('steps')
            ->where('document_id', $document->id)
            ->latest('id')
            ->first();

        if (! $instance) {
            return new ApprovalStateView('not_started', null, []);
        }

        return new ApprovalStateView(
            $instance->status,
            $instance->current_step_no,
            $instance->steps->map(static fn (FinanceApprovalStep $step): array => [
                'step_no' => $step->step_no,
                'status' => $step->status,
                'decision' => $step->decision,
                'approver_user_id' => $step->approver_user_id,
                'approver_role' => $step->approver_role,
                'permission_code' => $step->permission_code,
                'acted_at' => optional($step->acted_at)->toDateTimeString(),
            ])->values()->all()
        );
    }

    protected function recordAudit(
        FinanceDocument $document,
        string $eventType,
        ActionContext $context,
        mixed $beforeState,
        mixed $afterState
    ): void {
        FinanceAuditEvent::query()->create([
            'business_id' => $document->business_id,
            'document_id' => $document->id,
            'event_type' => $eventType,
            'actor_id' => $context->userId(),
            'reason' => $context->reason(),
            'request_id' => $context->requestId(),
            'ip_address' => $context->ipAddress(),
            'user_agent' => $context->userAgent(),
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'meta' => $context->meta(),
            'acted_at' => now(),
        ]);
    }

    protected function resolvePolicy(FinanceDocument $document): array
    {
        $expensePolicy = $this->expenseApprovalPolicyResolver->resolve($document);
        if ($expensePolicy !== null && $expensePolicy['steps'] !== []) {
            return $expensePolicy;
        }

        return [
            'policy_code' => (string) config('vasaccounting.approval_defaults.finance_document_defaults.default_policy_code'),
            'maker_checker' => (bool) config('vasaccounting.approval_defaults.finance_document_defaults.maker_checker', true),
            'threshold_max_amount' => null,
            'threshold_min_amount' => null,
            'steps' => [[
                'step_no' => 1,
                'approver_role' => config('vasaccounting.approval_defaults.finance_document_defaults.default_step_role'),
                'permission_code' => config('vasaccounting.approval_defaults.finance_document_defaults.default_permission_code'),
                'label' => null,
            ]],
        ];
    }
}
