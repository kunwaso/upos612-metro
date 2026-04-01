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
        protected ExpenseApprovalPolicyResolver $expenseApprovalPolicyResolver
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
                $nextStep->status = 'pending';
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
