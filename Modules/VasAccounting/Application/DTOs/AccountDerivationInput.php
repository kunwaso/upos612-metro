<?php

namespace Modules\VasAccounting\Application\DTOs;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinancePostingRuleLine;

class AccountDerivationInput
{
    public function __construct(
        public FinanceDocument $document,
        public FinanceDocumentLine $documentLine,
        public ?FinancePostingRuleLine $ruleLine,
        public string $entrySide
    ) {
    }
}
