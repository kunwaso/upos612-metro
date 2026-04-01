<?php

namespace Modules\VasAccounting\Services\FinanceCore;

use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Application\DTOs\AccountDerivationInput;
use Modules\VasAccounting\Application\DTOs\PostingContext;
use Modules\VasAccounting\Application\DTOs\PostingPreview;
use Modules\VasAccounting\Application\DTOs\PostingPreviewLine;
use Modules\VasAccounting\Application\DTOs\PostingResult;
use Modules\VasAccounting\Application\DTOs\ReversalContext;
use Modules\VasAccounting\Contracts\AccountDerivationServiceInterface;
use Modules\VasAccounting\Contracts\DocumentTraceServiceInterface;
use Modules\VasAccounting\Contracts\InventoryCostServiceInterface;
use Modules\VasAccounting\Contracts\OpenItemServiceInterface;
use Modules\VasAccounting\Contracts\OrderToCashLifecycleServiceInterface;
use Modules\VasAccounting\Contracts\PostingRuleEngineInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceAccountingEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceAccountingEventLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceJournalEntry;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceJournalEntryLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinancePostingRuleLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinancePostingRuleSet;
use RuntimeException;

class PostingRuleEngineService implements PostingRuleEngineInterface
{
    public function __construct(
        protected AccountDerivationServiceInterface $accountDerivationService,
        protected DocumentTraceServiceInterface $traceService,
        protected InventoryCostServiceInterface $inventoryCostService,
        protected OpenItemServiceInterface $openItemService,
        protected OrderToCashLifecycleServiceInterface $orderToCashLifecycleService,
        protected DocumentWorkflowService $documentWorkflowService
    ) {
    }

    public function preview(FinanceDocument $document, string $eventType): PostingPreview
    {
        $document->loadMissing('lines');
        $ruleSet = $this->resolveRuleSet($document, $eventType);
        $warnings = [];
        $previewLines = $ruleSet
            ? $this->buildFromRuleSet($document, $ruleSet, $warnings)
            : $this->buildFromDocumentHints($document, $warnings);

        $totals = $this->totalsForPreview($previewLines);

        return new PostingPreview(
            $previewLines,
            $this->normalizeAmount($totals['debit']),
            $this->normalizeAmount($totals['credit']),
            bccomp($totals['debit'], $totals['credit'], 4) === 0,
            $ruleSet?->version_no,
            $warnings
        );
    }

    public function post(FinanceDocument $document, string $eventType, PostingContext $context): PostingResult
    {
        return DB::transaction(function () use ($document, $eventType, $context) {
            $document = FinanceDocument::query()->with('lines')->findOrFail($document->id);
            $this->guardPostable($document);

            $preview = $this->preview($document, $eventType);
            if (! $preview->isBalanced) {
                throw new RuntimeException('Finance posting preview is not balanced.');
            }

            $ruleSet = $this->resolveRuleSet($document, $eventType);
            $idempotencyKey = $this->idempotencyKey($document, $eventType);
            $existingEvent = FinanceAccountingEvent::query()
                ->where('business_id', $document->business_id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingEvent) {
                $journal = $existingEvent->journalEntries()->latest('id')->firstOrFail();

                return new PostingResult($existingEvent, $journal, $preview->warnings);
            }

            $event = FinanceAccountingEvent::query()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'posting_rule_set_id' => $ruleSet?->id,
                'event_type' => $eventType,
                'event_status' => 'posted',
                'idempotency_key' => $idempotencyKey,
                'source_hash' => hash('sha256', json_encode([
                    'document_id' => $document->id,
                    'event_type' => $eventType,
                    'workflow_status' => $document->workflow_status,
                    'line_count' => $document->lines->count(),
                ])),
                'posting_date' => $document->posting_date ?: $document->document_date,
                'currency_code' => $document->currency_code,
                'exchange_rate' => $document->exchange_rate ?: 1,
                'total_debit' => $preview->totalDebit,
                'total_credit' => $preview->totalCredit,
                'prepared_by' => $context->userId(),
                'prepared_at' => now(),
                'posted_by' => $context->userId(),
                'posted_at' => now(),
                'warnings' => $preview->warnings,
                'meta' => ['request_id' => $context->requestId()],
            ]);

            foreach (array_values($preview->lines) as $index => $previewLine) {
                FinanceAccountingEventLine::query()->create([
                    'business_id' => $document->business_id,
                    'accounting_event_id' => $event->id,
                    'document_line_id' => $previewLine->documentLineId,
                    'line_no' => $index + 1,
                    'account_id' => $previewLine->accountId,
                    'business_location_id' => data_get($previewLine->dimensions, 'business_location_id'),
                    'contact_id' => data_get($previewLine->dimensions, 'contact_id'),
                    'product_id' => data_get($previewLine->dimensions, 'product_id'),
                    'tax_code_id' => data_get($previewLine->meta, 'tax_code_id'),
                    'posting_date' => $document->posting_date ?: $document->document_date,
                    'debit' => $previewLine->entrySide === 'debit' ? $previewLine->amount : 0,
                    'credit' => $previewLine->entrySide === 'credit' ? $previewLine->amount : 0,
                    'description' => $previewLine->description,
                    'dimensions' => $previewLine->dimensions,
                    'meta' => $previewLine->meta,
                ]);
            }

            $journal = FinanceJournalEntry::query()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'accounting_event_id' => $event->id,
                'accounting_period_id' => $document->accounting_period_id,
                'journal_no' => $this->journalNumber($document, $event),
                'journal_type' => $document->document_family,
                'posting_date' => $event->posting_date,
                'total_debit' => $preview->totalDebit,
                'total_credit' => $preview->totalCredit,
                'status' => 'posted',
                'posted_by' => $context->userId(),
                'posted_at' => now(),
                'meta' => ['event_type' => $eventType],
            ]);

            foreach ($event->fresh('lines')->lines as $eventLine) {
                FinanceJournalEntryLine::query()->create([
                    'business_id' => $document->business_id,
                    'journal_entry_id' => $journal->id,
                    'accounting_event_line_id' => $eventLine->id,
                    'line_no' => $eventLine->line_no,
                    'account_id' => $eventLine->account_id,
                    'business_location_id' => $eventLine->business_location_id,
                    'contact_id' => $eventLine->contact_id,
                    'product_id' => $eventLine->product_id,
                    'tax_code_id' => $eventLine->tax_code_id,
                    'debit' => $eventLine->debit,
                    'credit' => $eventLine->credit,
                    'description' => $eventLine->description,
                    'dimensions' => $eventLine->dimensions,
                    'meta' => $eventLine->meta,
                ]);
            }

            $document->workflow_status = 'posted';
            $document->accounting_status = 'posted';
            $document->posted_at = now();
            $document->posted_by = $context->userId();
            $document->save();

            $this->traceService->linkDocumentToEvent($document->id, $event->id);
            $this->openItemService->syncPostedDocument($document->fresh(), $event->fresh('lines'), $context);
            $this->inventoryCostService->syncPostedDocument($document->fresh(), $event->fresh('lines'), $context);
            $this->orderToCashLifecycleService->syncDocumentChain($document->fresh(), $context);
            $this->recordAudit($document, $event, $journal, 'posting.posted', $context);

            return new PostingResult($event->fresh('lines'), $journal->fresh('lines'), $preview->warnings);
        });
    }

    public function reverse(FinanceDocument $document, string $eventType, ReversalContext $context): PostingResult
    {
        return DB::transaction(function () use ($document, $eventType, $context) {
            $document = FinanceDocument::query()->findOrFail($document->id);
            $originalEvent = FinanceAccountingEvent::query()
                ->with(['lines', 'journalEntries.lines'])
                ->where('document_id', $document->id)
                ->where('event_type', $eventType)
                ->where('event_status', 'posted')
                ->latest('id')
                ->first();

            if (! $originalEvent) {
                throw new RuntimeException('No posted accounting event exists for reversal.');
            }

            if ($originalEvent->reversal_event_id) {
                $reversalEvent = FinanceAccountingEvent::query()->with('journalEntries')->findOrFail($originalEvent->reversal_event_id);
                $reversalJournal = $reversalEvent->journalEntries()->latest('id')->firstOrFail();

                return new PostingResult($reversalEvent, $reversalJournal);
            }

            $reversalEvent = FinanceAccountingEvent::query()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'posting_rule_set_id' => $originalEvent->posting_rule_set_id,
                'event_type' => $eventType . '_reversal',
                'event_status' => 'posted',
                'idempotency_key' => $this->idempotencyKey($document, $eventType . '_reversal'),
                'source_hash' => $originalEvent->source_hash,
                'posting_date' => $document->posting_date ?: now()->toDateString(),
                'currency_code' => $originalEvent->currency_code,
                'exchange_rate' => $originalEvent->exchange_rate,
                'total_debit' => $originalEvent->total_credit,
                'total_credit' => $originalEvent->total_debit,
                'prepared_by' => $context->userId(),
                'prepared_at' => now(),
                'posted_by' => $context->userId(),
                'posted_at' => now(),
                'meta' => ['reverses_event_id' => $originalEvent->id],
            ]);

            foreach ($originalEvent->lines as $line) {
                FinanceAccountingEventLine::query()->create([
                    'business_id' => $line->business_id,
                    'accounting_event_id' => $reversalEvent->id,
                    'document_line_id' => $line->document_line_id,
                    'line_no' => $line->line_no,
                    'account_id' => $line->account_id,
                    'business_location_id' => $line->business_location_id,
                    'contact_id' => $line->contact_id,
                    'product_id' => $line->product_id,
                    'tax_code_id' => $line->tax_code_id,
                    'posting_date' => $reversalEvent->posting_date,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => $line->description,
                    'dimensions' => $line->dimensions,
                    'meta' => array_merge((array) $line->meta, ['reversal_of_line_id' => $line->id]),
                ]);
            }

            $reversalJournal = FinanceJournalEntry::query()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'accounting_event_id' => $reversalEvent->id,
                'accounting_period_id' => $document->accounting_period_id,
                'journal_no' => $this->journalNumber($document, $reversalEvent, 'RV'),
                'journal_type' => 'reversal',
                'posting_date' => $reversalEvent->posting_date,
                'total_debit' => $reversalEvent->total_debit,
                'total_credit' => $reversalEvent->total_credit,
                'status' => 'posted',
                'posted_by' => $context->userId(),
                'posted_at' => now(),
                'meta' => ['reverses_journal_event_id' => $originalEvent->id],
            ]);

            foreach ($reversalEvent->fresh('lines')->lines as $line) {
                FinanceJournalEntryLine::query()->create([
                    'business_id' => $line->business_id,
                    'journal_entry_id' => $reversalJournal->id,
                    'accounting_event_line_id' => $line->id,
                    'line_no' => $line->line_no,
                    'account_id' => $line->account_id,
                    'business_location_id' => $line->business_location_id,
                    'contact_id' => $line->contact_id,
                    'product_id' => $line->product_id,
                    'tax_code_id' => $line->tax_code_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'description' => $line->description,
                    'dimensions' => $line->dimensions,
                    'meta' => $line->meta,
                ]);
            }

            $originalEvent->reversal_event_id = $reversalEvent->id;
            $originalEvent->save();

            $document->workflow_status = 'reversed';
            $document->accounting_status = 'reversed';
            $document->reversed_at = now();
            $document->reversed_by = $context->userId();
            $document->save();

            $this->traceService->linkDocumentToEvent($document->id, $reversalEvent->id);
            $this->openItemService->reverseDocument($document->fresh(), $reversalEvent->fresh('lines'), $context);
            $this->inventoryCostService->reverseDocument($document->fresh(), $reversalEvent->fresh('lines'), $context);
            $this->orderToCashLifecycleService->syncDocumentChain($document->fresh(), $context);
            $this->recordAudit($document, $reversalEvent, $reversalJournal, 'posting.reversed', $context);

            return new PostingResult($reversalEvent->fresh('lines'), $reversalJournal->fresh('lines'));
        });
    }

    protected function buildFromDocumentHints(FinanceDocument $document, array &$warnings): array
    {
        $previewLines = [];
        $lineNo = 1;

        foreach ($document->lines as $documentLine) {
            $baseAmount = $this->lineAmountForDefaultPreview($documentLine);

            if ($documentLine->debit_account_id && bccomp($baseAmount, '0.0000', 4) !== 0) {
                $previewLines[] = new PostingPreviewLine(
                    $lineNo++,
                    $documentLine->id,
                    (int) $documentLine->debit_account_id,
                    'debit',
                    $baseAmount,
                    $documentLine->description,
                    $this->dimensionsForLine($documentLine),
                    ['tax_code_id' => $documentLine->tax_code_id]
                );
            }

            if ($documentLine->credit_account_id && bccomp($baseAmount, '0.0000', 4) !== 0) {
                $previewLines[] = new PostingPreviewLine(
                    $lineNo++,
                    $documentLine->id,
                    (int) $documentLine->credit_account_id,
                    'credit',
                    $baseAmount,
                    $documentLine->description,
                    $this->dimensionsForLine($documentLine),
                    ['tax_code_id' => $documentLine->tax_code_id]
                );
            }

            $taxAmount = $this->normalizeAmount($documentLine->tax_amount);
            if ($documentLine->tax_account_id && bccomp($taxAmount, '0.0000', 4) !== 0) {
                $taxEntrySide = $documentLine->payload['tax_entry_side'] ?? 'debit';
                $previewLines[] = new PostingPreviewLine(
                    $lineNo++,
                    $documentLine->id,
                    (int) $documentLine->tax_account_id,
                    $taxEntrySide === 'credit' ? 'credit' : 'debit',
                    $taxAmount,
                    ($documentLine->description ?: 'Tax') . ' tax',
                    $this->dimensionsForLine($documentLine),
                    ['tax_code_id' => $documentLine->tax_code_id, 'line_type' => 'tax']
                );
            }

            if (! $documentLine->debit_account_id && ! $documentLine->credit_account_id) {
                $warnings[] = sprintf('Finance document line [%s] is missing debit/credit account hints.', (string) $documentLine->line_no);
            }
        }

        return $previewLines;
    }

    protected function buildFromRuleSet(FinanceDocument $document, FinancePostingRuleSet $ruleSet, array &$warnings): array
    {
        $previewLines = [];
        $lineNo = 1;
        $balancingRuleLine = null;

        foreach ($ruleSet->lines as $ruleLine) {
            if ($ruleLine->is_balancing_line) {
                $balancingRuleLine = $ruleLine;
                continue;
            }

            foreach ($document->lines as $documentLine) {
                $amount = $this->amountForRuleLine($documentLine, $ruleLine);
                if (bccomp($amount, '0.0000', 4) === 0) {
                    continue;
                }

                $derivedAccount = $this->accountDerivationService->derive(
                    new AccountDerivationInput($document, $documentLine, $ruleLine, $ruleLine->entry_side)
                );
                $warnings = array_merge($warnings, $derivedAccount->warnings);
                if (! $derivedAccount->accountId) {
                    continue;
                }

                $previewLines[] = new PostingPreviewLine(
                    $lineNo++,
                    $documentLine->id,
                    $derivedAccount->accountId,
                    $ruleLine->entry_side,
                    $amount,
                    $this->descriptionForRuleLine($documentLine, $ruleLine),
                    $this->dimensionsForLine($documentLine),
                    ['tax_code_id' => $documentLine->tax_code_id, 'rule_line_id' => $ruleLine->id]
                );
            }
        }

        if ($balancingRuleLine) {
            $totals = $this->totalsForPreview($previewLines);
            $difference = bcsub($totals['debit'], $totals['credit'], 4);

            if (bccomp($difference, '0.0000', 4) !== 0) {
                $entrySide = bccomp($difference, '0.0000', 4) === 1 ? 'credit' : 'debit';
                $absolute = $this->normalizeAmount(ltrim($difference, '-'));
                $derivedAccount = $this->accountDerivationService->derive(
                    new AccountDerivationInput($document, $document->lines->first(), $balancingRuleLine, $entrySide)
                );
                $warnings = array_merge($warnings, $derivedAccount->warnings);

                if ($derivedAccount->accountId) {
                    $previewLines[] = new PostingPreviewLine(
                        $lineNo,
                        optional($document->lines->first())->id,
                        $derivedAccount->accountId,
                        $entrySide,
                        $absolute,
                        $balancingRuleLine->description_template ?: 'Balancing entry',
                        [],
                        ['rule_line_id' => $balancingRuleLine->id, 'line_type' => 'balancing']
                    );
                }
            }
        }

        return $previewLines;
    }

    protected function resolveRuleSet(FinanceDocument $document, string $eventType): ?FinancePostingRuleSet
    {
        return FinancePostingRuleSet::query()
            ->with('lines')
            ->where('business_id', $document->business_id)
            ->where('document_family', $document->document_family)
            ->where('event_type', $eventType)
            ->where(function ($query) use ($document) {
                $query->whereNull('document_type')
                    ->orWhere('document_type', $document->document_type);
            })
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $document->posting_date ?: $document->document_date)
            ->where(function ($query) use ($document) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $document->posting_date ?: $document->document_date);
            })
            ->orderByDesc('version_no')
            ->orderByDesc('id')
            ->first();
    }

    protected function amountForRuleLine(FinanceDocumentLine $documentLine, FinancePostingRuleLine $ruleLine): string
    {
        return match ($ruleLine->amount_source) {
            'tax_amount' => $this->normalizeAmount($documentLine->tax_amount),
            'gross_amount' => $this->normalizeAmount($documentLine->gross_amount),
            'unit_price' => $this->normalizeAmount($documentLine->unit_price),
            default => $this->normalizeAmount($documentLine->line_amount),
        };
    }

    protected function lineAmountForDefaultPreview(FinanceDocumentLine $documentLine): string
    {
        return $this->normalizeAmount($documentLine->line_amount ?: $documentLine->gross_amount);
    }

    protected function guardPostable(FinanceDocument $document): void
    {
        $this->documentWorkflowService->assertPostable($document);

        if (in_array($document->accounting_status, ['posted', 'reversed'], true)) {
            throw new RuntimeException('Finance document has already been posted or reversed.');
        }
    }

    protected function totalsForPreview(array $previewLines): array
    {
        $debit = '0.0000';
        $credit = '0.0000';

        foreach ($previewLines as $line) {
            if ($line->entrySide === 'debit') {
                $debit = bcadd($debit, $line->amount, 4);
            } else {
                $credit = bcadd($credit, $line->amount, 4);
            }
        }

        return ['debit' => $debit, 'credit' => $credit];
    }

    protected function normalizeAmount($amount): string
    {
        return number_format((float) $amount, 4, '.', '');
    }

    protected function idempotencyKey(FinanceDocument $document, string $eventType): string
    {
        return substr(hash('sha256', implode('|', [
            (string) $document->business_id,
            (string) $document->id,
            $eventType,
            (string) ($document->updated_at ?: $document->created_at ?: now()),
        ])), 0, 120);
    }

    protected function journalNumber(FinanceDocument $document, FinanceAccountingEvent $event, string $prefix = 'JE'): string
    {
        return sprintf('%s-%s-%06d', $prefix, strtoupper(substr((string) $document->document_family, 0, 3)), $event->id);
    }

    protected function descriptionForRuleLine(FinanceDocumentLine $documentLine, FinancePostingRuleLine $ruleLine): string
    {
        if ($ruleLine->description_template) {
            return str_replace(
                [':description', ':line_no'],
                [(string) $documentLine->description, (string) $documentLine->line_no],
                $ruleLine->description_template
            );
        }

        return (string) ($documentLine->description ?: 'Finance posting line');
    }

    protected function dimensionsForLine(FinanceDocumentLine $documentLine): array
    {
        return array_filter(array_merge((array) $documentLine->dimensions, [
            'business_location_id' => $documentLine->business_location_id,
            'contact_id' => $documentLine->contact_id,
            'product_id' => $documentLine->product_id,
        ]), static fn ($value) => $value !== null && $value !== '');
    }

    protected function recordAudit(
        FinanceDocument $document,
        FinanceAccountingEvent $event,
        FinanceJournalEntry $journal,
        string $eventType,
        PostingContext|ReversalContext $context
    ): void {
        FinanceAuditEvent::query()->create([
            'business_id' => $document->business_id,
            'document_id' => $document->id,
            'accounting_event_id' => $event->id,
            'journal_entry_id' => $journal->id,
            'event_type' => $eventType,
            'actor_id' => $context->userId(),
            'reason' => $context->reason(),
            'request_id' => $context->requestId(),
            'ip_address' => $context->ipAddress(),
            'user_agent' => $context->userAgent(),
            'after_state' => [
                'document_status' => $document->workflow_status,
                'accounting_status' => $document->accounting_status,
                'journal_no' => $journal->journal_no,
            ],
            'meta' => $context->meta(),
            'acted_at' => now(),
        ]);
    }
}
