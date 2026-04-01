<?php

namespace Modules\VasAccounting\Application\DTOs;

class DerivedAccountSet
{
    /**
     * @param string[] $warnings
     */
    public function __construct(
        public ?int $accountId,
        public array $warnings = []
    ) {
    }
}
