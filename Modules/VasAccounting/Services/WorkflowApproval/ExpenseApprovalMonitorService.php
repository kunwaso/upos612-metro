<?php

namespace Modules\VasAccounting\Services\WorkflowApproval;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalInstance;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalStep;

class ExpenseApprovalMonitorService
{
    public function __construct(
        protected ExpenseApprovalPolicyResolver $expenseApprovalPolicyResolver
    ) {
    }

    /**
     * @param  iterable<int, FinanceDocument>  $documents
     * @return array<int, array<string, mixed>>
     */
    public function buildInsights(iterable $documents): array
    {
        $insights = [];

        foreach ($documents as $document) {
            if (! $document instanceof FinanceDocument) {
                continue;
            }

            $insights[$document->id] = $this->buildInsight($document);
        }

        return $insights;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildInsight(FinanceDocument $document): array
    {
        $policy = $this->expenseApprovalPolicyResolver->resolve($document) ?? [
            'steps' => [],
            'threshold_min_amount' => null,
            'threshold_max_amount' => null,
            'policy_code' => null,
        ];

        $instance = $this->resolveApprovalInstance($document);
        $steps = $instance?->steps instanceof Collection
            ? $instance->steps
            : collect();
        $currentStep = $steps->firstWhere('status', 'pending')
            ?: $steps->firstWhere('status', 'rejected')
            ?: $steps->sortByDesc('step_no')->first();

        $matchedPolicyStep = $currentStep instanceof FinanceApprovalStep
            ? collect((array) $policy['steps'])->firstWhere('step_no', (int) $currentStep->step_no)
            : null;

        $thresholdMinAmount = $this->toFloat(
            data_get($instance?->meta, 'threshold_min_amount', $policy['threshold_min_amount'] ?? null)
        );
        $thresholdMaxAmount = $this->toFloat(
            data_get($instance?->meta, 'threshold_max_amount', $policy['threshold_max_amount'] ?? null)
        );

        $slaHours = $this->toInt(
            data_get($currentStep?->meta, 'sla_hours', data_get($matchedPolicyStep, 'sla_hours'))
        );
        $warningHours = $this->toInt(
            data_get($currentStep?->meta, 'warning_hours', data_get($matchedPolicyStep, 'warning_hours'))
        );
        $pendingStartedAt = $this->pendingStartedAt($instance, $currentStep);
        $slaInsight = $this->resolveSlaInsight(
            $currentStep instanceof FinanceApprovalStep ? (string) $currentStep->status : null,
            $pendingStartedAt,
            $slaHours,
            $warningHours
        );

        $currentRole = $currentStep instanceof FinanceApprovalStep
            ? (string) ($currentStep->approver_role ?? '')
            : null;
        $escalationRole = (string) data_get($currentStep?->meta, 'escalation_role', data_get($matchedPolicyStep, 'escalation_role', ''));
        $currentRoleLabel = $this->roleLabel($currentRole);
        $escalationRoleLabel = $this->roleLabel($escalationRole);
        $currentStepLabel = (string) data_get($currentStep?->meta, 'label', data_get($matchedPolicyStep, 'label', ''));
        $lastEscalatedAt = data_get($currentStep?->meta, 'last_escalated_at');
        $lastEscalationReason = data_get($currentStep?->meta, 'last_escalation_reason');
        $escalationCount = (int) data_get($currentStep?->meta, 'escalation_count', 0);
        $dispatchStatus = data_get($currentStep?->meta, 'escalation_dispatch_status');
        $dispatchRecipientCount = data_get($currentStep?->meta, 'last_dispatch_recipient_count');
        $dispatchAttemptAt = data_get($currentStep?->meta, 'last_dispatch_attempt_at');
        $dispatchError = data_get($currentStep?->meta, 'last_dispatch_error');

        return [
            'policy_code' => data_get($instance?->meta, 'policy_code', $instance?->policy_code ?: ($policy['policy_code'] ?? null)),
            'threshold_min_amount' => $thresholdMinAmount,
            'threshold_max_amount' => $thresholdMaxAmount,
            'threshold_label' => $this->thresholdLabel($thresholdMinAmount, $thresholdMaxAmount),
            'is_high_value' => ($thresholdMinAmount ?? 0.0) > 0.0 || count((array) ($policy['steps'] ?? [])) > 1,
            'current_step_no' => $currentStep instanceof FinanceApprovalStep ? (int) $currentStep->step_no : null,
            'current_step_label' => $currentStepLabel !== '' ? $currentStepLabel : null,
            'current_step_role' => $currentRole,
            'current_step_role_label' => $currentRoleLabel,
            'step_count' => $steps->count() > 0 ? $steps->count() : count((array) ($policy['steps'] ?? [])),
            'sla_hours' => $slaHours,
            'warning_hours' => $warningHours,
            'pending_started_at' => $pendingStartedAt?->toDateTimeString(),
            'sla_state' => $slaInsight['state'],
            'sla_label' => $slaInsight['label'],
            'sla_badge_class' => $slaInsight['badge_class'],
            'elapsed_hours' => $slaInsight['elapsed_hours'],
            'hours_to_due' => $slaInsight['hours_to_due'],
            'overdue_hours' => $slaInsight['overdue_hours'],
            'escalation_role' => $escalationRole !== '' ? $escalationRole : null,
            'escalation_role_label' => $escalationRoleLabel,
            'escalation_message' => $slaInsight['state'] === 'overdue' && $escalationRoleLabel
                ? 'Escalate to ' . $escalationRoleLabel
                : null,
            'escalation_count' => $escalationCount,
            'last_escalated_at' => $lastEscalatedAt,
            'last_escalation_reason' => $lastEscalationReason,
            'dispatch_status' => $dispatchStatus,
            'dispatch_status_label' => $this->dispatchStatusLabel($dispatchStatus, $dispatchRecipientCount),
            'dispatch_recipient_count' => $dispatchRecipientCount === null ? null : (int) $dispatchRecipientCount,
            'dispatch_attempted_at' => $dispatchAttemptAt,
            'dispatch_error' => $dispatchError ?: null,
        ];
    }

    protected function resolveApprovalInstance(FinanceDocument $document): ?FinanceApprovalInstance
    {
        if ($document->relationLoaded('approvalInstances')) {
            /** @var Collection<int, FinanceApprovalInstance> $instances */
            $instances = $document->approvalInstances;

            return $instances->sortByDesc('id')->first();
        }

        return $document->approvalInstances()->with('steps')->latest('id')->first();
    }

    protected function pendingStartedAt(?FinanceApprovalInstance $instance, ?FinanceApprovalStep $step): ?CarbonInterface
    {
        if (! $step) {
            return null;
        }

        $pendingStartedAt = data_get($step->meta, 'pending_started_at');
        if ($pendingStartedAt) {
            return Carbon::parse($pendingStartedAt);
        }

        if ($step->status === 'pending' && $instance?->started_at) {
            return Carbon::parse($instance->started_at);
        }

        return null;
    }

    /**
     * @return array{state:string,label:string,badge_class:string,elapsed_hours:?float,hours_to_due:?float,overdue_hours:?float}
     */
    protected function resolveSlaInsight(?string $stepStatus, ?CarbonInterface $pendingStartedAt, ?int $slaHours, ?int $warningHours): array
    {
        if ($stepStatus !== 'pending' || ! $pendingStartedAt || ! $slaHours || $slaHours <= 0) {
            return [
                'state' => 'not_applicable',
                'label' => 'No SLA',
                'badge_class' => 'badge-light-secondary',
                'elapsed_hours' => null,
                'hours_to_due' => null,
                'overdue_hours' => null,
            ];
        }

        $elapsedHours = round($pendingStartedAt->diffInSeconds(now()) / 3600, 2);
        $hoursToDue = round($slaHours - $elapsedHours, 2);
        $warningThreshold = max(0, $slaHours - ($warningHours ?? min(4, $slaHours)));

        if ($elapsedHours > $slaHours) {
            return [
                'state' => 'overdue',
                'label' => 'Overdue by ' . number_format(abs($hoursToDue), 2) . 'h',
                'badge_class' => 'badge-light-danger',
                'elapsed_hours' => $elapsedHours,
                'hours_to_due' => $hoursToDue,
                'overdue_hours' => abs($hoursToDue),
            ];
        }

        if ($elapsedHours >= $warningThreshold) {
            return [
                'state' => 'due_soon',
                'label' => 'Due in ' . number_format(max($hoursToDue, 0), 2) . 'h',
                'badge_class' => 'badge-light-warning',
                'elapsed_hours' => $elapsedHours,
                'hours_to_due' => $hoursToDue,
                'overdue_hours' => null,
            ];
        }

        return [
            'state' => 'on_track',
            'label' => 'On track',
            'badge_class' => 'badge-light-success',
            'elapsed_hours' => $elapsedHours,
            'hours_to_due' => $hoursToDue,
            'overdue_hours' => null,
        ];
    }

    protected function thresholdLabel(?float $thresholdMinAmount, ?float $thresholdMaxAmount): string
    {
        $currency = (string) config('vasaccounting.book_currency', 'VND');

        if ($thresholdMinAmount === null && $thresholdMaxAmount === null) {
            return 'No amount threshold';
        }

        if ($thresholdMinAmount === null) {
            return 'Up to ' . $this->formatAmount((float) $thresholdMaxAmount) . ' ' . $currency;
        }

        if ($thresholdMaxAmount === null) {
            return 'Above ' . $this->formatAmount((float) $thresholdMinAmount) . ' ' . $currency;
        }

        return $this->formatAmount((float) $thresholdMinAmount) . ' - ' . $this->formatAmount((float) $thresholdMaxAmount) . ' ' . $currency;
    }

    protected function roleLabel(?string $role): ?string
    {
        $role = trim((string) $role);
        if ($role === '') {
            return null;
        }

        return data_get(config('vasaccounting.cutover_uat_personas', []), $role . '.label')
            ?: ucwords(str_replace('_', ' ', $role));
    }

    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2);
    }

    protected function toFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : round((float) $value, 4);
    }

    protected function toInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    protected function dispatchStatusLabel(?string $dispatchStatus, mixed $recipientCount): ?string
    {
        return match ((string) $dispatchStatus) {
            'queued' => 'Dispatch queued',
            'sent' => 'Sent to ' . (int) $recipientCount . ' recipient(s)',
            'no_recipients' => 'No recipients resolved',
            'failed' => 'Dispatch failed',
            default => null,
        };
    }
}
