<?php

namespace Modules\VasAccounting\Contracts;

interface BankStatementImportAdapterInterface
{
    public function import(array $payload): array;

    public function normalize(array $payload): array;
}
