<?php

namespace Modules\VasAccounting\Services\FinanceCore;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use RuntimeException;

class DocumentWorkflowService
{
    public function validateDocumentDefinition(array $attributes): void
    {
        $family = (string) ($attributes['document_family'] ?? '');
        $type = (string) ($attributes['document_type'] ?? '');
        $blueprint = $this->blueprintFor($family, $type);

        if (($blueprint['required_counterparty'] ?? false) && empty($attributes['counterparty_id'])) {
            throw new RuntimeException(sprintf('Finance document type [%s] requires a counterparty.', $type));
        }
    }

    public function validateLinks(array $attributes, array $links): void
    {
        if (empty($links)) {
            return;
        }

        $businessId = (int) ($attributes['business_id'] ?? 0);
        $blueprint = $this->blueprintFor(
            (string) ($attributes['document_family'] ?? ''),
            (string) ($attributes['document_type'] ?? '')
        );

        foreach ($links as $link) {
            $parentId = (int) ($link['parent_document_id'] ?? 0);
            if ($parentId > 0) {
                $parent = FinanceDocument::query()->findOrFail($parentId);
                if ($businessId > 0 && (int) $parent->business_id !== $businessId) {
                    throw new RuntimeException('Linked parent document belongs to a different business.');
                }

                if (! in_array($parent->document_type, $blueprint['allowed_parent_types'] ?? [], true)) {
                    throw new RuntimeException(sprintf(
                        'Finance document type [%s] cannot link to parent type [%s].',
                        (string) ($attributes['document_type'] ?? ''),
                        $parent->document_type
                    ));
                }
            }

            $childId = (int) ($link['child_document_id'] ?? 0);
            if ($childId > 0) {
                $child = FinanceDocument::query()->findOrFail($childId);
                if ($businessId > 0 && (int) $child->business_id !== $businessId) {
                    throw new RuntimeException('Linked child document belongs to a different business.');
                }

                if (! in_array($child->document_type, $blueprint['allowed_child_types'] ?? [], true)) {
                    throw new RuntimeException(sprintf(
                        'Finance document type [%s] cannot link to child type [%s].',
                        (string) ($attributes['document_type'] ?? ''),
                        $child->document_type
                    ));
                }
            }
        }
    }

    public function approvalAccountingStatus(FinanceDocument $document): string
    {
        return $this->isPostingDocument($document) ? 'ready_to_post' : 'not_ready';
    }

    public function assertPostable(FinanceDocument $document): void
    {
        if (! $this->isPostingDocument($document)) {
            throw new RuntimeException(sprintf('Finance document type [%s] is not posting-enabled.', $document->document_type));
        }

        $allowedStatuses = $this->blueprintFor($document->document_family, $document->document_type)['postable_workflow_statuses'] ?? ['approved'];
        if (! in_array($document->workflow_status, $allowedStatuses, true)) {
            throw new RuntimeException(sprintf(
                'Finance document type [%s] must be in [%s] before posting.',
                $document->document_type,
                implode(', ', $allowedStatuses)
            ));
        }
    }

    public function transition(FinanceDocument $document, string $eventName, array $meta = []): array
    {
        $transition = data_get(config('vasaccounting.finance_document_transition_events', []), $document->document_type . '.' . $eventName);
        if (! is_array($transition)) {
            throw new RuntimeException(sprintf(
                'Finance document type [%s] does not support workflow event [%s].',
                $document->document_type,
                $eventName
            ));
        }

        if (! in_array($document->workflow_status, $transition['allowed_from'] ?? [], true)) {
            throw new RuntimeException(sprintf(
                'Workflow event [%s] is not allowed from status [%s] for document type [%s].',
                $eventName,
                $document->workflow_status,
                $document->document_type
            ));
        }

        $target = $transition;
        if (isset($transition['targets'])) {
            $completionState = (string) data_get($meta, 'completion_state', '');
            $target = $transition['targets'][$completionState] ?? null;
            if (! is_array($target)) {
                throw new RuntimeException(sprintf(
                    'Workflow event [%s] requires a valid completion_state for document type [%s].',
                    $eventName,
                    $document->document_type
                ));
            }
        }

        return [
            'workflow_status' => (string) $target['workflow_status'],
            'accounting_status' => (string) ($target['accounting_status'] ?? $document->accounting_status),
        ];
    }

    public function blueprintFor(string $family, string $type): array
    {
        $blueprint = data_get(config('vasaccounting.finance_document_blueprints', []), $family . '.' . $type);
        if (! is_array($blueprint)) {
            throw new RuntimeException(sprintf('Finance document family/type [%s/%s] is not defined.', $family, $type));
        }

        return $blueprint;
    }

    public function isPostingDocument(FinanceDocument $document): bool
    {
        return ($this->blueprintFor($document->document_family, $document->document_type)['posting_mode'] ?? 'none') !== 'none';
    }
}
