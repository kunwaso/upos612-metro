<?php

namespace Modules\VasAccounting\Services\Adapters;

use Modules\VasAccounting\Contracts\TaxExportAdapterInterface;

class LocalTaxExportAdapter implements TaxExportAdapterInterface
{
    public function export(string $exportType, array $payload = []): array
    {
        return [
            'provider' => 'local',
            'export_type' => $exportType,
            'generated_at' => now()->toDateTimeString(),
            'payload' => $payload,
        ];
    }
}
