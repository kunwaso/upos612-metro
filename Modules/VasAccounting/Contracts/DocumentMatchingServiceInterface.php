<?php

namespace Modules\VasAccounting\Contracts;

use Illuminate\Support\Collection;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchRun;

interface DocumentMatchingServiceInterface
{
    public function evaluateSupplierInvoice(FinanceDocument $document, Collection $parentDocuments, array $options = []): array;

    public function matchSupplierInvoice(FinanceDocument $document, ActionContext $context): FinanceMatchRun;

    public function latestRunForDocument(FinanceDocument $document): ?FinanceMatchRun;
}
