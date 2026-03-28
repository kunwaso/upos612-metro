<?php

namespace Modules\VasAccounting\Services\Adapters;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\VasAccounting\Contracts\EInvoiceAdapterInterface;

class SandboxEInvoiceAdapter implements EInvoiceAdapterInterface
{
    public function issue(array $payload): array
    {
        return [
            'provider' => 'sandbox',
            'provider_document_id' => 'sandbox-' . Str::uuid(),
            'document_no' => 'SBX-' . now()->format('YmdHis'),
            'serial_no' => 'AA/26E',
            'status' => 'issued',
            'issued_at' => Carbon::now()->toDateTimeString(),
            'response_payload' => [
                'message' => 'Sandbox issuance completed.',
            ],
        ];
    }

    public function cancel(array $payload): array
    {
        return [
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now()->toDateTimeString(),
            'response_payload' => [
                'message' => 'Sandbox cancellation completed.',
            ],
        ];
    }

    public function correct(array $payload): array
    {
        return [
            'status' => 'corrected',
            'provider_document_id' => 'sandbox-' . Str::uuid(),
            'document_no' => 'SBX-C-' . now()->format('YmdHis'),
            'response_payload' => [
                'message' => 'Sandbox correction completed.',
            ],
        ];
    }

    public function replace(array $payload): array
    {
        return [
            'status' => 'replaced',
            'provider_document_id' => 'sandbox-' . Str::uuid(),
            'document_no' => 'SBX-R-' . now()->format('YmdHis'),
            'response_payload' => [
                'message' => 'Sandbox replacement completed.',
            ],
        ];
    }

    public function syncStatus(array $payload): array
    {
        return [
            'status' => $payload['status'] ?? 'issued',
            'last_synced_at' => Carbon::now()->toDateTimeString(),
            'response_payload' => [
                'message' => 'Sandbox status sync completed.',
            ],
        ];
    }
}
