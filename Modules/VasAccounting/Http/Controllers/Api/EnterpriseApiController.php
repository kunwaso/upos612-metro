<?php

namespace Modules\VasAccounting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Http\Requests\StoreIntegrationWebhookRequest;
use Modules\VasAccounting\Services\IntegrationHubService;
use Modules\VasAccounting\Services\ProviderHealthService;
use Modules\VasAccounting\Services\ReportSnapshotService;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class EnterpriseApiController extends Controller
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected ReportSnapshotService $reportSnapshotService,
        protected IntegrationHubService $integrationHubService,
        protected ProviderHealthService $providerHealthService
    ) {
    }

    public function health(Request $request): JsonResponse
    {
        $businessId = (int) ($request->user()->business_id ?? 0);

        return response()->json([
            'status' => 'ok',
            'guard' => 'api',
            'module' => 'vas-accounting',
            'version' => config('vasaccounting.version'),
            'provider_health' => $businessId > 0 ? $this->providerHealthService->healthForBusiness($businessId) : [],
        ]);
    }

    public function domains(Request $request): JsonResponse
    {
        $businessId = (int) $request->user()->business_id;

        return response()->json([
            'document_statuses' => $this->vasUtil->documentStatuses(),
            'period_statuses' => $this->vasUtil->periodStatuses(),
            'provider_labels' => [
                'bank_statement_import' => $this->vasUtil->providerOptions('bank_statement_import_adapters'),
                'tax_export' => $this->vasUtil->providerOptions('tax_export_adapters'),
                'einvoice' => $this->vasUtil->providerOptions('einvoice_adapters'),
                'payroll_bridge' => $this->vasUtil->providerOptions('payroll_bridge_adapters'),
            ],
            'domains' => collect($this->vasUtil->enterpriseDomains())
                ->map(function (array $domainConfig, string $domain) use ($businessId) {
                    return array_merge($domainConfig, [
                        'domain' => $domain,
                        'display' => [
                            'title' => $domainConfig['title'] ?? null,
                            'nav_label' => $domainConfig['nav_label'] ?? null,
                            'subtitle' => $domainConfig['subtitle'] ?? null,
                            'record_label' => $domainConfig['record_label'] ?? null,
                        ],
                        'summary' => $this->vasUtil->enterpriseDomainSummary($businessId, $domain),
                    ]);
                })
                ->values(),
        ]);
    }

    public function snapshots(Request $request): JsonResponse
    {
        $businessId = (int) $request->user()->business_id;

        return response()->json([
            'snapshots' => $this->reportSnapshotService->recentSnapshots($businessId, 20)->map(function ($snapshot) {
                return [
                    'id' => $snapshot->id,
                    'report_key' => $snapshot->report_key,
                    'snapshot_name' => $snapshot->snapshot_name,
                    'status' => $snapshot->status,
                    'generated_at' => optional($snapshot->generated_at)->toDateTimeString(),
                ];
            })->values(),
        ]);
    }

    public function integrationRuns(Request $request): JsonResponse
    {
        $businessId = (int) $request->user()->business_id;

        return response()->json([
            'overview' => $this->integrationHubService->overview($businessId),
            'runs' => $this->integrationHubService->recentRuns($businessId, 20)->values(),
        ]);
    }

    public function webhook(StoreIntegrationWebhookRequest $request, string $provider): JsonResponse
    {
        $businessId = (int) $request->user()->business_id;
        $payload = (array) ($request->input('payload') ?: $request->except(['event_key', 'external_reference', 'status', 'payload']));

        $webhook = $this->integrationHubService->recordWebhook(
            $businessId,
            $provider,
            $payload,
            $request->input('event_key'),
            $request->input('external_reference')
        );

        return response()->json([
            'status' => $webhook->status,
            'webhook_id' => $webhook->id,
            'processed_at' => optional($webhook->processed_at)->toDateTimeString(),
        ]);
    }
}
