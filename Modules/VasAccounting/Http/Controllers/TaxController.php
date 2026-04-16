<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Entities\VasTaxCode;
use Modules\VasAccounting\Http\Requests\ExportTaxRequest;
use Modules\VasAccounting\Services\TaxExportAdapterManager;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class TaxController extends VasBaseController
{
    public function __construct(
        protected TaxExportAdapterManager $adapterManager,
        protected EnterpriseFinanceReportUtil $enterpriseReportUtil,
        protected VasAccountingUtil $vasUtil
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeTaxCenterAccess();

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $taxCodes = VasTaxCode::query()->where('business_id', $businessId)->orderBy('code')->get();
        $summaries = DB::table('vas_journal_entries as je')
            ->leftJoin('vas_tax_codes as tc', 'tc.id', '=', 'je.tax_code_id')
            ->where('je.business_id', $businessId)
            ->selectRaw('COALESCE(tc.code, "UNMAPPED") as code, COALESCE(tc.name, "Unmapped") as name, SUM(je.debit) as total_debit, SUM(je.credit) as total_credit')
            ->groupBy('tc.code', 'tc.name')
            ->get();
        $salesVatBook = $this->enterpriseReportUtil->salesVatBook($businessId);
        $purchaseVatBook = $this->enterpriseReportUtil->purchaseVatBook($businessId);

        return view('vasaccounting::tax.index', [
            'taxCodes' => $taxCodes,
            'summaries' => $summaries,
            'salesVatBook' => $salesVatBook,
            'purchaseVatBook' => $purchaseVatBook,
            'taxStats' => [
                'tax_codes' => $taxCodes->count(),
                'summary_rows' => $summaries->count(),
                'sales_tax_total' => round((float) $salesVatBook->sum('tax_amount'), 2),
                'purchase_tax_total' => round((float) $purchaseVatBook->sum('tax_amount'), 2),
            ],
            'providerOptions' => $this->vasUtil->providerOptions('tax_export_adapters'),
            'defaultProvider' => (string) (((array) $settings->integration_settings)['tax_export_provider'] ?? 'local'),
        ]);
    }

    public function export(ExportTaxRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $integrationSettings = (array) $settings->integration_settings;
        $provider = (string) ($request->input('provider') ?: (((array) $settings->integration_settings)['tax_export_provider'] ?? 'local'));
        $adapter = $this->adapterManager->resolve($provider);
        $salesVatBook = $this->enterpriseReportUtil->salesVatBook($businessId);
        $purchaseVatBook = $this->enterpriseReportUtil->purchaseVatBook($businessId);

        $result = $adapter->export((string) $request->input('export_type'), [
            'business_id' => $businessId,
            'sales_book' => $salesVatBook->map(fn ($row) => (array) $row)->all(),
            'purchase_book' => $purchaseVatBook->map(fn ($row) => (array) $row)->all(),
            'summary' => [
                'sales_tax_total' => round((float) $salesVatBook->sum('tax_amount'), 2),
                'purchase_tax_total' => round((float) $purchaseVatBook->sum('tax_amount'), 2),
            ],
        ], [
            'request_id' => (string) ($request->headers->get('X-Request-Id') ?: Str::uuid()),
            'idempotency_key' => (string) ($request->headers->get('Idempotency-Key') ?: sha1($businessId . '|' . $provider . '|' . (string) $request->input('export_type'))),
            'signed_payload_hash' => hash('sha256', json_encode([
                'business_id' => $businessId,
                'provider' => $provider,
                'export_type' => (string) $request->input('export_type'),
            ])),
            'provider_config' => [
                'vnpt_api_base_url' => (string) ($integrationSettings['vnpt_api_base_url'] ?? ''),
                'vnpt_client_id' => (string) ($integrationSettings['vnpt_client_id'] ?? ''),
                'vnpt_client_secret' => (string) ($integrationSettings['vnpt_client_secret'] ?? ''),
                'vnpt_tax_username' => (string) ($integrationSettings['vnpt_tax_username'] ?? ''),
                'vnpt_tax_password' => (string) ($integrationSettings['vnpt_tax_password'] ?? ''),
            ],
            'retry' => [
                'attempt' => (int) $request->input('retry_attempt', 1),
                'is_retry' => (bool) $request->boolean('is_retry'),
            ],
        ]);

        return redirect()
            ->route('vasaccounting.tax.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.tax_export_generated')])
            ->with('tax_export_result', $result);
    }

    protected function authorizeTaxCenterAccess(): void
    {
        if (
            ! auth()->check()
            || (
                ! auth()->user()->can('vas_accounting.tax.manage')
                && ! auth()->user()->can('vas_accounting.filing.operator')
            )
        ) {
            abort(403, __('vasaccounting::lang.unauthorized_action'));
        }
    }
}
