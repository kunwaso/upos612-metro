<?php

namespace Modules\VasAccounting\Services\Expense;

use Illuminate\Support\Collection;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\ExpenseSettlementServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use RuntimeException;

class ExpenseSettlementService implements ExpenseSettlementServiceInterface
{
    public function buildCreationLinks(int $businessId, string $documentType, array $payload): array
    {
        $advanceRequestId = in_array($documentType, ['expense_claim', 'advance_settlement'], true)
            ? (int) ($payload['advance_request_id'] ?? 0)
            : 0;
        $expenseClaimId = in_array($documentType, ['advance_settlement', 'reimbursement_voucher'], true)
            ? (int) ($payload['expense_claim_id'] ?? 0)
            : 0;

        if ($documentType === 'advance_settlement' && $advanceRequestId <= 0 && $expenseClaimId > 0) {
            $expenseClaim = $this->requireDocument($businessId, $expenseClaimId, 'expense_claim');
            $advanceRequestId = (int) optional(
                $expenseClaim->parentLinks()
                    ->whereHas('parentDocument', fn ($query) => $query->where('document_type', 'advance_request'))
                    ->with('parentDocument:id')
                    ->first()
            )->parent_document_id;
        }

        $links = [];
        if ($advanceRequestId > 0) {
            $this->requireDocument($businessId, $advanceRequestId, 'advance_request');
            $links[] = [
                'parent_document_id' => $advanceRequestId,
                'link_type' => match ($documentType) {
                    'expense_claim' => 'advance_request_claim',
                    'advance_settlement' => 'advance_request_settlement',
                    default => 'related',
                },
            ];
        }

        if ($expenseClaimId > 0) {
            $this->requireDocument($businessId, $expenseClaimId, 'expense_claim');
            $links[] = [
                'parent_document_id' => $expenseClaimId,
                'link_type' => match ($documentType) {
                    'advance_settlement' => 'expense_claim_settlement',
                    'reimbursement_voucher' => 'expense_claim_reimbursement',
                    default => 'related',
                },
            ];
        }

        return collect($links)
            ->unique(fn (array $link) => ($link['parent_document_id'] ?? 0) . '|' . ($link['link_type'] ?? 'related'))
            ->values()
            ->all();
    }

    public function validateCreatePayload(array $attributes, array $links): void
    {
        if (($attributes['document_family'] ?? null) !== 'expense_management') {
            return;
        }

        $businessId = (int) ($attributes['business_id'] ?? 0);
        $documentType = (string) ($attributes['document_type'] ?? '');
        $grossAmount = (float) ($attributes['gross_amount'] ?? 0);
        $parents = collect($links)
            ->pluck('parent_document_id')
            ->filter()
            ->unique()
            ->map(fn ($documentId) => FinanceDocument::query()->findOrFail((int) $documentId))
            ->values();

        $advanceRequest = $parents->firstWhere('document_type', 'advance_request');
        $expenseClaim = $parents->firstWhere('document_type', 'expense_claim');

        if ($documentType === 'advance_settlement' && ! $advanceRequest instanceof FinanceDocument) {
            throw new RuntimeException('Advance settlements must link to an advance request.');
        }

        if ($documentType === 'reimbursement_voucher' && ! $expenseClaim instanceof FinanceDocument) {
            throw new RuntimeException('Reimbursement vouchers must link to an expense claim.');
        }

        if ($advanceRequest instanceof FinanceDocument && (int) $advanceRequest->business_id !== $businessId) {
            throw new RuntimeException('Linked advance request belongs to a different business.');
        }

        if ($expenseClaim instanceof FinanceDocument && (int) $expenseClaim->business_id !== $businessId) {
            throw new RuntimeException('Linked expense claim belongs to a different business.');
        }

        $this->assertMatchingClaimants($advanceRequest, $expenseClaim);

        if ($documentType === 'advance_settlement' && $advanceRequest instanceof FinanceDocument) {
            $availableAdvanceAmount = (float) $this->remainingAdvanceAmount($advanceRequest);
            if ($grossAmount > ($availableAdvanceAmount + 0.0001)) {
                throw new RuntimeException(sprintf(
                    'Advance settlement amount [%s] exceeds the remaining advance amount [%s].',
                    $this->normalizeAmount($grossAmount),
                    $this->normalizeAmount($availableAdvanceAmount)
                ));
            }

            if ($expenseClaim instanceof FinanceDocument) {
                $outstandingClaimAmount = (float) $this->outstandingClaimAmount($expenseClaim);
                if ($grossAmount > ($outstandingClaimAmount + 0.0001)) {
                    throw new RuntimeException(sprintf(
                        'Advance settlement amount [%s] exceeds the remaining claim amount [%s].',
                        $this->normalizeAmount($grossAmount),
                        $this->normalizeAmount($outstandingClaimAmount)
                    ));
                }

                $claimAdvance = $this->linkedParents($expenseClaim, 'advance_request')->first();
                if ($claimAdvance instanceof FinanceDocument && $claimAdvance->id !== $advanceRequest->id) {
                    throw new RuntimeException('Selected expense claim is linked to a different advance request.');
                }
            }
        }

        if ($documentType === 'reimbursement_voucher' && $expenseClaim instanceof FinanceDocument) {
            $outstandingClaimAmount = (float) $this->outstandingClaimAmount($expenseClaim);
            if ($grossAmount > ($outstandingClaimAmount + 0.0001)) {
                throw new RuntimeException(sprintf(
                    'Reimbursement amount [%s] exceeds the remaining claim amount [%s].',
                    $this->normalizeAmount($grossAmount),
                    $this->normalizeAmount($outstandingClaimAmount)
                ));
            }
        }
    }

    public function calculateAdvanceRequestSummary(FinanceDocument $advanceRequest, iterable $claims = [], iterable $settlements = []): array
    {
        $claims = $this->asCollection($claims)->filter(fn (FinanceDocument $document) => $this->isActiveDocument($document));
        $settlements = $this->asCollection($settlements)->filter(fn (FinanceDocument $document) => $this->isActiveDocument($document));

        $claimedAmount = $claims->sum(fn (FinanceDocument $document) => (float) $document->gross_amount);
        $postedSettlementAmount = $settlements
            ->filter(fn (FinanceDocument $document) => $this->isPostedDocument($document))
            ->sum(fn (FinanceDocument $document) => (float) $document->gross_amount);
        $pendingSettlementAmount = $settlements
            ->reject(fn (FinanceDocument $document) => $this->isPostedDocument($document))
            ->sum(fn (FinanceDocument $document) => (float) $document->gross_amount);

        $remainingAdvanceAmount = max(0, (float) $advanceRequest->gross_amount - $postedSettlementAmount);
        $settlementStatus = 'unsettled';
        if ($remainingAdvanceAmount < 0.0001 && (float) $advanceRequest->gross_amount > 0) {
            $settlementStatus = 'settled';
        } elseif ($postedSettlementAmount > 0) {
            $settlementStatus = 'partially_settled';
        }

        return [
            'linked_claim_count' => $claims->count(),
            'linked_claim_amount' => $this->normalizeAmount($claimedAmount),
            'posted_settlement_amount' => $this->normalizeAmount($postedSettlementAmount),
            'pending_settlement_amount' => $this->normalizeAmount($pendingSettlementAmount),
            'remaining_advance_amount' => $this->normalizeAmount($remainingAdvanceAmount),
            'settlement_status' => $settlementStatus,
        ];
    }

    public function calculateExpenseClaimSummary(
        FinanceDocument $expenseClaim,
        iterable $settlements = [],
        iterable $reimbursements = [],
        iterable $advances = []
    ): array {
        $settlements = $this->asCollection($settlements)->filter(fn (FinanceDocument $document) => $this->isActiveDocument($document));
        $reimbursements = $this->asCollection($reimbursements)->filter(fn (FinanceDocument $document) => $this->isActiveDocument($document));
        $advances = $this->asCollection($advances)->filter(fn (FinanceDocument $document) => $this->isActiveDocument($document));

        $postedSettlementAmount = $settlements
            ->filter(fn (FinanceDocument $document) => $this->isPostedDocument($document))
            ->sum(fn (FinanceDocument $document) => (float) $document->gross_amount);
        $postedReimbursementAmount = $reimbursements
            ->filter(fn (FinanceDocument $document) => $this->isPostedDocument($document))
            ->sum(fn (FinanceDocument $document) => (float) $document->gross_amount);
        $pendingSettlementAmount = $settlements
            ->reject(fn (FinanceDocument $document) => $this->isPostedDocument($document))
            ->sum(fn (FinanceDocument $document) => (float) $document->gross_amount);
        $pendingReimbursementAmount = $reimbursements
            ->reject(fn (FinanceDocument $document) => $this->isPostedDocument($document))
            ->sum(fn (FinanceDocument $document) => (float) $document->gross_amount);

        $resolvedAmount = $postedSettlementAmount + $postedReimbursementAmount;
        $outstandingAmount = max(0, (float) $expenseClaim->gross_amount - $resolvedAmount);

        $settlementStatus = 'open';
        if ($outstandingAmount < 0.0001 && (float) $expenseClaim->gross_amount > 0) {
            $settlementStatus = 'settled';
        } elseif ($resolvedAmount > 0) {
            $settlementStatus = 'partially_settled';
        }

        return [
            'linked_advance_document_no' => optional($advances->first())->document_no,
            'linked_advance_count' => $advances->count(),
            'posted_settlement_amount' => $this->normalizeAmount($postedSettlementAmount),
            'pending_settlement_amount' => $this->normalizeAmount($pendingSettlementAmount),
            'posted_reimbursement_amount' => $this->normalizeAmount($postedReimbursementAmount),
            'pending_reimbursement_amount' => $this->normalizeAmount($pendingReimbursementAmount),
            'outstanding_amount' => $this->normalizeAmount($outstandingAmount),
            'settlement_status' => $settlementStatus,
        ];
    }

    public function syncDocumentChain(FinanceDocument $document, ?ActionContext $context = null): void
    {
        $document = FinanceDocument::query()
            ->with(['parentLinks.parentDocument', 'childLinks.childDocument'])
            ->findOrFail($document->id);

        if ($document->document_family !== 'expense_management') {
            return;
        }

        if ($document->document_type === 'advance_request') {
            $this->syncAdvanceRequest($document, $context);

            return;
        }

        if ($document->document_type === 'expense_claim') {
            $this->syncExpenseClaim($document, $context);
            $this->linkedParents($document, 'advance_request')->each(fn (FinanceDocument $advanceRequest) => $this->syncAdvanceRequest($advanceRequest, $context));

            return;
        }

        if ($document->document_type === 'advance_settlement') {
            $this->syncAdvanceSettlement($document, $context);
            $this->linkedParents($document, 'expense_claim')->each(fn (FinanceDocument $expenseClaim) => $this->syncExpenseClaim($expenseClaim, $context));
            $this->linkedParents($document, 'advance_request')->each(fn (FinanceDocument $advanceRequest) => $this->syncAdvanceRequest($advanceRequest, $context));

            return;
        }

        if ($document->document_type === 'reimbursement_voucher') {
            $this->syncReimbursementVoucher($document, $context);
            $this->linkedParents($document, 'expense_claim')->each(function (FinanceDocument $expenseClaim) use ($context) {
                $this->syncExpenseClaim($expenseClaim, $context);
                $this->linkedParents($expenseClaim, 'advance_request')->each(fn (FinanceDocument $advanceRequest) => $this->syncAdvanceRequest($advanceRequest, $context));
            });
        }
    }

    protected function syncAdvanceRequest(FinanceDocument $advanceRequest, ?ActionContext $context): void
    {
        $claims = $this->linkedChildren($advanceRequest, 'expense_claim');
        $settlements = $this->linkedChildren($advanceRequest, 'advance_settlement');
        $summary = $this->calculateAdvanceRequestSummary($advanceRequest, $claims, $settlements);

        $this->applySummary(
            $advanceRequest,
            'expense_chain',
            $summary,
            ['open_amount' => $summary['remaining_advance_amount']],
            $context,
            'expense.advance_summary_synced'
        );
    }

    protected function syncExpenseClaim(FinanceDocument $expenseClaim, ?ActionContext $context): void
    {
        $summary = $this->calculateExpenseClaimSummary(
            $expenseClaim,
            $this->linkedChildren($expenseClaim, 'advance_settlement'),
            $this->linkedChildren($expenseClaim, 'reimbursement_voucher'),
            $this->linkedParents($expenseClaim, 'advance_request')
        );

        $this->applySummary(
            $expenseClaim,
            'expense_chain',
            $summary,
            ['open_amount' => $summary['outstanding_amount']],
            $context,
            'expense.claim_summary_synced'
        );
    }

    protected function syncAdvanceSettlement(FinanceDocument $settlement, ?ActionContext $context): void
    {
        $summary = [
            'linked_advance_document_no' => optional($this->linkedParents($settlement, 'advance_request')->first())->document_no,
            'linked_claim_document_no' => optional($this->linkedParents($settlement, 'expense_claim')->first())->document_no,
            'settlement_amount' => $this->normalizeAmount($settlement->gross_amount),
        ];

        $this->applySummary($settlement, 'expense_chain', $summary, ['open_amount' => '0.0000'], $context, 'expense.settlement_summary_synced');
    }

    protected function syncReimbursementVoucher(FinanceDocument $reimbursement, ?ActionContext $context): void
    {
        $summary = [
            'linked_claim_document_no' => optional($this->linkedParents($reimbursement, 'expense_claim')->first())->document_no,
            'reimbursement_amount' => $this->normalizeAmount($reimbursement->gross_amount),
        ];

        $this->applySummary($reimbursement, 'expense_chain', $summary, ['open_amount' => '0.0000'], $context, 'expense.reimbursement_summary_synced');
    }

    protected function applySummary(
        FinanceDocument $document,
        string $metaKey,
        array $summary,
        array $updates,
        ?ActionContext $context,
        string $auditEventType
    ): void {
        $before = $document->toArray();
        $meta = array_merge((array) $document->meta, [$metaKey => $summary]);
        $changed = $meta !== (array) $document->meta;

        foreach ($updates as $field => $value) {
            if ((string) $document->{$field} !== (string) $value) {
                $document->{$field} = $value;
                $changed = true;
            }
        }

        $document->meta = $meta;

        if (! $changed) {
            return;
        }

        $document->save();
        $this->recordAudit($document, $auditEventType, $context, $before, $document->fresh()->toArray());
    }

    protected function remainingAdvanceAmount(FinanceDocument $advanceRequest): string
    {
        return $this->calculateAdvanceRequestSummary(
            $advanceRequest,
            $this->linkedChildren($advanceRequest, 'expense_claim'),
            $this->linkedChildren($advanceRequest, 'advance_settlement')
        )['remaining_advance_amount'];
    }

    protected function outstandingClaimAmount(FinanceDocument $expenseClaim): string
    {
        return $this->calculateExpenseClaimSummary(
            $expenseClaim,
            $this->linkedChildren($expenseClaim, 'advance_settlement'),
            $this->linkedChildren($expenseClaim, 'reimbursement_voucher'),
            $this->linkedParents($expenseClaim, 'advance_request')
        )['outstanding_amount'];
    }

    protected function linkedParents(FinanceDocument $document, string $documentType): Collection
    {
        return FinanceDocument::query()
            ->whereHas('childLinks', function ($query) use ($document, $documentType) {
                $query->where('child_document_id', $document->id)
                    ->whereHas('parentDocument', fn ($parentQuery) => $parentQuery->where('document_type', $documentType));
            })
            ->get();
    }

    protected function linkedChildren(FinanceDocument $document, string $documentType): Collection
    {
        return FinanceDocument::query()
            ->whereHas('parentLinks', function ($query) use ($document, $documentType) {
                $query->where('parent_document_id', $document->id)
                    ->whereHas('childDocument', fn ($childQuery) => $childQuery->where('document_type', $documentType));
            })
            ->get();
    }

    protected function assertMatchingClaimants(?FinanceDocument $advanceRequest, ?FinanceDocument $expenseClaim): void
    {
        if (! $advanceRequest || ! $expenseClaim) {
            return;
        }

        $advanceClaimant = data_get($advanceRequest->meta, 'expense.claimant_user_id');
        $claimClaimant = data_get($expenseClaim->meta, 'expense.claimant_user_id');

        if ($advanceClaimant && $claimClaimant && (int) $advanceClaimant !== (int) $claimClaimant) {
            throw new RuntimeException('Linked advance request and expense claim must belong to the same claimant.');
        }
    }

    protected function requireDocument(int $businessId, int $documentId, string $documentType): FinanceDocument
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', $documentType)
            ->findOrFail($documentId);
    }

    protected function isActiveDocument(FinanceDocument $document): bool
    {
        return ! in_array($document->workflow_status, ['cancelled', 'reversed'], true);
    }

    protected function isPostedDocument(FinanceDocument $document): bool
    {
        return in_array($document->workflow_status, ['posted', 'closed'], true)
            || in_array($document->accounting_status, ['posted', 'closed'], true);
    }

    protected function asCollection(iterable $documents): Collection
    {
        return $documents instanceof Collection ? $documents : collect($documents);
    }

    protected function normalizeAmount(float|string|int|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 4, '.', '');
    }

    protected function recordAudit(
        FinanceDocument $document,
        string $eventType,
        ?ActionContext $context,
        mixed $beforeState,
        mixed $afterState
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
