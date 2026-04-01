<?php

namespace Modules\VasAccounting\Services\Treasury;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\TreasuryExceptionServiceInterface;
use Modules\VasAccounting\Contracts\TreasuryReconciliationServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryException;
use Modules\VasAccounting\Entities\VasBankStatementImport;
use Modules\VasAccounting\Entities\VasBankStatementLine;

class TreasuryExceptionService implements TreasuryExceptionServiceInterface
{
    public function __construct(protected TreasuryReconciliationServiceInterface $treasuryReconciliationService)
    {
    }

    public function refreshForStatementLine(
        VasBankStatementLine $statementLine,
        int $businessId,
        ?ActionContext $context = null
    ): FinanceTreasuryException {
        return DB::transaction(function () use ($statementLine, $businessId, $context) {
            $statementLine = VasBankStatementLine::query()
                ->with(['financeReconciliations.document', 'statementImport.bankAccount'])
                ->lockForUpdate()
                ->where('business_id', $businessId)
                ->findOrFail($statementLine->id);

            $currentException = FinanceTreasuryException::query()
                ->where('statement_line_id', $statementLine->id)
                ->first();

            $activeReconciliation = $statementLine->financeReconciliations
                ->where('status', 'active')
                ->sortByDesc('id')
                ->first();

            if ($activeReconciliation) {
                $exception = FinanceTreasuryException::query()->updateOrCreate(
                    ['statement_line_id' => $statementLine->id],
                    [
                        'business_id' => $businessId,
                        'recommended_document_id' => $activeReconciliation->document_id,
                        'reconciliation_id' => $activeReconciliation->id,
                        'status' => 'resolved',
                        'severity' => 'info',
                        'exception_code' => 'reconciled',
                        'top_match_score' => (string) $activeReconciliation->match_confidence,
                        'message' => 'Statement line has an active treasury reconciliation.',
                        'reviewed_by' => $context?->userId(),
                        'reviewed_at' => now(),
                        'meta' => [
                            'document_no' => optional($activeReconciliation->document)->document_no,
                            'matched_amount' => (string) $activeReconciliation->matched_amount,
                        ],
                    ]
                );

                $this->recordAudit($statementLine, $context, $currentException, $exception);

                return $exception->fresh(['statementLine', 'recommendedDocument', 'reconciliation']);
            }

            if ($statementLine->match_status === 'ignored') {
                $exception = FinanceTreasuryException::query()->updateOrCreate(
                    ['statement_line_id' => $statementLine->id],
                    [
                        'business_id' => $businessId,
                        'recommended_document_id' => null,
                        'reconciliation_id' => null,
                        'status' => 'ignored',
                        'severity' => 'info',
                        'exception_code' => 'ignored',
                        'top_match_score' => '0.0000',
                        'message' => 'Statement line was explicitly ignored.',
                        'reviewed_by' => $context?->userId(),
                        'reviewed_at' => now(),
                        'meta' => [
                            'notes' => data_get($statementLine->meta, 'reconciliation_notes'),
                        ],
                    ]
                );

                $this->recordAudit($statementLine, $context, $currentException, $exception);

                return $exception->fresh(['statementLine', 'recommendedDocument', 'reconciliation']);
            }

            $candidates = $this->treasuryReconciliationService->suggestCandidates(
                $statementLine,
                $businessId,
                (int) config('vasaccounting.treasury_reconciliation.default_candidate_limit', 5)
            );
            $topCandidate = $candidates[0] ?? null;
            $suggestThreshold = (float) config('vasaccounting.treasury_reconciliation.suggest_threshold', 70);
            $exception = FinanceTreasuryException::query()->updateOrCreate(
                ['statement_line_id' => $statementLine->id],
                [
                    'business_id' => $businessId,
                    'recommended_document_id' => data_get($topCandidate, 'document_id'),
                    'reconciliation_id' => null,
                    'status' => $topCandidate && (float) $topCandidate['score'] >= $suggestThreshold ? 'suggested' : 'open',
                    'severity' => $topCandidate ? 'warning' : 'critical',
                    'exception_code' => $topCandidate ? 'candidate_review_required' : 'no_candidate_found',
                    'top_match_score' => data_get($topCandidate, 'score', '0.0000'),
                    'message' => $topCandidate
                        ? 'Statement line requires review before reconciliation.'
                        : 'No suitable finance document candidate was found for this statement line.',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'meta' => [
                        'candidate_count' => count($candidates),
                        'candidate_document_ids' => array_values(array_filter(array_map(
                            fn (array $candidate) => $candidate['document_id'] ?? null,
                            array_slice($candidates, 0, 3)
                        ))),
                        'top_candidate' => $topCandidate,
                    ],
                ]
            );

            $this->recordAudit($statementLine, $context, $currentException, $exception);

            return $exception->fresh(['statementLine', 'recommendedDocument', 'reconciliation']);
        });
    }

    public function refreshForImport(
        VasBankStatementImport $statementImport,
        int $businessId,
        ?ActionContext $context = null
    ): void {
        $statementImport->loadMissing('lines');

        foreach ($statementImport->lines as $line) {
            $this->refreshForStatementLine($line, $businessId, $context);
        }
    }

    public function queueSummary(int $businessId, ?int $businessLocationId = null): array
    {
        $baseQuery = $this->exceptionQuery($businessId, $businessLocationId);

        return [
            'open' => (clone $baseQuery)->where('status', 'open')->count(),
            'suggested' => (clone $baseQuery)->where('status', 'suggested')->count(),
            'ignored' => (clone $baseQuery)->where('status', 'ignored')->count(),
            'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
        ];
    }

    public function queue(int $businessId, int $limit = 20, ?int $businessLocationId = null): array
    {
        return $this->exceptionQuery($businessId, $businessLocationId)
            ->with(['statementLine.statementImport.bankAccount', 'recommendedDocument'])
            ->whereIn('status', ['open', 'suggested'])
            ->orderByRaw("FIELD(status, 'open', 'suggested')")
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(function (FinanceTreasuryException $exception) {
                return [
                    'id' => $exception->id,
                    'status' => $exception->status,
                    'severity' => $exception->severity,
                    'exception_code' => $exception->exception_code,
                    'top_match_score' => (string) $exception->top_match_score,
                    'message' => $exception->message,
                    'statement_line_id' => $exception->statement_line_id,
                    'statement_date' => optional(optional($exception->statementLine)->transaction_date)->toDateString(),
                    'statement_description' => optional($exception->statementLine)->description,
                    'statement_amount' => (string) optional($exception->statementLine)->amount,
                    'bank_account_code' => optional(optional(optional($exception->statementLine)->statementImport)->bankAccount)->account_code,
                    'bank_account_name' => optional(optional(optional($exception->statementLine)->statementImport)->bankAccount)->bank_name,
                    'business_location_id' => optional(optional(optional($exception->statementLine)->statementImport)->bankAccount)->business_location_id,
                    'recommended_document_id' => $exception->recommended_document_id,
                    'recommended_document_no' => optional($exception->recommendedDocument)->document_no,
                    'recommended_document_type' => optional($exception->recommendedDocument)->document_type,
                    'candidate_count' => (int) data_get($exception->meta, 'candidate_count', 0),
                    'top_candidate' => data_get($exception->meta, 'top_candidate'),
                ];
            })
            ->values()
            ->all();
    }

    protected function exceptionQuery(int $businessId, ?int $businessLocationId = null): Builder
    {
        return FinanceTreasuryException::query()
            ->where('business_id', $businessId)
            ->when($businessLocationId, function (Builder $query) use ($businessLocationId) {
                $query->whereHas('statementLine.statementImport.bankAccount', function (Builder $bankAccountQuery) use ($businessLocationId) {
                    $bankAccountQuery->where('business_location_id', $businessLocationId);
                });
            });
    }

    protected function recordAudit(
        VasBankStatementLine $statementLine,
        ?ActionContext $context,
        ?FinanceTreasuryException $before,
        FinanceTreasuryException $after
    ): void {
        FinanceAuditEvent::query()->create([
            'business_id' => $statementLine->business_id,
            'document_id' => data_get($after, 'recommended_document_id'),
            'event_type' => 'treasury.exception_refreshed',
            'actor_id' => $context?->userId(),
            'reason' => $context?->reason(),
            'request_id' => $context?->requestId(),
            'ip_address' => $context?->ipAddress(),
            'user_agent' => $context?->userAgent(),
            'before_state' => $before ? [
                'status' => $before->status,
                'exception_code' => $before->exception_code,
                'recommended_document_id' => $before->recommended_document_id,
            ] : null,
            'after_state' => [
                'statement_line_id' => $statementLine->id,
                'status' => $after->status,
                'exception_code' => $after->exception_code,
                'recommended_document_id' => $after->recommended_document_id,
                'top_match_score' => (string) $after->top_match_score,
            ],
            'meta' => [
                'treasury_exception_id' => $after->id,
            ],
            'acted_at' => now(),
        ]);
    }
}
