<?php

namespace Modules\VasAccounting\Services\Sales;

use Illuminate\Support\Collection;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\OrderToCashLifecycleServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLink;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentStatusHistory;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceOpenItem;

class OrderToCashLifecycleService implements OrderToCashLifecycleServiceInterface
{
    public function calculateSalesOrderSummary(FinanceDocument $salesOrder, iterable $deliveries = [], iterable $directInvoices = []): array
    {
        $salesOrder->loadMissing('lines');
        $deliveries = $this->asCollection($deliveries);
        $directInvoices = $this->asCollection($directInvoices);

        $orderedQuantity = $this->sumDocumentQuantity($salesOrder);
        $postedDeliveries = $deliveries->filter(fn (FinanceDocument $delivery) => $this->isActiveOperationalChild($delivery));
        $deliveredQuantity = $postedDeliveries->sum(fn (FinanceDocument $delivery) => (float) $this->sumDocumentQuantity($delivery));
        $deliveryStatus = $this->deliveryStatusForOrder($salesOrder, $orderedQuantity, $deliveredQuantity);

        $deliveryInvoices = $postedDeliveries
            ->flatMap(fn (FinanceDocument $delivery) => $this->linkedChildren($delivery, 'customer_invoice')->all());

        $allInvoices = $directInvoices
            ->merge($deliveryInvoices)
            ->filter(fn (FinanceDocument $invoice) => $this->isActiveAccountingDocument($invoice))
            ->unique('id')
            ->values();

        $invoiceSummaries = $allInvoices
            ->map(function (FinanceDocument $invoice): array {
                $invoice->loadMissing('openItems');

                return $this->calculateInvoiceCollectionSummary($invoice, $this->receivableChargeOpenItem($invoice));
            })
            ->values();

        $invoicedAmount = $this->sumSummaryField($invoiceSummaries, 'original_amount');
        $collectedAmount = $this->sumSummaryField($invoiceSummaries, 'settled_amount');
        $openReceivableAmount = $this->sumSummaryField($invoiceSummaries, 'open_amount');
        $deliveryProgress = $orderedQuantity > 0
            ? min(1, round($deliveredQuantity / $orderedQuantity, 6))
            : 0.0;

        return [
            'workflow_status' => $deliveryStatus,
            'ordered_quantity' => $this->normalizeAmount($orderedQuantity),
            'delivered_quantity' => $this->normalizeAmount($deliveredQuantity),
            'delivery_progress' => $deliveryProgress,
            'posted_delivery_count' => $postedDeliveries->count(),
            'posted_invoice_count' => $allInvoices->count(),
            'invoiced_amount' => $this->normalizeAmount($invoicedAmount),
            'collected_amount' => $this->normalizeAmount($collectedAmount),
            'open_receivable_amount' => $this->normalizeAmount($openReceivableAmount),
        ];
    }

    public function calculateDeliverySummary(FinanceDocument $delivery, iterable $invoices = []): array
    {
        $invoices = $this->asCollection($invoices)
            ->filter(fn (FinanceDocument $invoice) => $this->isActiveAccountingDocument($invoice))
            ->values();

        $invoiceSummaries = $invoices
            ->map(function (FinanceDocument $invoice): array {
                $invoice->loadMissing('openItems');

                return $this->calculateInvoiceCollectionSummary($invoice, $this->receivableChargeOpenItem($invoice));
            })
            ->values();

        return [
            'posted_invoice_count' => $invoices->count(),
            'invoiced_amount' => $this->normalizeAmount($this->sumSummaryField($invoiceSummaries, 'original_amount')),
            'collected_amount' => $this->normalizeAmount($this->sumSummaryField($invoiceSummaries, 'settled_amount')),
            'open_receivable_amount' => $this->normalizeAmount($this->sumSummaryField($invoiceSummaries, 'open_amount')),
        ];
    }

    public function calculateInvoiceCollectionSummary(FinanceDocument $invoice, ?FinanceOpenItem $chargeOpenItem = null): array
    {
        $chargeOpenItem ??= $this->receivableChargeOpenItem($invoice);

        $originalAmount = $chargeOpenItem ? (float) $chargeOpenItem->original_amount : (float) $invoice->gross_amount;
        $openAmount = $chargeOpenItem ? (float) $chargeOpenItem->open_amount : (float) $invoice->open_amount;
        $settledAmount = $chargeOpenItem ? (float) $chargeOpenItem->settled_amount : max(0, $originalAmount - $openAmount);

        $workflowStatus = $invoice->workflow_status;
        if (! in_array($workflowStatus, ['reversed', 'cancelled', 'closed'], true)) {
            if ($originalAmount > 0 && abs($openAmount) < 0.00005) {
                $workflowStatus = 'collected';
            } elseif ($originalAmount > 0 && $openAmount < ($originalAmount - 0.00005)) {
                $workflowStatus = 'partially_collected';
            } elseif ($invoice->accounting_status === 'posted' || $invoice->workflow_status === 'posted') {
                $workflowStatus = 'posted';
            }
        }

        $collectionRatio = $originalAmount > 0
            ? min(1, round($settledAmount / $originalAmount, 6))
            : 0.0;

        return [
            'workflow_status' => $workflowStatus,
            'original_amount' => $this->normalizeAmount($originalAmount),
            'open_amount' => $this->normalizeAmount($openAmount),
            'settled_amount' => $this->normalizeAmount($settledAmount),
            'collection_ratio' => $collectionRatio,
        ];
    }

    public function syncDocumentChain(FinanceDocument $document, ActionContext $context): void
    {
        $document = FinanceDocument::query()->with(['lines', 'openItems'])->findOrFail($document->id);

        if ($document->document_type === 'sales_order') {
            $this->syncSalesOrder($document, $context);

            return;
        }

        if ($document->document_type === 'delivery') {
            $this->syncDelivery($document, $context);
            $this->salesOrdersFor($document)->each(fn (FinanceDocument $salesOrder) => $this->syncSalesOrder($salesOrder, $context));

            return;
        }

        if ($document->document_type === 'customer_invoice') {
            $this->syncCustomerInvoice($document, $context);
            $this->deliveriesForInvoice($document)->each(fn (FinanceDocument $delivery) => $this->syncDelivery($delivery, $context));
            $this->salesOrdersFor($document)->each(fn (FinanceDocument $salesOrder) => $this->syncSalesOrder($salesOrder, $context));

            return;
        }

        if ($document->document_type === 'customer_receipt') {
            $this->invoicesForReceipt($document)->each(function (FinanceDocument $invoice) use ($context) {
                $this->syncCustomerInvoice($invoice, $context);
                $this->deliveriesForInvoice($invoice)->each(fn (FinanceDocument $delivery) => $this->syncDelivery($delivery, $context));
                $this->salesOrdersFor($invoice)->each(fn (FinanceDocument $salesOrder) => $this->syncSalesOrder($salesOrder, $context));
            });
        }
    }

    protected function syncSalesOrder(FinanceDocument $salesOrder, ActionContext $context): void
    {
        $deliveries = $this->linkedChildren($salesOrder, 'delivery');
        $directInvoices = $this->linkedChildren($salesOrder, 'customer_invoice');
        $summary = $this->calculateSalesOrderSummary($salesOrder, $deliveries, $directInvoices);

        $updates = [];
        if (! in_array($salesOrder->workflow_status, ['closed', 'cancelled', 'reversed'], true)
            && $summary['workflow_status'] !== $salesOrder->workflow_status
        ) {
            $this->recordStatusChange(
                $salesOrder,
                'o2c_fulfillment_synced',
                $salesOrder->workflow_status,
                $summary['workflow_status'],
                $salesOrder->accounting_status,
                $salesOrder->accounting_status,
                $context
            );
            $updates['workflow_status'] = $summary['workflow_status'];
        }

        $this->applySummary($salesOrder, $summary, $updates, $context, 'o2c.sales_order_summary_synced');
    }

    protected function syncDelivery(FinanceDocument $delivery, ActionContext $context): void
    {
        $summary = $this->calculateDeliverySummary($delivery, $this->linkedChildren($delivery, 'customer_invoice'));
        $this->applySummary($delivery, $summary, [], $context, 'o2c.delivery_summary_synced');
    }

    protected function syncCustomerInvoice(FinanceDocument $invoice, ActionContext $context): void
    {
        $summary = $this->calculateInvoiceCollectionSummary($invoice, $this->receivableChargeOpenItem($invoice));
        $updates = [];
        if (! in_array($invoice->workflow_status, ['closed', 'cancelled', 'reversed'], true)
            && $summary['workflow_status'] !== $invoice->workflow_status
        ) {
            $this->recordStatusChange(
                $invoice,
                'o2c_collection_synced',
                $invoice->workflow_status,
                $summary['workflow_status'],
                $invoice->accounting_status,
                $invoice->accounting_status,
                $context
            );
            $updates['workflow_status'] = $summary['workflow_status'];
        }

        $this->applySummary($invoice, $summary, $updates, $context, 'o2c.invoice_summary_synced');
    }

    protected function applySummary(
        FinanceDocument $document,
        array $summary,
        array $updates,
        ActionContext $context,
        string $auditEventType
    ): void {
        $before = $document->toArray();
        $meta = array_merge((array) $document->meta, ['o2c' => $summary]);
        $changed = $meta !== (array) $document->meta;

        if (! empty($updates)) {
            $document->fill($updates);
            $changed = true;
        }

        $document->meta = $meta;

        if (! $changed) {
            return;
        }

        $document->save();

        $this->recordAudit($document, $auditEventType, $context, $before, $document->fresh()->toArray());
    }

    protected function recordStatusChange(
        FinanceDocument $document,
        string $eventName,
        ?string $fromWorkflowStatus,
        ?string $toWorkflowStatus,
        ?string $fromAccountingStatus,
        ?string $toAccountingStatus,
        ActionContext $context
    ): void {
        FinanceDocumentStatusHistory::query()->create([
            'business_id' => $document->business_id,
            'document_id' => $document->id,
            'event_name' => $eventName,
            'from_workflow_status' => $fromWorkflowStatus,
            'to_workflow_status' => $toWorkflowStatus,
            'from_accounting_status' => $fromAccountingStatus,
            'to_accounting_status' => $toAccountingStatus,
            'reason' => $context->reason(),
            'acted_by' => $context->userId(),
            'meta' => $context->meta(),
            'acted_at' => now(),
        ]);
    }

    protected function recordAudit(
        FinanceDocument $document,
        string $eventType,
        ActionContext $context,
        $beforeState,
        $afterState
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

    protected function linkedChildren(FinanceDocument $document, string $documentType): Collection
    {
        if (! $document->exists) {
            return collect();
        }

        return FinanceDocumentLink::query()
            ->with('childDocument.lines', 'childDocument.openItems')
            ->where('parent_document_id', $document->id)
            ->get()
            ->pluck('childDocument')
            ->filter(fn ($child) => $child instanceof FinanceDocument && $child->document_type === $documentType)
            ->values();
    }

    protected function linkedParents(FinanceDocument $document, string $documentType): Collection
    {
        if (! $document->exists) {
            return collect();
        }

        return FinanceDocumentLink::query()
            ->with('parentDocument.lines', 'parentDocument.openItems')
            ->where('child_document_id', $document->id)
            ->get()
            ->pluck('parentDocument')
            ->filter(fn ($parent) => $parent instanceof FinanceDocument && $parent->document_type === $documentType)
            ->values();
    }

    protected function salesOrdersFor(FinanceDocument $document): Collection
    {
        if ($document->document_type === 'sales_order') {
            return collect([$document]);
        }

        if ($document->document_type === 'delivery') {
            return $this->linkedParents($document, 'sales_order')->unique('id')->values();
        }

        if ($document->document_type === 'customer_invoice') {
            return $this->linkedParents($document, 'sales_order')
                ->merge($this->deliveriesForInvoice($document)->flatMap(fn (FinanceDocument $delivery) => $this->linkedParents($delivery, 'sales_order')->all()))
                ->unique('id')
                ->values();
        }

        if ($document->document_type === 'customer_receipt') {
            return $this->invoicesForReceipt($document)
                ->flatMap(fn (FinanceDocument $invoice) => $this->salesOrdersFor($invoice)->all())
                ->unique('id')
                ->values();
        }

        return collect();
    }

    protected function deliveriesForInvoice(FinanceDocument $invoice): Collection
    {
        if ($invoice->document_type !== 'customer_invoice') {
            return collect();
        }

        return $this->linkedParents($invoice, 'delivery')->unique('id')->values();
    }

    protected function invoicesForReceipt(FinanceDocument $receipt): Collection
    {
        if (! $receipt->exists) {
            return collect();
        }

        $receiptItemIds = FinanceOpenItem::query()
            ->where('document_id', $receipt->id)
            ->pluck('id');

        if ($receiptItemIds->isEmpty()) {
            return collect();
        }

        return FinanceOpenItem::query()
            ->with('document.lines', 'document.openItems')
            ->whereIn('id', function ($query) use ($receiptItemIds) {
                $query->select('target_open_item_id')
                    ->from('vas_fin_open_item_allocations')
                    ->whereIn('source_open_item_id', $receiptItemIds->all());
            })
            ->get()
            ->pluck('document')
            ->filter(fn ($document) => $document instanceof FinanceDocument && $document->document_type === 'customer_invoice')
            ->unique('id')
            ->values();
    }

    protected function receivableChargeOpenItem(FinanceDocument $invoice): ?FinanceOpenItem
    {
        $invoice->loadMissing('openItems');

        return $invoice->openItems
            ->first(fn (FinanceOpenItem $openItem) => $openItem->ledger_type === 'receivable'
                && $openItem->document_role === 'charge'
                && $openItem->status !== 'reversed');
    }

    protected function deliveryStatusForOrder(FinanceDocument $salesOrder, float $orderedQuantity, float $deliveredQuantity): string
    {
        if (in_array($salesOrder->workflow_status, ['closed', 'cancelled', 'reversed'], true)) {
            return $salesOrder->workflow_status;
        }

        if ($deliveredQuantity <= 0.00005) {
            return in_array($salesOrder->workflow_status, ['released', 'approved'], true)
                ? $salesOrder->workflow_status
                : 'approved';
        }

        if ($orderedQuantity <= 0.00005 || $deliveredQuantity >= ($orderedQuantity - 0.00005)) {
            return 'fully_delivered';
        }

        return 'partially_delivered';
    }

    protected function isActiveOperationalChild(FinanceDocument $document): bool
    {
        return ! in_array($document->workflow_status, ['reversed', 'cancelled'], true)
            && in_array($document->accounting_status, ['posted', 'closed'], true);
    }

    protected function isActiveAccountingDocument(FinanceDocument $document): bool
    {
        return ! in_array($document->workflow_status, ['reversed', 'cancelled'], true)
            && in_array($document->accounting_status, ['posted', 'closed'], true);
    }

    protected function sumDocumentQuantity(FinanceDocument $document): float
    {
        $document->loadMissing('lines');

        return (float) $document->lines
            ->sum(fn ($line) => (float) $line->quantity);
    }

    protected function sumSummaryField(Collection $summaries, string $field): float
    {
        return (float) $summaries->sum(fn (array $summary) => (float) ($summary[$field] ?? 0));
    }

    protected function normalizeAmount(float|string $amount): string
    {
        return number_format((float) $amount, 4, '.', '');
    }

    protected function asCollection(iterable $items): Collection
    {
        return $items instanceof Collection ? $items : collect($items);
    }
}
