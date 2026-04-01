<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceOpenItem;

interface OrderToCashLifecycleServiceInterface
{
    public function calculateSalesOrderSummary(FinanceDocument $salesOrder, iterable $deliveries = [], iterable $directInvoices = []): array;

    public function calculateDeliverySummary(FinanceDocument $delivery, iterable $invoices = []): array;

    public function calculateInvoiceCollectionSummary(FinanceDocument $invoice, ?FinanceOpenItem $chargeOpenItem = null): array;

    public function syncDocumentChain(FinanceDocument $document, ActionContext $context): void;
}
