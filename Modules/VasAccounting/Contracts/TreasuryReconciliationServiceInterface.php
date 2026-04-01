<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryReconciliation;
use Modules\VasAccounting\Entities\VasBankStatementLine;

interface TreasuryReconciliationServiceInterface
{
    public function resolveCandidateDocumentTypes(string $statementAmount): array;

    public function scoreCandidate(VasBankStatementLine $statementLine, FinanceDocument $document): array;

    public function suggestCandidates(VasBankStatementLine $statementLine, int $businessId, ?int $limit = null): array;

    public function reconcile(
        VasBankStatementLine $statementLine,
        FinanceDocument $document,
        ActionContext $context,
        ?int $openItemId = null
    ): FinanceTreasuryReconciliation;

    public function reverse(FinanceTreasuryReconciliation $reconciliation, ActionContext $context): FinanceTreasuryReconciliation;
}
