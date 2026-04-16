<?php

namespace Modules\VasAccounting\Services\Adapters;

use Modules\VasAccounting\Contracts\TaxExportAdapterInterface;

class LocalTaxExportAdapter implements TaxExportAdapterInterface
{
    public function export(string $exportType, array $payload = [], array $trace = []): array
    {
        return [
            'provider' => 'local',
            'export_type' => $exportType,
            'generated_at' => now()->toDateTimeString(),
            'payload' => $payload,
            'trace' => $trace,
        ];
    }

    public function capabilities(): array
    {
        return [
            'supports_idempotency' => false,
            'supports_signing' => false,
            'supports_submit' => false,
            'supports_status_check' => false,
            'supports_retry_classification' => false,
        ];
    }
}
