<?php

namespace Modules\VasAccounting\Services\FinanceCore;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\DocumentMatchingServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLink;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchRun;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchRunLine;
use RuntimeException;

class DocumentMatchingService implements DocumentMatchingServiceInterface
{
    public function evaluateSupplierInvoice(FinanceDocument $document, Collection $parentDocuments, array $options = []): array
    {
        if ($document->document_type !== 'supplier_invoice') {
            throw new RuntimeException('Only supplier invoices are supported by the current matching service.');
        }

        $config = array_merge(config('vasaccounting.finance_matching.supplier_invoice', []), $options);
        $invoiceLines = $this->documentLines($document);
        $sourceDocuments = $parentDocuments
            ->filter(fn ($parent) => $parent instanceof FinanceDocument)
            ->values();

        $goodsReceipts = $sourceDocuments->where('document_type', 'goods_receipt')->values();
        $purchaseOrders = $sourceDocuments->where('document_type', 'purchase_order')->values();
        $exceptions = collect();
        $lineResults = collect();

        if (($config['require_parent_link'] ?? true) && $sourceDocuments->isEmpty()) {
            $exceptions->push($this->exceptionPayload(
                null,
                'blocking',
                'missing_source_documents',
                'Supplier invoice matching requires a linked purchase order or goods receipt.'
            ));
        }

        if ($goodsReceipts->isEmpty() && ! ($config['allow_purchase_order_only'] ?? false)) {
            $exceptions->push($this->exceptionPayload(
                null,
                'blocking',
                'missing_goods_receipt',
                'Supplier invoice matching requires at least one linked goods receipt.'
            ));
        }

        $goodsReceiptIndex = $this->buildSourceIndex($goodsReceipts);
        $purchaseOrderIndex = $this->buildSourceIndex($purchaseOrders);

        foreach ($invoiceLines as $invoiceLine) {
            $matchKey = $this->matchKeyForLine($invoiceLine);
            $sourceBucket = $goodsReceiptIndex[$matchKey] ?? null;
            $sourceType = 'goods_receipt';

            if (! $sourceBucket && ($config['allow_purchase_order_only'] ?? false)) {
                $sourceBucket = $purchaseOrderIndex[$matchKey] ?? null;
                $sourceType = 'purchase_order';
            }

            $lineExceptions = collect();
            $matchedQuantity = $sourceBucket['quantity'] ?? '0.0000';
            $matchedAmount = $sourceBucket['line_amount'] ?? '0.0000';
            $matchedTaxAmount = $sourceBucket['tax_amount'] ?? '0.0000';
            $varianceQuantity = $this->normalizeAmount(bcsub($this->lineQuantity($invoiceLine), $matchedQuantity, 4));
            $varianceAmount = $this->normalizeAmount(bcsub($this->lineAmount($invoiceLine), $matchedAmount, 4));
            $varianceTaxAmount = $this->normalizeAmount(bcsub($this->lineTaxAmount($invoiceLine), $matchedTaxAmount, 4));

            if (! $sourceBucket) {
                $lineExceptions->push($this->exceptionPayload(
                    $invoiceLine,
                    'blocking',
                    'missing_source_line',
                    sprintf('No linked source line was found for supplier invoice line [%d].', (int) $invoiceLine->line_no),
                    ['match_key' => $matchKey]
                ));
            } else {
                if ($sourceType === 'purchase_order' && $goodsReceipts->isEmpty()) {
                    $lineExceptions->push($this->exceptionPayload(
                        $invoiceLine,
                        'warning',
                        'purchase_order_only_match',
                        sprintf('Supplier invoice line [%d] matched against purchase order data because no goods receipt is linked.', (int) $invoiceLine->line_no),
                        ['match_key' => $matchKey]
                    ));
                }

                if ($this->exceedsTolerance($varianceQuantity, (string) ($config['quantity_variance_tolerance'] ?? '0.0001'))) {
                    $lineExceptions->push($this->exceptionPayload(
                        $invoiceLine,
                        'blocking',
                        'quantity_variance_exceeded',
                        sprintf('Supplier invoice line [%d] quantity variance exceeds tolerance.', (int) $invoiceLine->line_no),
                        ['variance_quantity' => $varianceQuantity]
                    ));
                }

                if ($this->exceedsTolerance($varianceAmount, (string) ($config['amount_variance_tolerance'] ?? '0.0100'))) {
                    $lineExceptions->push($this->exceptionPayload(
                        $invoiceLine,
                        'blocking',
                        'amount_variance_exceeded',
                        sprintf('Supplier invoice line [%d] amount variance exceeds tolerance.', (int) $invoiceLine->line_no),
                        ['variance_amount' => $varianceAmount]
                    ));
                }

                if ($this->exceedsTolerance($varianceTaxAmount, (string) ($config['tax_variance_tolerance'] ?? '0.0100'))) {
                    $lineExceptions->push($this->exceptionPayload(
                        $invoiceLine,
                        'blocking',
                        'tax_variance_exceeded',
                        sprintf('Supplier invoice line [%d] tax variance exceeds tolerance.', (int) $invoiceLine->line_no),
                        ['variance_tax_amount' => $varianceTaxAmount]
                    ));
                }
            }

            $lineStatus = $lineExceptions->contains(fn (array $exception) => $exception['severity'] === 'blocking')
                ? 'exception'
                : ($lineExceptions->isNotEmpty() ? 'warning' : 'matched');

            $lineResults->push([
                'document_line_id' => $invoiceLine->id,
                'source_document_id' => data_get($sourceBucket, 'primary_source_document_id'),
                'source_document_line_id' => data_get($sourceBucket, 'primary_source_document_line_id'),
                'source_document_type' => $sourceBucket ? $sourceType : null,
                'status' => $lineStatus,
                'matched_quantity' => $matchedQuantity,
                'matched_amount' => $matchedAmount,
                'matched_tax_amount' => $matchedTaxAmount,
                'variance_quantity' => $varianceQuantity,
                'variance_amount' => $varianceAmount,
                'variance_tax_amount' => $varianceTaxAmount,
                'meta' => [
                    'match_key' => $matchKey,
                    'matched_source_documents' => data_get($sourceBucket, 'source_documents', []),
                    'matched_source_lines' => data_get($sourceBucket, 'source_lines', []),
                ],
            ]);

            $exceptions = $exceptions->concat($lineExceptions);
        }

        $blockingExceptionCount = $exceptions->where('severity', 'blocking')->count();
        $warningCount = $exceptions->where('severity', 'warning')->count();
        $matchedLineCount = $lineResults->where('status', 'matched')->count();
        $status = $blockingExceptionCount > 0
            ? 'blocked'
            : ($warningCount > 0 ? 'matched_with_warning' : 'matched');

        return [
            'status' => $status,
            'total_line_count' => $invoiceLines->count(),
            'matched_line_count' => $matchedLineCount,
            'blocking_exception_count' => $blockingExceptionCount,
            'warning_count' => $warningCount,
            'parent_document_ids' => $sourceDocuments->pluck('id')->values()->all(),
            'line_results' => $lineResults->values()->all(),
            'exceptions' => $exceptions->values()->all(),
            'meta' => [
                'allow_purchase_order_only' => (bool) ($config['allow_purchase_order_only'] ?? false),
                'tolerances' => [
                    'quantity' => (string) ($config['quantity_variance_tolerance'] ?? '0.0001'),
                    'amount' => (string) ($config['amount_variance_tolerance'] ?? '0.0100'),
                    'tax' => (string) ($config['tax_variance_tolerance'] ?? '0.0100'),
                ],
            ],
        ];
    }

    public function matchSupplierInvoice(FinanceDocument $document, ActionContext $context): FinanceMatchRun
    {
        return DB::transaction(function () use ($document, $context) {
            $document = FinanceDocument::query()->with('lines')->findOrFail($document->id);
            $parents = FinanceDocumentLink::query()
                ->with('parentDocument.lines')
                ->where('child_document_id', $document->id)
                ->get()
                ->pluck('parentDocument')
                ->filter()
                ->values();

            $evaluation = $this->evaluateSupplierInvoice($document, $parents);
            $run = FinanceMatchRun::query()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'match_type' => 'supplier_invoice',
                'status' => $evaluation['status'],
                'total_line_count' => $evaluation['total_line_count'],
                'matched_line_count' => $evaluation['matched_line_count'],
                'blocking_exception_count' => $evaluation['blocking_exception_count'],
                'warning_count' => $evaluation['warning_count'],
                'parent_document_ids' => $evaluation['parent_document_ids'],
                'meta' => $evaluation['meta'],
                'matched_at' => now(),
                'matched_by' => $context->userId(),
            ]);

            foreach ($evaluation['line_results'] as $lineResult) {
                FinanceMatchRunLine::query()->create(array_merge($lineResult, [
                    'business_id' => $document->business_id,
                    'match_run_id' => $run->id,
                ]));
            }

            foreach ($evaluation['exceptions'] as $exception) {
                FinanceMatchException::query()->create([
                    'business_id' => $document->business_id,
                    'document_id' => $document->id,
                    'match_run_id' => $run->id,
                    'document_line_id' => $exception['document_line_id'],
                    'severity' => $exception['severity'],
                    'code' => $exception['code'],
                    'status' => 'open',
                    'message' => $exception['message'],
                    'meta' => $exception['meta'] ?? null,
                ]);
            }

            $document->meta = array_merge((array) $document->meta, [
                'matching' => [
                    'latest_run_id' => $run->id,
                    'latest_status' => $run->status,
                    'blocking_exception_count' => $run->blocking_exception_count,
                    'warning_count' => $run->warning_count,
                    'matched_line_count' => $run->matched_line_count,
                    'total_line_count' => $run->total_line_count,
                    'matched_at' => optional($run->matched_at)->toDateTimeString(),
                ],
            ]);
            $document->save();

            $this->recordAudit(
                $document,
                $run->status === 'blocked' ? 'document.match_blocked' : 'document.match_evaluated',
                $context,
                null,
                [
                    'match_run_id' => $run->id,
                    'status' => $run->status,
                    'blocking_exception_count' => $run->blocking_exception_count,
                    'warning_count' => $run->warning_count,
                ]
            );

            return $run->fresh(['lines', 'exceptions']);
        });
    }

    public function latestRunForDocument(FinanceDocument $document): ?FinanceMatchRun
    {
        return FinanceMatchRun::query()
            ->where('document_id', $document->id)
            ->with(['lines', 'exceptions'])
            ->latest('id')
            ->first();
    }

    protected function buildSourceIndex(Collection $documents): array
    {
        $index = [];

        foreach ($documents as $document) {
            foreach ($this->documentLines($document) as $line) {
                $key = $this->matchKeyForLine($line);
                if (! isset($index[$key])) {
                    $index[$key] = [
                        'quantity' => '0.0000',
                        'line_amount' => '0.0000',
                        'tax_amount' => '0.0000',
                        'primary_source_document_id' => $document->id,
                        'primary_source_document_line_id' => $line->id,
                        'source_documents' => [],
                        'source_lines' => [],
                    ];
                }

                $index[$key]['quantity'] = $this->normalizeAmount(bcadd($index[$key]['quantity'], $this->lineQuantity($line), 4));
                $index[$key]['line_amount'] = $this->normalizeAmount(bcadd($index[$key]['line_amount'], $this->lineAmount($line), 4));
                $index[$key]['tax_amount'] = $this->normalizeAmount(bcadd($index[$key]['tax_amount'], $this->lineTaxAmount($line), 4));
                $index[$key]['source_documents'][] = $document->id;
                $index[$key]['source_lines'][] = $line->id;
            }
        }

        foreach ($index as $key => $bucket) {
            $index[$key]['source_documents'] = array_values(array_unique(array_filter($bucket['source_documents'])));
            $index[$key]['source_lines'] = array_values(array_unique(array_filter($bucket['source_lines'])));
        }

        return $index;
    }

    protected function documentLines(FinanceDocument $document): Collection
    {
        if ($document->relationLoaded('lines')) {
            return $document->lines;
        }

        return $document->lines()->get();
    }

    protected function matchKeyForLine(FinanceDocumentLine $line): string
    {
        if ($line->product_id) {
            return 'product:' . $line->product_id;
        }

        if ($line->source_line_reference) {
            return 'reference:' . $line->source_line_reference;
        }

        return 'line:' . $line->line_no;
    }

    protected function lineQuantity(FinanceDocumentLine $line): string
    {
        return $this->normalizeAmount((string) ($line->quantity ?? '0'));
    }

    protected function lineAmount(FinanceDocumentLine $line): string
    {
        return $this->normalizeAmount((string) ($line->line_amount ?? '0'));
    }

    protected function lineTaxAmount(FinanceDocumentLine $line): string
    {
        return $this->normalizeAmount((string) ($line->tax_amount ?? '0'));
    }

    protected function exceedsTolerance(string $variance, string $tolerance): bool
    {
        return bccomp($this->absolute($variance), $this->normalizeAmount($tolerance), 4) === 1;
    }

    protected function absolute(string $amount): string
    {
        return str_starts_with($amount, '-') ? substr($amount, 1) : $amount;
    }

    protected function normalizeAmount(string $amount): string
    {
        return number_format((float) $amount, 4, '.', '');
    }

    protected function exceptionPayload(
        ?FinanceDocumentLine $line,
        string $severity,
        string $code,
        string $message,
        array $meta = []
    ): array {
        return [
            'document_line_id' => $line?->id,
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'meta' => $meta,
        ];
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
}
