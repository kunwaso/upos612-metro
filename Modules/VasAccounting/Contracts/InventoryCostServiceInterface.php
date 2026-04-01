<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceAccountingEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;

interface InventoryCostServiceInterface
{
    public function resolveDocumentProfile(FinanceDocument $document): ?array;

    public function buildMovementPlans(FinanceDocument $document, array $poolSnapshots = []): array;

    public function syncPostedDocument(FinanceDocument $document, FinanceAccountingEvent $event, ActionContext $context): void;

    public function reverseDocument(FinanceDocument $document, FinanceAccountingEvent $reversalEvent, ActionContext $context): void;
}
