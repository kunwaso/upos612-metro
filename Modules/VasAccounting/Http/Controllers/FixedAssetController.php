<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\VasAccounting\Entities\VasAssetCategory;
use Modules\VasAccounting\Entities\VasFixedAsset;
use Modules\VasAccounting\Http\Requests\DisposeFixedAssetRequest;
use Modules\VasAccounting\Http\Requests\StoreAssetCategoryRequest;
use Modules\VasAccounting\Http\Requests\StoreFixedAssetRequest;
use Modules\VasAccounting\Http\Requests\TransferFixedAssetRequest;
use Modules\VasAccounting\Services\VasDepreciationService;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class FixedAssetController extends VasBaseController
{
    public function __construct(
        protected VasDepreciationService $depreciationService,
        protected VasPostingService $postingService,
        protected VasAccountingUtil $vasUtil,
        protected OperationsAssetReportUtil $operationsAssetReportUtil
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.assets.manage');

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['assets'] ?? true) === false) {
            abort(404);
        }

        return view('vasaccounting::fixed_assets.index', [
            'summary' => $this->operationsAssetReportUtil->fixedAssetSummary($businessId),
            'assetRows' => $this->operationsAssetReportUtil->fixedAssetRegisterRows($businessId),
            'categories' => VasAssetCategory::query()->where('business_id', $businessId)->where('is_active', true)->orderBy('name')->get(),
            'chartOptions' => $this->vasUtil->chartOptions($businessId),
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'vendorOptions' => Contact::query()
                ->where('business_id', $businessId)
                ->whereIn('type', ['supplier', 'both'])
                ->where('contact_status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'supplier_business_name'])
                ->mapWithKeys(function (Contact $contact) {
                    $label = trim($contact->name . ($contact->supplier_business_name ? ' (' . $contact->supplier_business_name . ')' : ''));

                    return [$contact->id => $label];
                })
                ->prepend(__('lang_v1.none'), ''),
        ]);
    }

    public function storeCategory(StoreAssetCategoryRequest $request): RedirectResponse
    {
        VasAssetCategory::create([
            'business_id' => $this->businessId($request),
            'name' => $request->input('name'),
            'asset_account_id' => $request->input('asset_account_id'),
            'accumulated_depreciation_account_id' => $request->input('accumulated_depreciation_account_id'),
            'depreciation_expense_account_id' => $request->input('depreciation_expense_account_id'),
            'default_useful_life_months' => $request->input('default_useful_life_months'),
            'depreciation_method' => $request->input('depreciation_method', 'straight_line'),
            'is_active' => (bool) $request->input('is_active', true),
        ]);

        return redirect()
            ->route('vasaccounting.assets.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.asset_category_saved')]);
    }

    public function storeAsset(StoreFixedAssetRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $category = VasAssetCategory::query()
            ->where('business_id', $businessId)
            ->findOrFail((int) $request->input('asset_category_id'));

        $usefulLifeMonths = (int) ($request->input('useful_life_months') ?: $category->default_useful_life_months);
        $assetAccountId = (int) ($request->input('asset_account_id') ?: $category->asset_account_id);
        $accumulatedAccountId = (int) ($request->input('accumulated_depreciation_account_id') ?: $category->accumulated_depreciation_account_id);
        $expenseAccountId = (int) ($request->input('depreciation_expense_account_id') ?: $category->depreciation_expense_account_id);

        if ($usefulLifeMonths <= 0 || $assetAccountId <= 0 || $accumulatedAccountId <= 0 || $expenseAccountId <= 0) {
            throw ValidationException::withMessages([
                'asset_category_id' => 'Choose a category with complete asset and depreciation account defaults, or provide the missing override accounts.',
            ]);
        }

        $originalCost = round((float) $request->input('original_cost'), 4);
        $salvageValue = round((float) $request->input('salvage_value', 0), 4);
        $monthlyDepreciation = round(max(0, $originalCost - $salvageValue) / $usefulLifeMonths, 4);

        VasFixedAsset::create([
            'business_id' => $businessId,
            'asset_category_id' => $category->id,
            'asset_code' => strtoupper((string) $request->input('asset_code')),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'acquisition_date' => $request->input('acquisition_date'),
            'capitalization_date' => $request->input('capitalization_date'),
            'vendor_contact_id' => $request->input('vendor_contact_id'),
            'business_location_id' => $request->input('business_location_id'),
            'original_cost' => $originalCost,
            'salvage_value' => $salvageValue,
            'useful_life_months' => $usefulLifeMonths,
            'monthly_depreciation' => $monthlyDepreciation,
            'status' => $request->input('status', 'active'),
            'asset_account_id' => $assetAccountId,
            'accumulated_depreciation_account_id' => $accumulatedAccountId,
            'depreciation_expense_account_id' => $expenseAccountId,
            'created_by' => auth()->id(),
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('vasaccounting.assets.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.fixed_asset_saved')]);
    }

    public function transfer(TransferFixedAssetRequest $request, int $asset): RedirectResponse
    {
        $assetModel = $this->findBusinessAsset($this->businessId($request), $asset);
        $meta = (array) $assetModel->meta;
        $transferHistory = (array) ($meta['transfer_history'] ?? []);
        $transferHistory[] = [
            'from_business_location_id' => $assetModel->business_location_id,
            'to_business_location_id' => (int) $request->input('business_location_id'),
            'notes' => $request->input('notes'),
            'transferred_at' => now()->toDateTimeString(),
            'transferred_by' => auth()->id(),
        ];

        $meta['transfer_history'] = $transferHistory;
        $assetModel->business_location_id = (int) $request->input('business_location_id');
        $assetModel->meta = $meta;
        $assetModel->save();

        return redirect()
            ->route('vasaccounting.assets.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.fixed_asset_transferred')]);
    }

    public function dispose(DisposeFixedAssetRequest $request, int $asset): RedirectResponse
    {
        $assetModel = $this->findBusinessAsset($this->businessId($request), $asset);

        if ($assetModel->status === 'disposed' || ! empty($assetModel->disposed_at)) {
            throw ValidationException::withMessages([
                'disposed_at' => 'This asset has already been disposed.',
            ]);
        }

        $accumulatedDepreciation = min(
            round((float) $assetModel->depreciations()->where('status', 'posted')->sum('amount'), 4),
            round((float) $assetModel->original_cost, 4)
        );
        $netBookValue = round(max(0, (float) $assetModel->original_cost - $accumulatedDepreciation), 4);

        $lines = [
            [
                'account_id' => (int) $assetModel->accumulated_depreciation_account_id,
                'description' => 'Reverse accumulated depreciation',
                'debit' => $accumulatedDepreciation,
                'credit' => 0,
                'asset_id' => $assetModel->id,
            ],
        ];

        if ($netBookValue > 0) {
            $lines[] = [
                'account_id' => (int) $assetModel->depreciation_expense_account_id,
                'description' => 'Recognize net book value on disposal',
                'debit' => $netBookValue,
                'credit' => 0,
                'asset_id' => $assetModel->id,
            ];
        }

        $lines[] = [
            'account_id' => (int) $assetModel->asset_account_id,
            'description' => 'Remove disposed asset cost',
            'debit' => 0,
            'credit' => (float) $assetModel->original_cost,
            'asset_id' => $assetModel->id,
        ];

        $voucher = $this->postingService->postVoucherPayload([
            'business_id' => (int) $assetModel->business_id,
            'voucher_type' => 'asset_disposal',
            'sequence_key' => 'general_journal',
            'source_type' => 'fixed_asset_disposal',
            'source_id' => (int) $assetModel->id,
            'posting_date' => $request->input('disposed_at'),
            'document_date' => $request->input('disposed_at'),
            'description' => 'Fixed asset disposal for ' . $assetModel->asset_code,
            'reference' => $assetModel->asset_code,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) auth()->id(),
            'business_location_id' => $assetModel->business_location_id,
            'lines' => $lines,
        ]);

        $meta = (array) $assetModel->meta;
        $meta['disposal'] = [
            'notes' => $request->input('notes'),
            'disposed_by' => auth()->id(),
            'disposed_at' => $request->input('disposed_at'),
            'accumulated_depreciation' => $accumulatedDepreciation,
            'net_book_value' => $netBookValue,
        ];

        $assetModel->status = 'disposed';
        $assetModel->disposed_at = $request->input('disposed_at');
        $assetModel->disposal_voucher_id = (int) $voucher->id;
        $assetModel->meta = $meta;
        $assetModel->save();

        return redirect()
            ->route('vasaccounting.assets.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.fixed_asset_disposed')]);
    }

    public function runDepreciation(Request $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.assets.manage');

        $this->depreciationService->run($this->businessId($request), null, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.assets.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.depreciation_completed')]);
    }

    protected function findBusinessAsset(int $businessId, int $asset): VasFixedAsset
    {
        return VasFixedAsset::query()
            ->where('business_id', $businessId)
            ->with('depreciations')
            ->findOrFail($asset);
    }
}
