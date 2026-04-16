<?php

namespace Modules\VasAccounting\Services\Adapters;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\VasAccounting\Contracts\EInvoiceAdapterInterface;
use RuntimeException;

class VnptEInvoiceAdapter implements EInvoiceAdapterInterface
{
    public function issue(array $payload, array $trace = []): array
    {
        $this->assertCredentials($trace);

        return $this->buildResponse('issued', $payload, $trace, [
            'document_no' => 'VNPT-' . now()->format('YmdHis'),
            'serial_no' => 'VNPT/26E',
            'issued_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    public function cancel(array $payload, array $trace = []): array
    {
        $this->assertCredentials($trace);

        return $this->buildResponse('cancelled', $payload, $trace, [
            'cancelled_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    public function correct(array $payload, array $trace = []): array
    {
        $this->assertCredentials($trace);

        return $this->buildResponse('corrected', $payload, $trace, [
            'document_no' => 'VNPT-C-' . now()->format('YmdHis'),
        ]);
    }

    public function replace(array $payload, array $trace = []): array
    {
        $this->assertCredentials($trace);

        return $this->buildResponse('replaced', $payload, $trace, [
            'document_no' => 'VNPT-R-' . now()->format('YmdHis'),
        ]);
    }

    public function syncStatus(array $payload, array $trace = []): array
    {
        $this->assertCredentials($trace);

        return $this->buildResponse((string) ($payload['status'] ?? 'issued'), $payload, $trace, [
            'last_synced_at' => Carbon::now()->toDateTimeString(),
        ]);
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

    protected function buildResponse(string $status, array $payload, array $trace, array $extra = []): array
    {
        $traceId = (string) ($trace['trace_id'] ?? Str::uuid());
        $idempotencyKey = (string) ($trace['idempotency_key'] ?? Str::uuid());
        $providerDocumentId = (string) ($payload['provider_document_id'] ?? ('vnpt-' . Str::uuid()));
        $payloadHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return array_merge([
            'provider' => 'vnpt',
            'status' => $status,
            'provider_document_id' => $providerDocumentId,
            'idempotency_key' => $idempotencyKey,
            'trace_id' => $traceId,
            'signed_payload_hash' => $payloadHash,
            'retry_classification' => 'not_required',
            'response_payload' => [
                'message' => 'VNPT adapter simulated response.',
                'trace_id' => $traceId,
            ],
        ], $extra);
    }

    protected function assertCredentials(array $trace): void
    {
        $providerConfig = (array) ($trace['provider_config'] ?? []);
        $apiBaseUrl = (string) ($providerConfig['vnpt_api_base_url'] ?? '');
        $clientId = (string) ($providerConfig['vnpt_client_id'] ?? '');
        $clientSecret = (string) ($providerConfig['vnpt_client_secret'] ?? '');

        if ($apiBaseUrl === '' || $clientId === '' || $clientSecret === '') {
            throw new RuntimeException('VNPT e-invoice adapter is missing required credentials.');
        }
    }
}

