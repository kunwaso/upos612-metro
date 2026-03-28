<?php

namespace Modules\VasAccounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\VasAccounting\Entities\VasBankStatementImport;
use Modules\VasAccounting\Entities\VasEInvoiceDocument;
use Modules\VasAccounting\Entities\VasEInvoiceLog;
use Modules\VasAccounting\Entities\VasIntegrationRun;
use Modules\VasAccounting\Entities\VasIntegrationWebhook;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Jobs\RunIntegrationTaskJob;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;
use Throwable;

class IntegrationHubService
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected BankStatementImportAdapterManager $bankStatementImportAdapterManager,
        protected TaxExportAdapterManager $taxExportAdapterManager,
        protected EInvoiceAdapterManager $eInvoiceAdapterManager,
        protected EnterpriseFinanceReportUtil $enterpriseFinanceReportUtil,
        protected VasPayrollBridgeService $vasPayrollBridgeService,
        protected VasPostingService $postingService
    ) {
    }

    public function overview(int $businessId): array
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $integrationSettings = (array) $settings->integration_settings;

        return [
            'provider_groups' => [
                ['label' => 'Bank imports', 'count' => count((array) config('vasaccounting.bank_statement_import_adapters', [])), 'default' => (string) ($integrationSettings['bank_statement_provider'] ?? 'manual')],
                ['label' => 'Tax exports', 'count' => count((array) config('vasaccounting.tax_export_adapters', [])), 'default' => (string) ($integrationSettings['tax_export_provider'] ?? 'local')],
                ['label' => 'Payroll bridges', 'count' => count((array) config('vasaccounting.payroll_bridge_adapters', [])), 'default' => (string) ($integrationSettings['payroll_bridge_provider'] ?? 'essentials')],
                ['label' => 'E-invoice adapters', 'count' => count((array) config('vasaccounting.einvoice_adapters', [])), 'default' => (string) (((array) $settings->einvoice_settings)['provider'] ?? 'sandbox')],
            ],
            'metrics' => [
                ['label' => 'Queued runs', 'value' => VasIntegrationRun::query()->where('business_id', $businessId)->whereIn('status', ['queued', 'processing'])->count()],
                ['label' => 'Failed runs', 'value' => VasIntegrationRun::query()->where('business_id', $businessId)->where('status', 'failed')->count()],
                ['label' => 'Pending webhooks', 'value' => VasIntegrationWebhook::query()->where(function ($query) use ($businessId) {
                    $query->where('business_id', $businessId)->orWhereNull('business_id');
                })->whereIn('status', ['received', 'queued'])->count()],
                ['label' => 'Posting failures', 'value' => VasPostingFailure::query()->where('business_id', $businessId)->whereNull('resolved_at')->count()],
            ],
        ];
    }

    public function recentRuns(int $businessId, int $limit = 20)
    {
        return VasIntegrationRun::query()
            ->where('business_id', $businessId)
            ->latest()
            ->take($limit)
            ->get();
    }

    public function recentWebhooks(int $businessId, int $limit = 20)
    {
        return VasIntegrationWebhook::query()
            ->where(function ($query) use ($businessId) {
                $query->where('business_id', $businessId)->orWhereNull('business_id');
            })
            ->latest()
            ->take($limit)
            ->get();
    }

    public function queueRun(int $businessId, string $runType, ?string $provider, string $action, array $payload, int $userId): VasIntegrationRun
    {
        $run = VasIntegrationRun::create([
            'business_id' => $businessId,
            'run_type' => $runType,
            'provider' => $provider,
            'action' => $action,
            'status' => 'queued',
            'requested_by' => $userId,
            'payload' => $payload,
        ]);

        dispatch(new RunIntegrationTaskJob((int) $run->id));

        return $run;
    }

    public function queueFailureReplay(int $businessId, int $failureId, int $userId): VasIntegrationRun
    {
        return $this->queueRun($businessId, 'posting_failure_replay', 'vas_posting', 'replay_failure', [
            'failure_id' => $failureId,
        ], $userId);
    }

    public function processRunById(int $runId): ?VasIntegrationRun
    {
        $run = VasIntegrationRun::query()->find($runId);
        if (! $run) {
            return null;
        }

        return $this->processRun($run);
    }

    public function processRun(VasIntegrationRun $run): VasIntegrationRun
    {
        if (! in_array($run->status, ['queued', 'failed'], true)) {
            return $run;
        }

        $run->status = 'processing';
        $run->started_at = now();
        $run->error_message = null;
        $run->save();

        try {
            $response = match ($run->run_type) {
                'bank_statement_import' => $this->runBankStatementImport($run),
                'tax_export' => $this->runTaxExport($run),
                'payroll_bridge' => $this->runPayrollBridge($run),
                'einvoice_sync' => $this->runEInvoiceSync($run),
                'posting_failure_replay' => $this->runPostingFailureReplay($run),
                default => throw new RuntimeException("Unsupported integration run type [{$run->run_type}]."),
            };

            $run->status = 'completed';
            $run->response_payload = $response;
            $run->completed_at = now();
            $run->save();

            return $run->fresh();
        } catch (Throwable $exception) {
            $run->status = 'failed';
            $run->error_message = $exception->getMessage();
            $run->response_payload = ['trace' => substr($exception->getTraceAsString(), 0, 5000)];
            $run->completed_at = now();
            $run->save();

            throw $exception;
        }
    }

    public function recordWebhook(?int $businessId, string $provider, array $payload, ?string $eventKey = null, ?string $externalReference = null): VasIntegrationWebhook
    {
        $webhook = VasIntegrationWebhook::create([
            'business_id' => $businessId,
            'provider' => $provider,
            'event_key' => $eventKey,
            'external_reference' => $externalReference,
            'status' => 'received',
            'received_at' => now(),
            'payload' => $payload,
        ]);

        try {
            $response = $this->processWebhook($webhook);
            $webhook->status = 'processed';
            $webhook->processed_at = now();
            $webhook->response_payload = $response;
            $webhook->save();
        } catch (Throwable $exception) {
            $webhook->status = 'failed';
            $webhook->processed_at = now();
            $webhook->error_message = $exception->getMessage();
            $webhook->response_payload = ['trace' => substr($exception->getTraceAsString(), 0, 5000)];
            $webhook->save();

            throw $exception;
        }

        return $webhook->fresh();
    }

    public function parseStatementLinesText(string $input): Collection
    {
        $rows = collect(preg_split('/\r\n|\r|\n/', trim($input)))
            ->filter(fn ($line) => trim((string) $line) !== '')
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'statement_lines' => 'Provide at least one bank statement line.',
            ]);
        }

        return $rows->map(function (string $row, int $index) {
            $parts = array_map('trim', explode('|', $row));

            if (count($parts) < 3) {
                throw ValidationException::withMessages([
                    'statement_lines' => 'Line ' . ($index + 1) . ' must use YYYY-MM-DD|Description|Amount|Running balance(optional).',
                ]);
            }

            try {
                $transactionDate = Carbon::parse($parts[0])->toDateString();
            } catch (Throwable $exception) {
                throw ValidationException::withMessages([
                    'statement_lines' => 'Line ' . ($index + 1) . ' has an invalid date.',
                ]);
            }

            if (! is_numeric(str_replace(',', '', $parts[2]))) {
                throw ValidationException::withMessages([
                    'statement_lines' => 'Line ' . ($index + 1) . ' has an invalid amount.',
                ]);
            }

            if (isset($parts[3]) && $parts[3] !== '' && ! is_numeric(str_replace(',', '', $parts[3]))) {
                throw ValidationException::withMessages([
                    'statement_lines' => 'Line ' . ($index + 1) . ' has an invalid running balance.',
                ]);
            }

            return [
                'transaction_date' => $transactionDate,
                'description' => $parts[1],
                'amount' => (float) str_replace(',', '', $parts[2]),
                'running_balance' => isset($parts[3]) && $parts[3] !== '' ? (float) str_replace(',', '', $parts[3]) : null,
                'match_status' => 'unmatched',
                'meta' => ['raw_line' => $row],
            ];
        });
    }

    protected function runBankStatementImport(VasIntegrationRun $run): array
    {
        $payload = (array) $run->payload;
        $adapter = $this->bankStatementImportAdapterManager->resolve($run->provider ?: 'manual');
        $result = $adapter->import([
            'provider' => $run->provider ?: 'manual',
            'lines' => (array) ($payload['lines'] ?? []),
        ]);

        $statementImport = VasBankStatementImport::create([
            'business_id' => $run->business_id,
            'bank_account_id' => $payload['bank_account_id'] ?? null,
            'provider' => $run->provider ?: 'manual',
            'reference_no' => $payload['reference_no'] ?? null,
            'status' => $result['status'] ?? 'imported',
            'imported_by' => $run->requested_by,
            'imported_at' => now(),
            'meta' => ['line_count' => count((array) ($result['lines'] ?? []))],
        ]);

        foreach ((array) ($result['lines'] ?? []) as $line) {
            $statementImport->lines()->create([
                'business_id' => $run->business_id,
                'transaction_date' => $line['transaction_date'],
                'description' => $line['description'] ?? null,
                'amount' => $line['amount'] ?? 0,
                'running_balance' => $line['running_balance'] ?? null,
                'match_status' => $line['match_status'] ?? 'unmatched',
                'meta' => $line['meta'] ?? null,
            ]);
        }

        $this->refreshStatementImportStatus($statementImport->fresh('lines'));

        return [
            'statement_import_id' => (int) $statementImport->id,
            'line_count' => $statementImport->lines()->count(),
            'status' => $statementImport->status,
        ];
    }

    protected function runTaxExport(VasIntegrationRun $run): array
    {
        $payload = (array) $run->payload;
        $adapter = $this->taxExportAdapterManager->resolve($run->provider ?: 'local');

        $salesBook = $this->enterpriseFinanceReportUtil->salesVatBook((int) $run->business_id);
        $purchaseBook = $this->enterpriseFinanceReportUtil->purchaseVatBook((int) $run->business_id);

        return $adapter->export((string) ($payload['export_type'] ?? 'vat_declaration'), array_replace($payload, [
            'business_id' => (int) $run->business_id,
            'sales_book' => $salesBook->map(fn ($row) => (array) $row)->all(),
            'purchase_book' => $purchaseBook->map(fn ($row) => (array) $row)->all(),
            'summary' => [
                'sales_tax_total' => round((float) $salesBook->sum('tax_amount'), 2),
                'purchase_tax_total' => round((float) $purchaseBook->sum('tax_amount'), 2),
            ],
        ]));
    }

    protected function runPayrollBridge(VasIntegrationRun $run): array
    {
        $payload = (array) $run->payload;
        $payrollGroupId = (int) ($payload['payroll_group_id'] ?? 0);
        if ($payrollGroupId <= 0) {
            throw new RuntimeException('Payroll bridge run requires payroll_group_id.');
        }

        $result = match ($run->action) {
            'bridge_payments' => $this->vasPayrollBridgeService->bridgePayments((int) $run->business_id, $payrollGroupId, (int) ($run->requested_by ?? 0)),
            default => $this->vasPayrollBridgeService->bridgeGroup((int) $run->business_id, $payrollGroupId, (int) ($run->requested_by ?? 0)),
        };

        return [
            'payroll_group_id' => $payrollGroupId,
            'mode' => $run->action,
            'batch_id' => data_get($result, 'batch.id'),
            'batch_status' => data_get($result, 'batch.status'),
            'payments_bridged' => (int) data_get($result, 'payments_bridged', 0),
            'voucher_id' => data_get($result, 'voucher.id'),
        ];
    }

    protected function runEInvoiceSync(VasIntegrationRun $run): array
    {
        $payload = (array) $run->payload;
        $documentId = (int) ($payload['einvoice_document_id'] ?? 0);
        if ($documentId <= 0) {
            throw new RuntimeException('E-invoice sync run requires einvoice_document_id.');
        }

        $document = VasEInvoiceDocument::query()
            ->where('business_id', $run->business_id)
            ->findOrFail($documentId);

        $adapter = $this->eInvoiceAdapterManager->resolve($run->provider ?: $document->provider);
        $result = $adapter->syncStatus([
            'provider_document_id' => $document->provider_document_id,
            'status' => $document->status,
        ]);

        $document->status = $result['status'] ?? $document->status;
        $document->last_synced_at = $result['last_synced_at'] ?? now();
        $document->response_payload = $result['response_payload'] ?? $result;
        $document->save();

        VasEInvoiceLog::create([
            'business_id' => $document->business_id,
            'einvoice_document_id' => $document->id,
            'action' => 'sync',
            'status' => $document->status,
            'request_payload' => ['provider_document_id' => $document->provider_document_id],
            'response_payload' => $result,
            'created_by' => $run->requested_by,
        ]);

        return [
            'einvoice_document_id' => (int) $document->id,
            'status' => $document->status,
            'last_synced_at' => optional($document->last_synced_at)->toDateTimeString(),
        ];
    }

    protected function runPostingFailureReplay(VasIntegrationRun $run): array
    {
        $payload = (array) $run->payload;
        $failure = VasPostingFailure::query()
            ->where('business_id', $run->business_id)
            ->whereNull('resolved_at')
            ->findOrFail((int) ($payload['failure_id'] ?? 0));

        $voucher = $this->postingService->replayFailure($failure);
        $failure->resolved_by = $run->requested_by;
        $failure->save();

        return [
            'failure_id' => (int) $failure->id,
            'voucher_id' => $voucher?->id,
            'resolved_at' => optional($failure->resolved_at)->toDateTimeString(),
        ];
    }

    protected function processWebhook(VasIntegrationWebhook $webhook): array
    {
        $payload = (array) $webhook->payload;

        if (array_key_exists($webhook->provider, (array) config('vasaccounting.einvoice_adapters', []))) {
            $document = VasEInvoiceDocument::query()
                ->when($webhook->business_id, fn ($query) => $query->where('business_id', $webhook->business_id))
                ->where('provider', $webhook->provider)
                ->where(function ($query) use ($payload, $webhook) {
                    $providerDocumentId = $payload['provider_document_id'] ?? null;
                    $documentNo = $payload['document_no'] ?? $webhook->external_reference;

                    if ($providerDocumentId) {
                        $query->where('provider_document_id', $providerDocumentId);
                    }

                    if ($documentNo) {
                        $query->orWhere('document_no', $documentNo);
                    }
                })
                ->first();

            if ($document) {
                $document->status = $payload['status'] ?? $document->status;
                $document->last_synced_at = now();
                $document->response_payload = $payload;
                $document->save();

                VasEInvoiceLog::create([
                    'business_id' => $document->business_id,
                    'einvoice_document_id' => $document->id,
                    'action' => 'webhook',
                    'status' => $document->status,
                    'request_payload' => $payload,
                    'response_payload' => ['webhook_id' => $webhook->id],
                    'created_by' => null,
                ]);

                return [
                    'matched' => true,
                    'einvoice_document_id' => (int) $document->id,
                    'status' => $document->status,
                ];
            }
        }

        return [
            'matched' => false,
            'status' => $payload['status'] ?? 'received',
        ];
    }

    protected function refreshStatementImportStatus(VasBankStatementImport $statementImport): void
    {
        $matched = (int) $statementImport->lines->where('match_status', 'matched')->count();
        $ignored = (int) $statementImport->lines->where('match_status', 'ignored')->count();
        $unmatched = (int) $statementImport->lines->where('match_status', 'unmatched')->count();

        $statementImport->status = match (true) {
            $matched === 0 && $ignored === 0 && $unmatched > 0 => 'imported',
            $unmatched === 0 && ($matched + $ignored) > 0 => 'reconciled',
            default => 'in_review',
        };
        $statementImport->save();
    }
}
