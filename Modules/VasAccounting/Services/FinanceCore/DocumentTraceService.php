<?php

namespace Modules\VasAccounting\Services\FinanceCore;

use Modules\VasAccounting\Application\DTOs\DocumentTraceView;
use Modules\VasAccounting\Contracts\DocumentTraceServiceInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceAccountingEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceInventoryMovement;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchRun;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchRunLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceOpenItem;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceOpenItemAllocation;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryReconciliation;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTraceLink;

class DocumentTraceService implements DocumentTraceServiceInterface
{
    public function linkDocumentToEvent(int $documentId, int $eventId): void
    {
        $document = FinanceDocument::query()->with('lines')->findOrFail($documentId);
        $event = FinanceAccountingEvent::query()->with('lines', 'journalEntries.lines')->findOrFail($eventId);

        FinanceTraceLink::query()->firstOrCreate(
            [
                'document_id' => $document->id,
                'accounting_event_id' => $event->id,
                'link_type' => 'document_event',
            ],
            [
                'business_id' => $document->business_id,
                'journal_entry_id' => optional($event->journalEntries->first())->id,
                'meta' => ['linked_via' => 'posting_engine'],
            ]
        );

        foreach ($event->lines as $eventLine) {
            FinanceTraceLink::query()->firstOrCreate(
                [
                    'document_id' => $document->id,
                    'document_line_id' => $eventLine->document_line_id,
                    'accounting_event_id' => $event->id,
                    'accounting_event_line_id' => $eventLine->id,
                    'link_type' => 'document_line_event_line',
                ],
                [
                    'business_id' => $document->business_id,
                    'journal_entry_id' => optional($event->journalEntries->first())->id,
                    'meta' => ['line_no' => $eventLine->line_no],
                ]
            );
        }
    }

    public function traceDocument(int $documentId): DocumentTraceView
    {
        $document = FinanceDocument::query()
            ->with([
                'lines',
                'statusHistory',
                'accountingEvents.lines',
                'journalEntries.lines',
                'traceLinks',
                'openItems.allocationsFrom',
                'openItems.allocationsTo',
                'matchRuns.lines',
                'matchRuns.exceptions',
                'inventoryMovements.costSettlements',
                'treasuryReconciliations.statementLine.statementImport.bankAccount',
            ])
            ->findOrFail($documentId);

        return new DocumentTraceView(
            [
                'id' => $document->id,
                'document_no' => $document->document_no,
                'document_family' => $document->document_family,
                'document_type' => $document->document_type,
                'workflow_status' => $document->workflow_status,
                'accounting_status' => $document->accounting_status,
                'document_date' => optional($document->document_date)->toDateString(),
                'posting_date' => optional($document->posting_date)->toDateString(),
                'gross_amount' => (string) $document->gross_amount,
                'tax_amount' => (string) $document->tax_amount,
                'net_amount' => (string) $document->net_amount,
                'o2c_summary' => data_get($document->meta, 'o2c'),
            ],
            $document->accountingEvents->map(function (FinanceAccountingEvent $event) {
                return [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'event_status' => $event->event_status,
                    'posting_date' => optional($event->posting_date)->toDateString(),
                    'total_debit' => (string) $event->total_debit,
                    'total_credit' => (string) $event->total_credit,
                ];
            })->values()->all(),
            $document->journalEntries->map(function ($journal) {
                return [
                    'id' => $journal->id,
                    'journal_no' => $journal->journal_no,
                    'journal_type' => $journal->journal_type,
                    'posting_date' => optional($journal->posting_date)->toDateString(),
                    'total_debit' => (string) $journal->total_debit,
                    'total_credit' => (string) $journal->total_credit,
                    'line_count' => $journal->lines->count(),
                ];
            })->values()->all(),
            $document->traceLinks->map(function (FinanceTraceLink $link) {
                return [
                    'id' => $link->id,
                    'link_type' => $link->link_type,
                    'document_line_id' => $link->document_line_id,
                    'accounting_event_id' => $link->accounting_event_id,
                    'accounting_event_line_id' => $link->accounting_event_line_id,
                    'journal_entry_id' => $link->journal_entry_id,
                    'journal_entry_line_id' => $link->journal_entry_line_id,
                ];
            })->values()->all(),
            $document->openItems->map(function (FinanceOpenItem $openItem) {
                return [
                    'id' => $openItem->id,
                    'ledger_type' => $openItem->ledger_type,
                    'document_role' => $openItem->document_role,
                    'status' => $openItem->status,
                    'counterparty_id' => $openItem->counterparty_id,
                    'original_amount' => (string) $openItem->original_amount,
                    'open_amount' => (string) $openItem->open_amount,
                    'settled_amount' => (string) $openItem->settled_amount,
                    'allocations_from' => $openItem->allocationsFrom->where('status', 'active')->map(function (FinanceOpenItemAllocation $allocation) {
                        return [
                            'id' => $allocation->id,
                            'target_open_item_id' => $allocation->target_open_item_id,
                            'amount' => (string) $allocation->amount,
                            'allocation_date' => optional($allocation->allocation_date)->toDateString(),
                        ];
                    })->values()->all(),
                    'allocations_to' => $openItem->allocationsTo->where('status', 'active')->map(function (FinanceOpenItemAllocation $allocation) {
                        return [
                            'id' => $allocation->id,
                            'source_open_item_id' => $allocation->source_open_item_id,
                            'amount' => (string) $allocation->amount,
                            'allocation_date' => optional($allocation->allocation_date)->toDateString(),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            $document->matchRuns->map(function (FinanceMatchRun $matchRun) {
                return [
                    'id' => $matchRun->id,
                    'match_type' => $matchRun->match_type,
                    'status' => $matchRun->status,
                    'matched_line_count' => $matchRun->matched_line_count,
                    'total_line_count' => $matchRun->total_line_count,
                    'blocking_exception_count' => $matchRun->blocking_exception_count,
                    'warning_count' => $matchRun->warning_count,
                    'matched_at' => optional($matchRun->matched_at)->toDateTimeString(),
                    'lines' => $matchRun->lines->map(function (FinanceMatchRunLine $line) {
                        return [
                            'id' => $line->id,
                            'document_line_id' => $line->document_line_id,
                            'source_document_id' => $line->source_document_id,
                            'source_document_type' => $line->source_document_type,
                            'status' => $line->status,
                            'matched_quantity' => (string) $line->matched_quantity,
                            'matched_amount' => (string) $line->matched_amount,
                            'matched_tax_amount' => (string) $line->matched_tax_amount,
                            'variance_quantity' => (string) $line->variance_quantity,
                            'variance_amount' => (string) $line->variance_amount,
                            'variance_tax_amount' => (string) $line->variance_tax_amount,
                        ];
                    })->values()->all(),
                    'exceptions' => $matchRun->exceptions->map(function (FinanceMatchException $exception) {
                        return [
                            'id' => $exception->id,
                            'document_line_id' => $exception->document_line_id,
                            'severity' => $exception->severity,
                            'code' => $exception->code,
                            'status' => $exception->status,
                            'message' => $exception->message,
                            'owner_id' => $exception->owner_id,
                            'reviewed_by' => $exception->reviewed_by,
                            'reviewed_at' => optional($exception->reviewed_at)->toDateTimeString(),
                            'resolved_by' => $exception->resolved_by,
                            'resolved_at' => optional($exception->resolved_at)->toDateTimeString(),
                            'resolution_note' => $exception->resolution_note,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            $document->inventoryMovements->map(function (FinanceInventoryMovement $movement) {
                return [
                    'id' => $movement->id,
                    'document_line_id' => $movement->document_line_id,
                    'product_id' => $movement->product_id,
                    'business_location_id' => $movement->business_location_id,
                    'movement_type' => $movement->movement_type,
                    'direction' => $movement->direction,
                    'status' => $movement->status,
                    'quantity' => (string) $movement->quantity,
                    'unit_cost' => (string) $movement->unit_cost,
                    'total_cost' => (string) $movement->total_cost,
                    'movement_date' => optional($movement->movement_date)->toDateString(),
                    'cost_settlements' => $movement->costSettlements->map(function ($settlement) {
                        return [
                            'id' => $settlement->id,
                            'cost_layer_id' => $settlement->cost_layer_id,
                            'settled_quantity' => (string) $settlement->settled_quantity,
                            'settled_value' => (string) $settlement->settled_value,
                            'unit_cost' => (string) $settlement->unit_cost,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            $document->treasuryReconciliations->map(function (FinanceTreasuryReconciliation $reconciliation) {
                return [
                    'id' => $reconciliation->id,
                    'statement_line_id' => $reconciliation->statement_line_id,
                    'open_item_id' => $reconciliation->open_item_id,
                    'status' => $reconciliation->status,
                    'direction' => $reconciliation->direction,
                    'match_confidence' => (string) $reconciliation->match_confidence,
                    'statement_amount' => (string) $reconciliation->statement_amount,
                    'document_amount' => (string) $reconciliation->document_amount,
                    'matched_amount' => (string) $reconciliation->matched_amount,
                    'reconciled_at' => optional($reconciliation->reconciled_at)->toDateTimeString(),
                    'statement_line' => [
                        'transaction_date' => optional(optional($reconciliation->statementLine)->transaction_date)->toDateString(),
                        'description' => optional($reconciliation->statementLine)->description,
                        'match_status' => optional($reconciliation->statementLine)->match_status,
                        'statement_reference' => optional(optional($reconciliation->statementLine)->statementImport)->reference_no,
                        'bank_account_code' => optional(optional(optional($reconciliation->statementLine)->statementImport)->bankAccount)->account_code,
                    ],
                ];
            })->values()->all(),
            data_get($document->meta, 'o2c')
        );
    }
}
