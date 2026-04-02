<?php

namespace Modules\VasAccounting\Services\WorkflowApproval;

use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalInstance;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalStep;
use Modules\VasAccounting\Jobs\DispatchExpenseApprovalEscalationJob;
use Modules\VasAccounting\Notifications\ExpenseApprovalEscalatedNotification;
use RuntimeException;
use Throwable;

class ExpenseApprovalEscalationDispatchService
{
    public function __construct(
        protected ExpenseApprovalMonitorService $expenseApprovalMonitorService
    ) {
    }

    public function queueEscalation(
        FinanceDocument $document,
        FinanceApprovalInstance $instance,
        FinanceApprovalStep $step,
        ActionContext $context
    ): void {
        dispatch(new DispatchExpenseApprovalEscalationJob(
            (int) $document->id,
            (int) $instance->id,
            (int) $step->id,
            $context->userId(),
            $context->reason()
        ));
    }

    public function retryDispatch(FinanceDocument $document, ActionContext $context): void
    {
        $instance = $document->approvalInstances instanceof Collection
            ? $document->approvalInstances->sortByDesc('id')->first()
            : $document->approvalInstances()->with('steps')->latest('id')->first();

        if (! $instance) {
            throw new RuntimeException('Finance document approval has not been started.');
        }

        $steps = $instance->steps instanceof Collection
            ? $instance->steps
            : $instance->steps()->get();
        $step = $steps->firstWhere('status', 'pending');

        if (! $step) {
            throw new RuntimeException('Finance document does not have a pending approval step to re-dispatch.');
        }

        if (data_get($step->meta, 'escalation_dispatch_status') !== 'failed') {
            throw new RuntimeException('Only failed escalation dispatches can be retried.');
        }

        $stepMeta = (array) $step->meta;
        $step->meta = array_merge($stepMeta, [
            'escalation_dispatch_status' => 'queued',
            'last_dispatch_attempt_at' => null,
            'last_dispatch_recipient_count' => 0,
            'last_dispatch_reason' => $context->reason(),
            'last_dispatch_error' => null,
            'last_dispatch_exception_class' => null,
        ]);
        $step->save();

        $this->recordAudit($document, $context->userId(), 'workflow.escalation_dispatch_requeued', [
            'approval_instance_id' => $instance->id,
            'step_no' => $step->step_no,
            'dispatch_status' => 'queued',
            'reason' => $context->reason(),
        ]);

        $this->queueEscalation($document, $instance, $step, $context);
    }

    public function retryFailedDispatchesForBusiness(int $businessId, ActionContext $context): int
    {
        $documents = FinanceDocument::query()
            ->with(['approvalInstances.steps'])
            ->where('business_id', $businessId)
            ->where('document_family', 'expense_management')
            ->where('workflow_status', 'submitted')
            ->orderByDesc('id')
            ->get();

        $retried = 0;

        foreach ($documents as $document) {
            $instance = $document->approvalInstances instanceof Collection
                ? $document->approvalInstances->sortByDesc('id')->first()
                : null;
            $step = $instance?->steps instanceof Collection
                ? $instance->steps->firstWhere('status', 'pending')
                : null;

            if (! $step) {
                continue;
            }

            if (data_get($step->meta, 'escalation_dispatch_status') !== 'failed') {
                continue;
            }

            $this->retryDispatch($document, $context);
            $retried++;
        }

        return $retried;
    }

    public function handleDispatch(
        int $documentId,
        int $approvalInstanceId,
        int $approvalStepId,
        int $actorId,
        ?string $reason = null
    ): void {
        $document = FinanceDocument::query()
            ->with('approvalInstances.steps')
            ->findOrFail($documentId);

        $instance = FinanceApprovalInstance::query()
            ->where('document_id', $document->id)
            ->findOrFail($approvalInstanceId);

        $step = FinanceApprovalStep::query()
            ->where('approval_instance_id', $instance->id)
            ->findOrFail($approvalStepId);

        if ($step->status !== 'pending') {
            throw new RuntimeException('Only pending expense approval steps can receive escalation dispatch.');
        }

        $approvalInsight = $this->expenseApprovalMonitorService->buildInsight($document);
        $recipients = $this->resolveRecipients($document, $step, $actorId);

        if ($recipients->isEmpty()) {
            $this->updateDispatchState($step, 'no_recipients', 0, $reason);
            $this->recordAudit($document, $actorId, 'workflow.escalation_dispatch_skipped', [
                'approval_instance_id' => $instance->id,
                'step_no' => $step->step_no,
                'dispatch_status' => 'no_recipients',
            ]);

            return;
        }

        Notification::send(
            $recipients,
            new ExpenseApprovalEscalatedNotification($document, $step, $approvalInsight, $reason)
        );

        $this->updateDispatchState($step, 'sent', $recipients->count(), $reason);

        $this->recordAudit($document, $actorId, 'workflow.escalation_dispatched', [
            'approval_instance_id' => $instance->id,
            'step_no' => $step->step_no,
            'dispatch_status' => 'sent',
            'recipient_count' => $recipients->count(),
            'recipient_ids' => $recipients->pluck('id')->values()->all(),
        ]);
    }

    public function handleDispatchFailure(
        int $documentId,
        int $approvalInstanceId,
        int $approvalStepId,
        int $actorId,
        ?string $reason,
        Throwable $exception
    ): void {
        $document = FinanceDocument::query()->find($documentId);
        if (! $document) {
            return;
        }

        $instance = FinanceApprovalInstance::query()
            ->where('document_id', $document->id)
            ->find($approvalInstanceId);

        $step = $instance
            ? FinanceApprovalStep::query()
                ->where('approval_instance_id', $instance->id)
                ->find($approvalStepId)
            : null;

        if ($step) {
            $this->updateDispatchState($step, 'failed', 0, $reason, [
                'last_dispatch_error' => $exception->getMessage(),
                'last_dispatch_exception_class' => $exception::class,
            ]);
        }

        $this->recordAudit($document, $actorId, 'workflow.escalation_dispatch_failed', [
            'approval_instance_id' => $instance?->id,
            'step_no' => $step?->step_no,
            'dispatch_status' => 'failed',
            'error' => $exception->getMessage(),
            'exception_class' => $exception::class,
        ]);
    }

    protected function resolveRecipients(FinanceDocument $document, FinanceApprovalStep $step, int $actorId): Collection
    {
        $businessId = (int) $document->business_id;
        $locationId = $document->business_location_id ? (int) $document->business_location_id : null;
        $permissionCode = (string) ($step->permission_code ?: 'vas_accounting.expenses.manage');

        return User::query()
            ->where('business_id', $businessId)
            ->user()
            ->get()
            ->filter(function (User $user) use ($businessId, $locationId, $permissionCode, $actorId) {
                if ((int) $user->id === $actorId) {
                    return false;
                }

                $isAdmin = $user->hasRole('Admin#' . $businessId);
                $hasPermission = $user->can($permissionCode);

                if (! $isAdmin && ! $hasPermission) {
                    return false;
                }

                if (! $locationId || $isAdmin) {
                    return true;
                }

                $permittedLocations = $user->permitted_locations($businessId);

                return $permittedLocations === 'all'
                    || in_array($locationId, (array) $permittedLocations, true);
            })
            ->values();
    }

    protected function updateDispatchState(
        FinanceApprovalStep $step,
        string $status,
        int $recipientCount,
        ?string $reason,
        array $extraMeta = []
    ): void
    {
        $stepMeta = (array) $step->meta;
        $step->meta = array_merge($stepMeta, [
            'escalation_dispatch_status' => $status,
            'last_dispatch_attempt_at' => now()->toDateTimeString(),
            'last_dispatch_recipient_count' => $recipientCount,
            'last_dispatch_reason' => $reason,
        ], $extraMeta);
        $step->save();
    }

    protected function recordAudit(FinanceDocument $document, int $actorId, string $eventType, array $afterState): void
    {
        FinanceAuditEvent::query()->create([
            'business_id' => $document->business_id,
            'document_id' => $document->id,
            'event_type' => $eventType,
            'actor_id' => $actorId,
            'after_state' => $afterState,
            'acted_at' => now(),
        ]);
    }
}
