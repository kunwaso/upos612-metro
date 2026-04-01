<?php

namespace Modules\VasAccounting\Services\Subledger;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\OpenItemServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceAccountingEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceOpenItem;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceOpenItemAllocation;
use RuntimeException;

class OpenItemService implements OpenItemServiceInterface
{
    public function resolveDocumentProfile(FinanceDocument $document): ?array
    {
        return match ($document->document_type) {
            'customer_invoice' => ['ledger_type' => 'receivable', 'document_role' => 'charge'],
            'customer_receipt' => ['ledger_type' => 'receivable', 'document_role' => 'settlement'],
            'supplier_invoice' => ['ledger_type' => 'payable', 'document_role' => 'charge'],
            'supplier_payment' => ['ledger_type' => 'payable', 'document_role' => 'settlement'],
            default => null,
        };
    }

    public function syncPostedDocument(FinanceDocument $document, FinanceAccountingEvent $event, ActionContext $context): void
    {
        $profile = $this->resolveDocumentProfile($document);
        if (! $profile) {
            return;
        }

        $document->refresh();
        $amount = $this->documentAmount($document);
        if (bccomp($amount, '0.0000', 4) === 0) {
            return;
        }

        $openItem = FinanceOpenItem::query()->updateOrCreate(
            [
                'document_id' => $document->id,
                'ledger_type' => $profile['ledger_type'],
                'document_role' => $profile['document_role'],
            ],
            [
                'business_id' => $document->business_id,
                'accounting_event_id' => $event->id,
                'counterparty_type' => $document->counterparty_type,
                'counterparty_id' => $document->counterparty_id,
                'currency_code' => $document->currency_code,
                'exchange_rate' => $document->exchange_rate ?: 1,
                'document_date' => $document->document_date,
                'posting_date' => $document->posting_date ?: $document->document_date,
                'due_date' => data_get($document->meta, 'due_date', $document->document_date),
                'reference_no' => $document->document_no,
                'original_amount' => $amount,
                'open_amount' => $amount,
                'settled_amount' => '0.0000',
                'status' => 'open',
                'meta' => array_merge((array) $document->meta, ['source' => 'posting_engine_v2']),
            ]
        );

        if ($profile['document_role'] === 'settlement') {
            $this->allocateSettlementTargets($document, $event, $openItem, $profile['ledger_type'], $context);
        }

        $this->syncDocumentOpenAmount($document->fresh());
    }

    public function reverseDocument(FinanceDocument $document, FinanceAccountingEvent $reversalEvent, ActionContext $context): void
    {
        $profile = $this->resolveDocumentProfile($document);
        if (! $profile) {
            return;
        }

        $documentItemIds = FinanceOpenItem::query()
            ->where('document_id', $document->id)
            ->pluck('id')
            ->all();

        if (empty($documentItemIds)) {
            return;
        }

        $allocations = FinanceOpenItemAllocation::query()
            ->where('status', 'active')
            ->where(function ($query) use ($documentItemIds) {
                $query->whereIn('source_open_item_id', $documentItemIds)
                    ->orWhereIn('target_open_item_id', $documentItemIds);
            })
            ->with(['sourceOpenItem.document', 'targetOpenItem.document'])
            ->get();

        foreach ($allocations as $allocation) {
            $this->reverseAllocation($allocation, $reversalEvent, $context);
        }

        $openItems = FinanceOpenItem::query()
            ->whereIn('id', $documentItemIds)
            ->get();

        foreach ($openItems as $openItem) {
            $openItem->status = 'reversed';
            $openItem->open_amount = '0.0000';
            $openItem->settled_amount = '0.0000';
            $openItem->reversal_event_id = $reversalEvent->id;
            $openItem->reversed_at = now();
            $openItem->reversed_by = $context->userId();
            $openItem->meta = array_merge((array) $openItem->meta, [
                'reversed_reason' => $context->reason(),
                'reversed_request_id' => $context->requestId(),
            ]);
            $openItem->save();

            $this->recordAudit(
                $openItem->document,
                'subledger.open_item_reversed',
                $context,
                ['open_item_id' => $openItem->id, 'status' => 'active'],
                ['open_item_id' => $openItem->id, 'status' => 'reversed']
            );
        }

        $affectedDocumentIds = collect($allocations)
            ->flatMap(function (FinanceOpenItemAllocation $allocation) {
                return [
                    optional($allocation->sourceOpenItem)->document_id,
                    optional($allocation->targetOpenItem)->document_id,
                ];
            })
            ->merge([$document->id])
            ->filter()
            ->unique()
            ->values();

        foreach ($affectedDocumentIds as $affectedDocumentId) {
            $affectedDocument = FinanceDocument::query()->find($affectedDocumentId);
            if ($affectedDocument) {
                $this->syncDocumentOpenAmount($affectedDocument);
            }
        }
    }

    protected function allocateSettlementTargets(
        FinanceDocument $document,
        FinanceAccountingEvent $event,
        FinanceOpenItem $settlementItem,
        string $ledgerType,
        ActionContext $context
    ): void {
        $targets = collect((array) data_get($document->meta, 'settlement_targets', []));
        if ($targets->isEmpty()) {
            return;
        }

        foreach ($targets as $target) {
            $targetDocumentId = (int) data_get($target, 'document_id');
            $targetAmount = $this->normalizeAmount((string) data_get($target, 'amount', '0'));
            if ($targetDocumentId <= 0 || bccomp($targetAmount, '0.0000', 4) !== 1) {
                continue;
            }

            $targetOpenItem = FinanceOpenItem::query()
                ->where('document_id', $targetDocumentId)
                ->where('ledger_type', $ledgerType)
                ->where('document_role', 'charge')
                ->first();

            if (! $targetOpenItem) {
                throw new RuntimeException(sprintf('Settlement target finance document [%d] does not have an open item.', $targetDocumentId));
            }

            if ((int) $targetOpenItem->business_id !== (int) $document->business_id) {
                throw new RuntimeException('Settlement target belongs to a different business.');
            }

            if ($targetOpenItem->counterparty_id && $document->counterparty_id && (int) $targetOpenItem->counterparty_id !== (int) $document->counterparty_id) {
                throw new RuntimeException('Settlement target counterparty does not match the settlement document.');
            }

            $remainingSettlement = $this->normalizeAmount((string) $settlementItem->open_amount);
            $remainingTarget = $this->normalizeAmount((string) $targetOpenItem->open_amount);
            if (bccomp($remainingSettlement, '0.0000', 4) !== 1 || bccomp($remainingTarget, '0.0000', 4) !== 1) {
                continue;
            }

            $allocatedAmount = $this->minimum($targetAmount, $remainingSettlement, $remainingTarget);
            if (bccomp($allocatedAmount, '0.0000', 4) !== 1) {
                continue;
            }

            FinanceOpenItemAllocation::query()->create([
                'business_id' => $document->business_id,
                'source_open_item_id' => $settlementItem->id,
                'target_open_item_id' => $targetOpenItem->id,
                'accounting_event_id' => $event->id,
                'allocation_type' => 'settlement',
                'status' => 'active',
                'allocation_date' => $event->posting_date,
                'currency_code' => $document->currency_code,
                'amount' => $allocatedAmount,
                'reason' => $context->reason(),
                'acted_by' => $context->userId(),
                'meta' => ['request_id' => $context->requestId()],
            ]);

            $this->applyAllocationToOpenItem($settlementItem, $allocatedAmount);
            $this->applyAllocationToOpenItem($targetOpenItem, $allocatedAmount);

            $this->recordAudit(
                $document,
                'subledger.open_item_allocated',
                $context,
                [
                    'source_open_item_id' => $settlementItem->id,
                    'target_open_item_id' => $targetOpenItem->id,
                ],
                [
                    'amount' => $allocatedAmount,
                    'source_open_amount' => (string) $settlementItem->open_amount,
                    'target_open_amount' => (string) $targetOpenItem->open_amount,
                ]
            );

            $this->syncDocumentOpenAmount($targetOpenItem->document);
        }
    }

    protected function reverseAllocation(
        FinanceOpenItemAllocation $allocation,
        FinanceAccountingEvent $reversalEvent,
        ActionContext $context
    ): void {
        if ($allocation->status !== 'active') {
            return;
        }

        $source = $allocation->sourceOpenItem;
        $target = $allocation->targetOpenItem;
        if (! $source || ! $target) {
            return;
        }

        $reversal = FinanceOpenItemAllocation::query()->create([
            'business_id' => $allocation->business_id,
            'source_open_item_id' => $allocation->source_open_item_id,
            'target_open_item_id' => $allocation->target_open_item_id,
            'accounting_event_id' => $reversalEvent->id,
            'reverses_allocation_id' => $allocation->id,
            'allocation_type' => 'reversal',
            'status' => 'active',
            'allocation_date' => $reversalEvent->posting_date,
            'currency_code' => $allocation->currency_code,
            'amount' => $allocation->amount,
            'reason' => $context->reason(),
            'acted_by' => $context->userId(),
            'meta' => ['reverses_allocation_id' => $allocation->id],
        ]);

        $allocation->status = 'reversed';
        $allocation->reversed_at = now();
        $allocation->reversed_by = $context->userId();
        $allocation->meta = array_merge((array) $allocation->meta, ['reversal_allocation_id' => $reversal->id]);
        $allocation->save();

        $this->restoreAllocationOnOpenItem($source, (string) $allocation->amount);
        $this->restoreAllocationOnOpenItem($target, (string) $allocation->amount);

        if ($source->document) {
            $this->syncDocumentOpenAmount($source->document);
        }

        if ($target->document) {
            $this->syncDocumentOpenAmount($target->document);
        }

        $anchorDocument = $source->document ?: $target->document;
        if ($anchorDocument) {
            $this->recordAudit(
                $anchorDocument,
                'subledger.open_item_allocation_reversed',
                $context,
                ['allocation_id' => $allocation->id, 'status' => 'active'],
                ['allocation_id' => $allocation->id, 'status' => 'reversed', 'reversal_allocation_id' => $reversal->id]
            );
        }
    }

    protected function applyAllocationToOpenItem(FinanceOpenItem $openItem, string $amount): void
    {
        $openItem->open_amount = $this->normalizeAmount(bcsub((string) $openItem->open_amount, $amount, 4));
        $openItem->settled_amount = $this->normalizeAmount(bcadd((string) $openItem->settled_amount, $amount, 4));
        $openItem->status = $this->statusForAmounts((string) $openItem->original_amount, (string) $openItem->open_amount);
        $openItem->save();
    }

    protected function restoreAllocationOnOpenItem(FinanceOpenItem $openItem, string $amount): void
    {
        $restoredSettledAmount = max(0, (float) bcsub((string) $openItem->settled_amount, $amount, 4));
        $openItem->open_amount = $this->normalizeAmount(bcadd((string) $openItem->open_amount, $amount, 4));
        $openItem->settled_amount = $this->normalizeAmount((string) $restoredSettledAmount);
        $openItem->status = $this->statusForAmounts((string) $openItem->original_amount, (string) $openItem->open_amount);
        $openItem->save();
    }

    protected function syncDocumentOpenAmount(FinanceDocument $document): void
    {
        $openAmount = FinanceOpenItem::query()
            ->where('document_id', $document->id)
            ->where('status', '!=', 'reversed')
            ->sum('open_amount');

        $document->open_amount = $this->normalizeAmount((string) $openAmount);
        $document->save();
    }

    protected function statusForAmounts(string $originalAmount, string $openAmount): string
    {
        if (bccomp($openAmount, '0.0000', 4) === 0) {
            return 'settled';
        }

        if (bccomp($openAmount, $originalAmount, 4) === 0) {
            return 'open';
        }

        return 'partial';
    }

    protected function documentAmount(FinanceDocument $document): string
    {
        foreach ([$document->open_amount, $document->gross_amount, $document->net_amount] as $candidate) {
            $amount = $this->normalizeAmount((string) $candidate);
            if (bccomp($amount, '0.0000', 4) === 1) {
                return $amount;
            }
        }

        return '0.0000';
    }

    protected function minimum(string ...$amounts): string
    {
        $normalized = array_map(fn (string $amount) => $this->normalizeAmount($amount), $amounts);

        return array_reduce($normalized, function (?string $carry, string $amount) {
            if ($carry === null) {
                return $amount;
            }

            return bccomp($amount, $carry, 4) === -1 ? $amount : $carry;
        });
    }

    protected function normalizeAmount(string $amount): string
    {
        if ($amount === '') {
            return '0.0000';
        }

        return number_format((float) $amount, 4, '.', '');
    }

    protected function recordAudit(
        FinanceDocument $document,
        string $eventType,
        ActionContext $context,
        array $beforeState,
        array $afterState
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
}
