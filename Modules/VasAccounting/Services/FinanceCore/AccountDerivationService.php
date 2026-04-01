<?php

namespace Modules\VasAccounting\Services\FinanceCore;

use Modules\VasAccounting\Application\DTOs\AccountDerivationInput;
use Modules\VasAccounting\Application\DTOs\DerivedAccountSet;
use Modules\VasAccounting\Contracts\AccountDerivationServiceInterface;

class AccountDerivationService implements AccountDerivationServiceInterface
{
    public function derive(AccountDerivationInput $input): DerivedAccountSet
    {
        $ruleLine = $input->ruleLine;
        $documentLine = $input->documentLine;
        $source = $ruleLine?->account_source ?: 'document_line_account';
        $warnings = [];

        $accountId = match ($source) {
            'fixed' => $ruleLine?->fixed_account_id,
            'document_line_account' => $documentLine->account_hint_id,
            'document_line_debit' => $documentLine->debit_account_id,
            'document_line_credit' => $documentLine->credit_account_id,
            'document_line_tax' => $documentLine->tax_account_id,
            default => null,
        };

        if (! $accountId) {
            $warnings[] = sprintf(
                'No account could be derived for document line [%s] using source [%s].',
                (string) ($documentLine->line_no ?? '?'),
                $source
            );
        }

        return new DerivedAccountSet($accountId ? (int) $accountId : null, $warnings);
    }
}
