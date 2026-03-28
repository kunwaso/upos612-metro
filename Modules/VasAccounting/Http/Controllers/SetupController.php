<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Http\Requests\StoreSetupRequest;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class SetupController extends VasBaseController
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.setup.manage');

        $businessId = $this->businessId($request);
        $bootstrap = $this->vasUtil->ensureBusinessBootstrapped($businessId, (int) auth()->id());
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $accounts = $this->vasUtil->chartOptions($businessId);
        $metrics = $this->vasUtil->dashboardMetrics($businessId);
        $bootstrapStatus = $this->vasUtil->bootstrapStatus($businessId);
        $oldPostingMap = array_filter(
            (array) $request->session()->getOldInput('posting_map', []),
            fn ($value) => $value !== null && $value !== ''
        );
        $selectedPostingMap = array_replace((array) $settings->posting_map, $oldPostingMap);
        $enterpriseDomains = $this->vasUtil->enterpriseDomains();
        $documentStatuses = $this->vasUtil->documentStatuses();

        return view('vasaccounting::setup.index', compact('settings', 'accounts', 'metrics', 'bootstrapStatus', 'selectedPostingMap', 'enterpriseDomains', 'documentStatuses') + [
            'autoBootstrapped' => $bootstrap['bootstrapped'],
        ]);
    }

    public function store(StoreSetupRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $validated = $request->validated();
        $featureFlags = array_fill_keys(array_keys($this->vasUtil->defaultFeatureFlags()), false);
        foreach ((array) ($validated['feature_flags'] ?? []) as $flagKey => $flagValue) {
            $featureFlags[$flagKey] = (bool) $flagValue;
        }
        $approvalSettings = array_replace((array) $settings->approval_settings, (array) ($validated['approval_settings'] ?? []));
        $approvalSettings['require_manual_voucher_approval'] = (bool) data_get($request->input('approval_settings', []), 'require_manual_voucher_approval', false);

        $settings->fill([
            'book_currency' => $validated['book_currency'],
            'inventory_method' => $validated['inventory_method'],
            'is_enabled' => $request->boolean('is_enabled'),
            'posting_map' => $validated['posting_map'] ?? $settings->posting_map,
            'einvoice_settings' => array_replace((array) $settings->einvoice_settings, (array) ($validated['einvoice_settings'] ?? [])),
            'depreciation_settings' => array_replace((array) $settings->depreciation_settings, (array) ($validated['depreciation_settings'] ?? [])),
            'tax_settings' => array_replace((array) $settings->tax_settings, (array) ($validated['tax_settings'] ?? [])),
            'feature_flags' => $featureFlags,
            'approval_settings' => $approvalSettings,
            'integration_settings' => array_replace((array) $settings->integration_settings, (array) ($validated['integration_settings'] ?? [])),
        ]);
        $settings->save();

        return redirect()
            ->route('vasaccounting.setup.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.setup_saved')]);
    }

    public function bootstrap(Request $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.setup.manage');

        $this->vasUtil->bootstrapBusiness($this->businessId($request), (int) auth()->id());

        return redirect()
            ->route('vasaccounting.setup.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.bootstrap_completed')]);
    }
}
