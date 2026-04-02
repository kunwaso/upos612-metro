<?php

namespace Modules\VasAccounting\Services\Procurement;

use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\ProcurementDiscrepancyServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchRun;
use RuntimeException;

class ProcurementDiscrepancyService implements ProcurementDiscrepancyServiceInterface
{
    public function takeOwnership(FinanceMatchException $exception, ActionContext $context): FinanceMatchException
    {
        return DB::transaction(function () use ($exception, $context) {
            $exception = $this->loadExceptionForUpdate($exception, $context->businessId());
            if (! in_array($exception->status, FinanceMatchException::unresolvedStatuses(), true)) {
                throw new RuntimeException('Only unresolved procurement discrepancies can be assigned.');
            }

            $before = $this->auditSnapshot($exception);
            $exception->owner_id = $context->userId();
            $exception->owner_assigned_at = now();
            $exception->reviewed_by = $context->userId();
            $exception->reviewed_at = now();
            $exception->status = FinanceMatchException::STATUS_IN_REVIEW;
            $exception->save();

            $this->syncMatchingState($exception);
            $this->recordAudit($exception, 'procurement.discrepancy_owned', $context, $before);

            return $exception->fresh(['documentLine', 'owner', 'reviewer', 'resolver']);
        });
    }

    public function resolve(FinanceMatchException $exception, string $resolutionNote, ActionContext $context): FinanceMatchException
    {
        return DB::transaction(function () use ($exception, $resolutionNote, $context) {
            $exception = $this->loadExceptionForUpdate($exception, $context->businessId());
            if (! in_array($exception->status, FinanceMatchException::unresolvedStatuses(), true)) {
                throw new RuntimeException('Only unresolved procurement discrepancies can be resolved.');
            }

            $before = $this->auditSnapshot($exception);
            $exception->owner_id = $exception->owner_id ?: $context->userId();
            $exception->owner_assigned_at = $exception->owner_assigned_at ?: now();
            $exception->reviewed_by = $context->userId();
            $exception->reviewed_at = now();
            $exception->resolved_by = $context->userId();
            $exception->resolved_at = now();
            $exception->resolution_note = $resolutionNote;
            $exception->status = FinanceMatchException::STATUS_RESOLVED;
            $exception->save();

            $this->syncMatchingState($exception);
            $this->recordAudit($exception, 'procurement.discrepancy_resolved', $context, $before);

            return $exception->fresh(['documentLine', 'owner', 'reviewer', 'resolver']);
        });
    }

    public function assignOwner(FinanceMatchException $exception, int $ownerId, ActionContext $context): FinanceMatchException
    {
        return DB::transaction(function () use ($exception, $ownerId, $context) {
            $exception = $this->loadExceptionForUpdate($exception, $context->businessId());
            if (! in_array($exception->status, FinanceMatchException::unresolvedStatuses(), true)) {
                throw new RuntimeException('Only unresolved procurement discrepancies can be reassigned.');
            }

            $before = $this->auditSnapshot($exception);
            $exception->owner_id = $ownerId;
            $exception->owner_assigned_at = now();
            $exception->reviewed_by = $context->userId();
            $exception->reviewed_at = now();
            $exception->status = FinanceMatchException::STATUS_IN_REVIEW;
            $exception->save();

            $this->syncMatchingState($exception);
            $this->recordAudit($exception, 'procurement.discrepancy_reassigned', $context, $before);

            return $exception->fresh(['documentLine', 'owner', 'reviewer', 'resolver']);
        });
    }

    protected function loadExceptionForUpdate(FinanceMatchException $exception, int $businessId): FinanceMatchException
    {
        return FinanceMatchException::query()
            ->with(['document', 'matchRun', 'documentLine', 'owner', 'reviewer', 'resolver'])
            ->lockForUpdate()
            ->where('business_id', $businessId)
            ->findOrFail($exception->id);
    }

    protected function syncMatchingState(FinanceMatchException $exception): void
    {
        $matchRun = FinanceMatchRun::query()
            ->with(['document', 'exceptions'])
            ->findOrFail($exception->match_run_id);

        $unresolved = $matchRun->exceptions->whereIn('status', FinanceMatchException::unresolvedStatuses());
        $blockingCount = $unresolved->where('severity', 'blocking')->count();
        $warningCount = $unresolved->where('severity', 'warning')->count();
        $status = $blockingCount > 0
            ? 'blocked'
            : ($warningCount > 0 ? 'matched_with_warning' : 'matched');

        $matchRun->status = $status;
        $matchRun->blocking_exception_count = $blockingCount;
        $matchRun->warning_count = $warningCount;
        $matchRun->save();

        $document = $matchRun->document ?: FinanceDocument::query()->findOrFail($matchRun->document_id);
        $matchingMeta = array_merge((array) data_get($document->meta, 'matching', []), [
            'latest_run_id' => $matchRun->id,
            'latest_status' => $status,
            'blocking_exception_count' => $blockingCount,
            'warning_count' => $warningCount,
            'matched_line_count' => $matchRun->matched_line_count,
            'total_line_count' => $matchRun->total_line_count,
            'matched_at' => optional($matchRun->matched_at)->toDateTimeString(),
            'reviewed_at' => now()->toDateTimeString(),
        ]);
        $document->meta = array_merge((array) $document->meta, ['matching' => $matchingMeta]);
        $document->save();
    }

    protected function recordAudit(
        FinanceMatchException $exception,
        string $eventType,
        ActionContext $context,
        array $beforeState
    ): void {
        FinanceAuditEvent::query()->create([
            'business_id' => $exception->business_id,
            'document_id' => $exception->document_id,
            'event_type' => $eventType,
            'actor_id' => $context->userId(),
            'reason' => $context->reason(),
            'request_id' => $context->requestId(),
            'ip_address' => $context->ipAddress(),
            'user_agent' => $context->userAgent(),
            'before_state' => $beforeState,
            'after_state' => $this->auditSnapshot($exception),
            'meta' => [
                'match_exception_id' => $exception->id,
                'match_run_id' => $exception->match_run_id,
                'document_line_id' => $exception->document_line_id,
            ],
            'acted_at' => now(),
        ]);
    }

    protected function auditSnapshot(FinanceMatchException $exception): array
    {
        return [
            'status' => $exception->status,
            'owner_id' => $exception->owner_id,
            'owner_assigned_at' => optional($exception->owner_assigned_at)->toDateTimeString(),
            'reviewed_by' => $exception->reviewed_by,
            'reviewed_at' => optional($exception->reviewed_at)->toDateTimeString(),
            'resolved_by' => $exception->resolved_by,
            'resolved_at' => optional($exception->resolved_at)->toDateTimeString(),
            'resolution_note' => $exception->resolution_note,
        ];
    }
}
