<?php

namespace Modules\VasAccounting\Services\FinanceCore;

use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Application\DTOs\DocumentCreateData;
use Modules\VasAccounting\Application\DTOs\DocumentUpdateData;
use Modules\VasAccounting\Contracts\ApprovalWorkflowServiceInterface;
use Modules\VasAccounting\Contracts\DocumentMatchingServiceInterface;
use Modules\VasAccounting\Contracts\ExpenseSettlementServiceInterface;
use Modules\VasAccounting\Contracts\FinanceDocumentServiceInterface;
use Modules\VasAccounting\Contracts\OrderToCashLifecycleServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLink;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentStatusHistory;
use RuntimeException;

class FinanceDocumentCommandService implements FinanceDocumentServiceInterface
{
    public function __construct(
        protected ApprovalWorkflowServiceInterface $approvalWorkflowService,
        protected DocumentWorkflowService $documentWorkflowService,
        protected DocumentMatchingServiceInterface $documentMatchingService,
        protected OrderToCashLifecycleServiceInterface $orderToCashLifecycleService,
        protected ExpenseSettlementServiceInterface $expenseSettlementService
    ) {
    }

    public function create(DocumentCreateData $data): FinanceDocument
    {
        return DB::transaction(function () use ($data) {
            $this->documentWorkflowService->validateDocumentDefinition($data->attributes);
            $this->documentWorkflowService->validateLinks($data->attributes, $data->links);
            $this->expenseSettlementService->validateCreatePayload($data->attributes, $data->links);
            $document = FinanceDocument::query()->create($data->attributes);

            $this->syncLines($document, $data->lines);
            $this->syncLinks($document, $data->links);
            $this->recordHistory($document, 'created', null, $document->workflow_status, null, $document->accounting_status);
            $this->recordAudit($document, 'document.created', null, null, ['attributes' => $data->attributes]);
            $this->expenseSettlementService->syncDocumentChain($document);

            return $document->fresh(['lines', 'statusHistory']);
        });
    }

    public function update(int $documentId, DocumentUpdateData $data): FinanceDocument
    {
        return DB::transaction(function () use ($documentId, $data) {
            $document = FinanceDocument::query()->findOrFail($documentId);
            $this->ensureEditable($document);

            $before = $document->toArray();
            $updatedAttributes = array_merge($document->toArray(), $data->attributes);
            $this->documentWorkflowService->validateDocumentDefinition($updatedAttributes);
            $document->fill($data->attributes);
            $document->save();

            if (is_array($data->lines)) {
                $document->lines()->delete();
                $this->syncLines($document, $data->lines);
            }

            $this->recordAudit($document, 'document.updated', null, $before, $document->fresh()->toArray());
            $this->expenseSettlementService->syncDocumentChain($document);

            return $document->fresh(['lines', 'statusHistory']);
        });
    }

    public function submit(int $documentId, ActionContext $context): FinanceDocument
    {
        return DB::transaction(function () use ($documentId, $context) {
            $document = FinanceDocument::query()->findOrFail($documentId);
            $before = $document->toArray();

            if (! in_array($document->workflow_status, ['draft', 'submitted', 'rejected'], true)) {
                throw new RuntimeException('Only draft or rejected finance documents can be submitted for approval.');
            }

            $oldWorkflowStatus = $document->workflow_status;
            $oldAccountingStatus = $document->accounting_status;
            $document->workflow_status = 'submitted';
            $document->accounting_status = 'pending_approval';
            $document->submitted_at = now();
            $document->submitted_by = $context->userId();
            $document->save();

            $this->approvalWorkflowService->start($document, $context);
            $this->recordHistory($document, 'submitted', $oldWorkflowStatus, 'submitted', $oldAccountingStatus, 'pending_approval', $context);
            $this->recordAudit($document, 'document.submitted', $context, $before, $document->fresh()->toArray());

            return $document->fresh(['lines', 'statusHistory', 'approvalInstances.steps']);
        });
    }

    public function approve(int $documentId, ActionContext $context): FinanceDocument
    {
        return DB::transaction(function () use ($documentId, $context) {
            $document = FinanceDocument::query()->findOrFail($documentId);
            $before = $document->toArray();

            if (! in_array($document->workflow_status, ['submitted', 'approved'], true)) {
                throw new RuntimeException('Only submitted finance documents can be approved.');
            }

            $approvalInstance = $this->approvalWorkflowService->approve($document, $context);

            $oldWorkflowStatus = $document->workflow_status;
            $oldAccountingStatus = $document->accounting_status;
            if ($approvalInstance->status === 'approved') {
                $document->workflow_status = 'approved';
                $document->accounting_status = $this->documentWorkflowService->approvalAccountingStatus($document);
                $document->approved_at = now();
                $document->approved_by = $context->userId();
                $document->save();

                $this->recordHistory($document, 'approved', $oldWorkflowStatus, 'approved', $oldAccountingStatus, $document->accounting_status, $context);
                $this->recordAudit($document, 'document.approved', $context, $before, $document->fresh()->toArray());
            } else {
                $this->recordHistory(
                    $document,
                    'approval_progressed',
                    $oldWorkflowStatus,
                    $document->workflow_status,
                    $oldAccountingStatus,
                    $document->accounting_status,
                    $context
                );
                $this->recordAudit($document, 'document.approval_progressed', $context, $before, $document->fresh()->toArray());
            }

            return $document->fresh(['lines', 'statusHistory', 'approvalInstances.steps']);
        });
    }

    public function reject(int $documentId, ActionContext $context): FinanceDocument
    {
        return DB::transaction(function () use ($documentId, $context) {
            $document = FinanceDocument::query()->findOrFail($documentId);
            $before = $document->toArray();

            if ($document->workflow_status !== 'submitted') {
                throw new RuntimeException('Only submitted finance documents can be rejected.');
            }

            $this->approvalWorkflowService->reject($document, $context);

            $oldWorkflowStatus = $document->workflow_status;
            $oldAccountingStatus = $document->accounting_status;
            $document->workflow_status = 'rejected';
            $document->accounting_status = 'rejected';
            $document->save();

            $this->recordHistory($document, 'rejected', $oldWorkflowStatus, 'rejected', $oldAccountingStatus, 'rejected', $context);
            $this->recordAudit($document, 'document.rejected', $context, $before, $document->fresh()->toArray());
            $this->expenseSettlementService->syncDocumentChain($document, $context);

            return $document->fresh(['lines', 'statusHistory', 'approvalInstances.steps']);
        });
    }

    public function match(int $documentId, ActionContext $context): FinanceDocument
    {
        $document = FinanceDocument::query()->findOrFail($documentId);
        if ($document->document_type === 'supplier_invoice') {
            $matchRun = $this->documentMatchingService->matchSupplierInvoice($document, $context);
            if ($matchRun->status === 'blocked') {
                $message = optional($matchRun->exceptions->where('severity', 'blocking')->first())->message
                    ?? 'Supplier invoice matching failed due to blocking exceptions.';

                throw new RuntimeException($message);
            }
        }

        return $this->workflowTransition($documentId, $context, 'matched', 'match');
    }

    public function fulfill(int $documentId, ActionContext $context): FinanceDocument
    {
        return $this->workflowTransition($documentId, $context, 'fulfilled', 'fulfill');
    }

    public function close(int $documentId, ActionContext $context): FinanceDocument
    {
        return $this->workflowTransition($documentId, $context, 'closed', 'close');
    }

    public function cancel(int $documentId, ActionContext $context): FinanceDocument
    {
        return $this->transition($documentId, $context, 'cancelled', 'cancelled', 'cancelled');
    }

    public function reverse(int $documentId, ActionContext $context): FinanceDocument
    {
        return $this->transition($documentId, $context, 'reversed', 'reversed', 'reversed');
    }

    protected function transition(
        int $documentId,
        ActionContext $context,
        string $eventName,
        string $workflowStatus,
        string $accountingStatus
    ): FinanceDocument {
        return DB::transaction(function () use ($documentId, $context, $eventName, $workflowStatus, $accountingStatus) {
            $document = FinanceDocument::query()->findOrFail($documentId);
            $before = $document->toArray();

            if ($document->workflow_status === 'cancelled' && $eventName !== 'reversed') {
                throw new RuntimeException('Cancelled finance documents cannot transition further.');
            }

            $oldWorkflowStatus = $document->workflow_status;
            $oldAccountingStatus = $document->accounting_status;
            $document->workflow_status = $workflowStatus;
            $document->accounting_status = $accountingStatus;

            if ($eventName === 'submitted') {
                $document->submitted_at = now();
                $document->submitted_by = $context->userId();
            } elseif ($eventName === 'approved') {
                $document->approved_at = now();
                $document->approved_by = $context->userId();
            } elseif ($eventName === 'cancelled') {
                $document->cancelled_at = now();
                $document->cancelled_by = $context->userId();
            } elseif ($eventName === 'reversed') {
                $document->reversed_at = now();
                $document->reversed_by = $context->userId();
            }

            $document->save();

            $this->recordHistory(
                $document,
                $eventName,
                $oldWorkflowStatus,
                $workflowStatus,
                $oldAccountingStatus,
                $accountingStatus,
                $context
            );
            $this->recordAudit($document, 'document.' . $eventName, $context, $before, $document->fresh()->toArray());
            $this->expenseSettlementService->syncDocumentChain($document, $context);

            return $document->fresh(['lines', 'statusHistory']);
        });
    }

    protected function workflowTransition(
        int $documentId,
        ActionContext $context,
        string $historyEventName,
        string $workflowEventName
    ): FinanceDocument {
        return DB::transaction(function () use ($documentId, $context, $historyEventName, $workflowEventName) {
            $document = FinanceDocument::query()->findOrFail($documentId);
            $before = $document->toArray();
            $target = $this->documentWorkflowService->transition($document, $workflowEventName, $context->meta());

            $oldWorkflowStatus = $document->workflow_status;
            $oldAccountingStatus = $document->accounting_status;
            $document->workflow_status = $target['workflow_status'];
            $document->accounting_status = $target['accounting_status'];
            $document->save();

            $this->recordHistory(
                $document,
                $historyEventName,
                $oldWorkflowStatus,
                $target['workflow_status'],
                $oldAccountingStatus,
                $target['accounting_status'],
                $context
            );
            $this->recordAudit($document, 'document.' . $historyEventName, $context, $before, $document->fresh()->toArray());

            $document = $document->fresh(['lines', 'statusHistory', 'approvalInstances.steps']);
            $this->orderToCashLifecycleService->syncDocumentChain($document, $context);
            $this->expenseSettlementService->syncDocumentChain($document, $context);

            return $document->fresh(['lines', 'statusHistory', 'approvalInstances.steps']);
        });
    }

    protected function ensureEditable(FinanceDocument $document): void
    {
        if (in_array($document->workflow_status, ['posted', 'reversed', 'cancelled'], true)) {
            throw new RuntimeException('Posted, reversed, or cancelled finance documents are not editable.');
        }
    }

    protected function syncLines(FinanceDocument $document, array $lines): void
    {
        foreach (array_values($lines) as $index => $line) {
            FinanceDocumentLine::query()->create(array_merge($line, [
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'line_no' => (int) ($line['line_no'] ?? ($index + 1)),
            ]));
        }
    }

    protected function syncLinks(FinanceDocument $document, array $links): void
    {
        foreach ($links as $link) {
            FinanceDocumentLink::query()->create([
                'business_id' => $document->business_id,
                'parent_document_id' => (int) ($link['parent_document_id'] ?? $document->id),
                'child_document_id' => (int) ($link['child_document_id'] ?? $document->id),
                'link_type' => (string) ($link['link_type'] ?? 'related'),
                'meta' => $link['meta'] ?? null,
            ]);
        }
    }

    protected function recordHistory(
        FinanceDocument $document,
        string $eventName,
        ?string $fromWorkflowStatus,
        ?string $toWorkflowStatus,
        ?string $fromAccountingStatus,
        ?string $toAccountingStatus,
        ?ActionContext $context = null
    ): void {
        FinanceDocumentStatusHistory::query()->create([
            'business_id' => $document->business_id,
            'document_id' => $document->id,
            'event_name' => $eventName,
            'from_workflow_status' => $fromWorkflowStatus,
            'to_workflow_status' => $toWorkflowStatus,
            'from_accounting_status' => $fromAccountingStatus,
            'to_accounting_status' => $toAccountingStatus,
            'reason' => $context?->reason(),
            'acted_by' => $context?->userId(),
            'meta' => $context?->meta(),
            'acted_at' => now(),
        ]);
    }

    protected function recordAudit(
        FinanceDocument $document,
        string $eventType,
        ?ActionContext $context,
        $beforeState,
        $afterState
    ): void {
        FinanceAuditEvent::query()->create([
            'business_id' => $document->business_id,
            'document_id' => $document->id,
            'event_type' => $eventType,
            'actor_id' => $context?->userId(),
            'reason' => $context?->reason(),
            'request_id' => $context?->requestId(),
            'ip_address' => $context?->ipAddress(),
            'user_agent' => $context?->userAgent(),
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'meta' => $context?->meta(),
            'acted_at' => now(),
        ]);
    }
}
