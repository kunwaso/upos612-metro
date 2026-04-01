<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryException;
use Modules\VasAccounting\Entities\VasBankStatementImport;
use Modules\VasAccounting\Entities\VasBankStatementLine;

interface TreasuryExceptionServiceInterface
{
    public function refreshForStatementLine(
        VasBankStatementLine $statementLine,
        int $businessId,
        ?ActionContext $context = null
    ): FinanceTreasuryException;

    public function refreshForImport(
        VasBankStatementImport $statementImport,
        int $businessId,
        ?ActionContext $context = null
    ): void;

    public function queueSummary(int $businessId, ?int $businessLocationId = null): array;

    public function queue(int $businessId, int $limit = 20, ?int $businessLocationId = null): array;
}
