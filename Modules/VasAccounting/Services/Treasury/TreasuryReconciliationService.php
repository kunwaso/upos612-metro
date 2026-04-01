<?php

namespace Modules\VasAccounting\Services\Treasury;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\TreasuryReconciliationServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceOpenItem;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryReconciliation;
use Modules\VasAccounting\Entities\VasBankStatementLine;
use RuntimeException;

class TreasuryReconciliationService implements TreasuryReconciliationServiceInterface
{
    public function resolveCandidateDocumentTypes(string $statementAmount): array
    {
        return bccomp($this->normalizeAmount($statementAmount), '0.0000', 4) === -1
            ? ['supplier_payment']
            : ['customer_receipt'];
    }

    public function scoreCandidate(VasBankStatementLine $statementLine, FinanceDocument $document): array
    {
        $statementAmount = $this->normalizeAmount((string) $statementLine->amount);
        $documentAmount = $this->documentAmount($document);
        $statementAbsolute = $this->absoluteAmount($statementAmount);
        $documentAbsolute = $this->absoluteAmount($documentAmount);
        $variance = $this->absoluteAmount(bcsub($statementAbsolute, $documentAbsolute, 4));
        $amountTolerance = $this->normalizeAmount((string) config('vasaccounting.treasury_reconciliation.amount_tolerance', '0.0100'));
        $bankAccountId = $this->statementBankAccountId($statementLine);
        $candidateBankAccountId = $this->documentBankAccountId($document);
        $dateDistance = $this->dateDistanceInDays(
            optional($statementLine->transaction_date)->toDateString(),
            optional($document->posting_date ?: $document->document_date)->toDateString()
        );
        $referenceMatched = $this->referenceMatched($statementLine, $document);
        $bankMatched = $bankAccountId && $candidateBankAccountId && $bankAccountId === $candidateBankAccountId;
        $score = 0.0;
        $warnings = [];

        if (bccomp($variance, $amountTolerance, 4) <= 0) {
            $score += 60;
        } elseif (bccomp($variance, $this->normalizeAmount((string) config('vasaccounting.treasury_reconciliation.candidate_amount_variance', '1000.0000')), 4) <= 0) {
            $score += 20;
            $warnings[] = 'Amount variance exceeds the reconciliation tolerance.';
        }

        if ($bankMatched) {
            $score += 20;
        } elseif ($bankAccountId && $candidateBankAccountId && $bankAccountId !== $candidateBankAccountId) {
            $warnings[] = 'Bank account differs from the statement import account.';
        }

        if ($referenceMatched) {
            $score += 15;
        }

        if ($dateDistance <= 1) {
            $score += 10;
        } elseif ($dateDistance <= 3) {
            $score += 5;
        } elseif ($dateDistance > (int) config('vasaccounting.treasury_reconciliation.candidate_date_window_days', 7)) {
            $warnings[] = 'Posting date falls outside the preferred reconciliation window.';
        }

        return [
            'score' => min(100.0, $score),
            'within_amount_tolerance' => bccomp($variance, $amountTolerance, 4) <= 0,
            'amount_variance' => $variance,
            'date_distance_days' => $dateDistance,
            'reference_matched' => $referenceMatched,
            'bank_account_matched' => $bankMatched,
            'warnings' => $warnings,
        ];
    }

    public function suggestCandidates(VasBankStatementLine $statementLine, int $businessId, ?int $limit = null): array
    {
        $statementLine->loadMissing('statementImport.bankAccount');
        $candidateTypes = $this->resolveCandidateDocumentTypes((string) $statementLine->amount);
        $limit = $limit ?: (int) config('vasaccounting.treasury_reconciliation.default_candidate_limit', 5);
        $windowDays = (int) config('vasaccounting.treasury_reconciliation.candidate_date_window_days', 7);
        $startDate = optional($statementLine->transaction_date)?->copy()->subDays($windowDays)->toDateString();
        $endDate = optional($statementLine->transaction_date)?->copy()->addDays($windowDays)->toDateString();

        return FinanceDocument::query()
            ->with('openItems')
            ->where('business_id', $businessId)
            ->whereIn('document_type', $candidateTypes)
            ->where('accounting_status', 'posted')
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('posting_date', [$startDate, $endDate]);
            })
            ->orderByDesc('posting_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (FinanceDocument $document) use ($statementLine) {
                $score = $this->scoreCandidate($statementLine, $document);
                $settlementOpenItem = $document->openItems
                    ->first(fn (FinanceOpenItem $openItem) => $openItem->document_role === 'settlement' && $openItem->status !== 'reversed');

                return [
                    'document_id' => $document->id,
                    'document_no' => $document->document_no,
                    'document_type' => $document->document_type,
                    'posting_date' => optional($document->posting_date ?: $document->document_date)->toDateString(),
                    'counterparty_id' => $document->counterparty_id,
                    'bank_account_id' => $this->documentBankAccountId($document),
                    'open_item_id' => optional($settlementOpenItem)->id,
                    'amount' => $this->documentAmount($document),
                    'score' => $score['score'],
                    'within_amount_tolerance' => $score['within_amount_tolerance'],
                    'amount_variance' => $score['amount_variance'],
                    'date_distance_days' => $score['date_distance_days'],
                    'reference_matched' => $score['reference_matched'],
                    'bank_account_matched' => $score['bank_account_matched'],
                    'warnings' => $score['warnings'],
                ];
            })
            ->filter(fn (array $candidate) => $candidate['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    public function reconcile(
        VasBankStatementLine $statementLine,
        FinanceDocument $document,
        ActionContext $context,
        ?int $openItemId = null
    ): FinanceTreasuryReconciliation {
        return DB::transaction(function () use ($statementLine, $document, $context, $openItemId) {
            $statementLine = VasBankStatementLine::query()
                ->with('statementImport.bankAccount')
                ->lockForUpdate()
                ->findOrFail($statementLine->id);
            $document = FinanceDocument::query()
                ->with(['openItems', 'accountingEvents', 'treasuryReconciliations'])
                ->lockForUpdate()
                ->findOrFail($document->id);

            $this->guardReconciliation($statementLine, $document);

            $existing = FinanceTreasuryReconciliation::query()
                ->where('statement_line_id', $statementLine->id)
                ->where('status', 'active')
                ->first();

            if ($existing && (int) $existing->document_id === (int) $document->id) {
                return $existing->fresh(['statementLine.statementImport.bankAccount', 'document']);
            }

            if ($existing) {
                throw new RuntimeException('Statement line already has an active reconciliation.');
            }

            $score = $this->scoreCandidate($statementLine, $document);
            if (! $score['within_amount_tolerance']) {
                throw new RuntimeException('Statement line amount does not match the finance document within tolerance.');
            }

            $openItem = $this->resolveSettlementOpenItem($document, $openItemId);
            $matchedAmount = $this->minimum(
                $this->absoluteAmount((string) $statementLine->amount),
                $this->absoluteAmount($this->documentAmount($document))
            );

            $reconciliation = FinanceTreasuryReconciliation::query()->create([
                'business_id' => $document->business_id,
                'statement_line_id' => $statementLine->id,
                'document_id' => $document->id,
                'open_item_id' => optional($openItem)->id,
                'accounting_event_id' => optional($document->accountingEvents->sortByDesc('id')->first())->id,
                'reconciliation_type' => 'statement_match',
                'direction' => bccomp((string) $statementLine->amount, '0.0000', 4) === -1 ? 'outbound' : 'inbound',
                'status' => 'active',
                'match_confidence' => $this->normalizeAmount((string) $score['score']),
                'statement_amount' => $statementLine->amount,
                'document_amount' => $this->documentAmount($document),
                'matched_amount' => $matchedAmount,
                'currency_code' => $document->currency_code ?: 'VND',
                'match_notes' => empty($score['warnings']) ? null : implode(' ', $score['warnings']),
                'reconciled_by' => $context->userId(),
                'reconciled_at' => now(),
                'meta' => [
                    'request_id' => $context->requestId(),
                    'reference_matched' => $score['reference_matched'],
                    'bank_account_matched' => $score['bank_account_matched'],
                    'date_distance_days' => $score['date_distance_days'],
                    'warnings' => $score['warnings'],
                ],
            ]);

            $this->syncStatementLineMatchState($statementLine);
            $this->syncDocumentTreasuryMeta($document);
            $this->recordAudit(
                $document,
                'treasury.reconciled',
                $context,
                ['statement_line_id' => $statementLine->id, 'status' => 'unmatched'],
                ['statement_line_id' => $statementLine->id, 'status' => 'matched', 'reconciliation_id' => $reconciliation->id]
            );

            return $reconciliation->fresh(['statementLine.statementImport.bankAccount', 'document']);
        });
    }

    public function reverse(FinanceTreasuryReconciliation $reconciliation, ActionContext $context): FinanceTreasuryReconciliation
    {
        return DB::transaction(function () use ($reconciliation, $context) {
            $reconciliation = FinanceTreasuryReconciliation::query()
                ->with(['document', 'statementLine.statementImport.bankAccount'])
                ->lockForUpdate()
                ->findOrFail($reconciliation->id);

            if ($reconciliation->status !== 'active') {
                return $reconciliation;
            }

            $reconciliation->status = 'reversed';
            $reconciliation->reversed_by = $context->userId();
            $reconciliation->reversed_at = now();
            $reconciliation->meta = array_merge((array) $reconciliation->meta, [
                'reversal_request_id' => $context->requestId(),
                'reversal_reason' => $context->reason(),
            ]);
            $reconciliation->save();

            $this->syncStatementLineMatchState($reconciliation->statementLine);
            $this->syncDocumentTreasuryMeta($reconciliation->document);
            $this->recordAudit(
                $reconciliation->document,
                'treasury.reconciliation_reversed',
                $context,
                ['reconciliation_id' => $reconciliation->id, 'status' => 'active'],
                ['reconciliation_id' => $reconciliation->id, 'status' => 'reversed']
            );

            return $reconciliation->fresh(['statementLine.statementImport.bankAccount', 'document']);
        });
    }

    protected function guardReconciliation(VasBankStatementLine $statementLine, FinanceDocument $document): void
    {
        if ((int) $statementLine->business_id !== (int) $document->business_id) {
            throw new RuntimeException('Statement line and finance document belong to different businesses.');
        }

        if (! in_array($document->document_type, ['customer_receipt', 'supplier_payment'], true)) {
            throw new RuntimeException('Finance document type is not eligible for treasury reconciliation.');
        }

        if ($document->accounting_status !== 'posted') {
            throw new RuntimeException('Only posted finance documents can be reconciled to a bank statement line.');
        }

        if (! in_array($document->document_type, $this->resolveCandidateDocumentTypes((string) $statementLine->amount), true)) {
            throw new RuntimeException('Statement line direction does not match the finance document type.');
        }
    }

    protected function resolveSettlementOpenItem(FinanceDocument $document, ?int $openItemId): ?FinanceOpenItem
    {
        if ($openItemId) {
            $openItem = $document->openItems->first(fn (FinanceOpenItem $candidate) => (int) $candidate->id === $openItemId);
            if (! $openItem) {
                throw new RuntimeException('Selected settlement open item does not belong to the finance document.');
            }

            if ($openItem->document_role !== 'settlement' || $openItem->status === 'reversed') {
                throw new RuntimeException('Selected open item is not an active settlement open item.');
            }

            return $openItem;
        }

        return $document->openItems
            ->first(fn (FinanceOpenItem $candidate) => $candidate->document_role === 'settlement' && $candidate->status !== 'reversed');
    }

    protected function syncStatementLineMatchState(VasBankStatementLine $statementLine): void
    {
        $statementLine = $statementLine->fresh('financeReconciliations.document');
        $activeReconciliation = $statementLine->financeReconciliations
            ->where('status', 'active')
            ->sortByDesc('id')
            ->first();
        $statementMeta = (array) $statementLine->meta;

        if (! $activeReconciliation) {
            unset($statementMeta['canonical_treasury_match']);
            $statementLine->match_status = 'unmatched';
            $statementLine->matched_voucher_id = null;
            $statementLine->meta = $statementMeta;
            $statementLine->save();

            return;
        }

        $statementMeta['canonical_treasury_match'] = [
            'reconciliation_id' => $activeReconciliation->id,
            'document_id' => $activeReconciliation->document_id,
            'document_no' => optional($activeReconciliation->document)->document_no,
            'document_type' => optional($activeReconciliation->document)->document_type,
            'matched_amount' => (string) $activeReconciliation->matched_amount,
            'reconciled_at' => optional($activeReconciliation->reconciled_at)->toDateTimeString(),
        ];

        $statementLine->match_status = 'matched';
        $statementLine->matched_voucher_id = data_get($activeReconciliation->document?->meta, 'legacy_links.voucher_id')
            ?: data_get($activeReconciliation->document?->meta, 'legacy_links.matched_voucher_id');
        $statementLine->meta = $statementMeta;
        $statementLine->save();
    }

    protected function syncDocumentTreasuryMeta(FinanceDocument $document): void
    {
        $document = $document->fresh('treasuryReconciliations');
        $activeReconciliations = $document->treasuryReconciliations->where('status', 'active');
        $meta = (array) $document->meta;
        $meta['treasury'] = [
            'active_reconciliation_count' => $activeReconciliations->count(),
            'latest_reconciliation_id' => optional($activeReconciliations->sortByDesc('id')->first())->id,
            'latest_reconciled_at' => optional(optional($activeReconciliations->sortByDesc('id')->first())->reconciled_at)->toDateTimeString(),
            'reconciled_amount' => $this->normalizeAmount((string) $activeReconciliations->sum('matched_amount')),
            'status' => $activeReconciliations->isEmpty() ? 'unreconciled' : 'reconciled',
        ];

        $document->meta = $meta;
        $document->save();
    }

    protected function documentAmount(FinanceDocument $document): string
    {
        foreach ([$document->gross_amount, $document->net_amount, $document->open_amount] as $candidate) {
            $amount = $this->normalizeAmount((string) $candidate);
            if (bccomp($this->absoluteAmount($amount), '0.0000', 4) === 1) {
                return $amount;
            }
        }

        return '0.0000';
    }

    protected function statementBankAccountId(VasBankStatementLine $statementLine): ?int
    {
        return optional($statementLine->statementImport)->bank_account_id
            ?: data_get($statementLine->meta, 'bank_account_id');
    }

    protected function documentBankAccountId(FinanceDocument $document): ?int
    {
        return data_get($document->meta, 'payment.bank_account_id')
            ?: data_get($document->meta, 'bank_account_id');
    }

    protected function referenceMatched(VasBankStatementLine $statementLine, FinanceDocument $document): bool
    {
        $haystack = mb_strtolower(trim((string) $statementLine->description));
        if ($haystack === '') {
            return false;
        }

        $needles = collect([
            $document->document_no,
            $document->external_reference,
            data_get($document->meta, 'payment.external_reference'),
            data_get($document->meta, 'payment.reference'),
            data_get($document->meta, 'legacy_links.payment_ref_no'),
        ])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => mb_strtolower(trim($value)));

        return $needles->contains(fn (string $needle) => str_contains($haystack, $needle));
    }

    protected function dateDistanceInDays(?string $statementDate, ?string $documentDate): int
    {
        if (! $statementDate || ! $documentDate) {
            return 999;
        }

        return abs((int) Carbon::parse($statementDate)->diffInDays(Carbon::parse($documentDate), false));
    }

    protected function absoluteAmount(string $amount): string
    {
        return bccomp($amount, '0.0000', 4) === -1
            ? $this->normalizeAmount(bcmul($amount, '-1', 4))
            : $this->normalizeAmount($amount);
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
        return number_format((float) $amount, 4, '.', '');
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
