<?php

namespace Modules\VasAccounting\Services\WorkflowApproval;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use RuntimeException;

class MakerCheckerGuard
{
    public function assertCanApprove(FinanceDocument $document, int $approverUserId): void
    {
        if (! $this->makerCheckerEnabled($document)) {
            return;
        }

        if ((int) $document->submitted_by === $approverUserId && $approverUserId > 0) {
            throw new RuntimeException('Maker-checker control prevents the document submitter from approving the same finance document.');
        }
    }

    protected function makerCheckerEnabled(FinanceDocument $document): bool
    {
        if ($document->document_family === 'expense_management') {
            $expenseRule = data_get(
                config('vasaccounting.approval_defaults.expense_document_policies', []),
                $document->document_type . '.maker_checker'
            );

            if (! is_null($expenseRule)) {
                return (bool) $expenseRule;
            }
        }

        return (bool) config('vasaccounting.approval_defaults.finance_document_defaults.maker_checker', true);
    }
}
