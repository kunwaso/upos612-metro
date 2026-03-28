<?php

namespace Modules\VasAccounting\Contracts;

interface TaxExportAdapterInterface
{
    public function export(string $exportType, array $payload = []): array;
}
