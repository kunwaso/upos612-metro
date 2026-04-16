<?php

namespace Modules\VasAccounting\Services\Adapters;

use Illuminate\Support\Str;
use Modules\VasAccounting\Contracts\TaxExportAdapterInterface;
use RuntimeException;

class VnptTaxExportAdapter implements TaxExportAdapterInterface
{
    public function export(string $exportType, array $payload = [], array $trace = []): array
    {
        $this->assertCredentials($trace);

        $traceId = (string) ($trace['trace_id'] ?? Str::uuid());
        $idempotencyKey = (string) ($trace['idempotency_key'] ?? Str::uuid());
        $submissionId = 'vnpt-tax-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        $payloadHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return [
            'provider' => 'vnpt',
            'export_type' => $exportType,
            'status' => 'submitted',
            'submission_id' => $submissionId,
            'generated_at' => now()->toDateTimeString(),
            'idempotency_key' => $idempotencyKey,
            'trace_id' => $traceId,
            'signed_payload_hash' => $payloadHash,
            'retry_classification' => 'not_required',
            'summary' => [
                'sales_tax_total' => data_get($payload, 'summary.sales_tax_total', 0),
                'purchase_tax_total' => data_get($payload, 'summary.purchase_tax_total', 0),
            ],
            'response_payload' => [
                'message' => 'VNPT tax export simulated response.',
                'trace_id' => $traceId,
                'submission_id' => $submissionId,
            ],
        ];
    }

    public function capabilities(): array
    {
        return [
            'supports_idempotency' => true,
            'supports_signing' => true,
            'supports_submit' => true,
            'supports_status_check' => true,
            'supports_retry_classification' => true,
        ];
    }

    protected function assertCredentials(array $trace): void
    {
        $providerConfig = (array) ($trace['provider_config'] ?? []);
        $apiBaseUrl = (string) ($providerConfig['vnpt_api_base_url'] ?? '');
        $clientId = (string) ($providerConfig['vnpt_client_id'] ?? '');
        $clientSecret = (string) ($providerConfig['vnpt_client_secret'] ?? '');
        $taxUsername = (string) ($providerConfig['vnpt_tax_username'] ?? '');
        $taxPassword = (string) ($providerConfig['vnpt_tax_password'] ?? '');

        if ($apiBaseUrl === '' || $clientId === '' || $clientSecret === '' || $taxUsername === '' || $taxPassword === '') {
            throw new RuntimeException('VNPT tax export adapter is missing required credentials.');
        }
    }
}

