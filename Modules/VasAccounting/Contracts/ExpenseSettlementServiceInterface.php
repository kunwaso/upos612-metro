<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;

interface ExpenseSettlementServiceInterface
{
    public function buildCreationLinks(int $businessId, string $documentType, array $payload): array;

    public function validateCreatePayload(array $attributes, array $links): void;

    public function calculateAdvanceRequestSummary(FinanceDocument $advanceRequest, iterable $claims = [], iterable $settlements = []): array;

    public function calculateExpenseClaimSummary(
        FinanceDocument $expenseClaim,
        iterable $settlements = [],
        iterable $reimbursements = [],
        iterable $advances = []
    ): array;

    public function syncDocumentChain(FinanceDocument $document, ?ActionContext $context = null): void;
}
