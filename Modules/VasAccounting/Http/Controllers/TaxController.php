<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $this->authorizePermission('vas_accounting.tax.manage');

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
        ]);

        return redirect()
            ->route('vasaccounting.tax.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.tax_export_generated')])
            ->with('tax_export_result', $result);
    }
}
