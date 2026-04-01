<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\AccountDerivationInput;
use Modules\VasAccounting\Application\DTOs\DerivedAccountSet;

interface AccountDerivationServiceInterface
{
    public function derive(AccountDerivationInput $input): DerivedAccountSet;
}
