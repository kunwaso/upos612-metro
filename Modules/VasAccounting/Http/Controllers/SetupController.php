<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Http\Requests\StoreSetupRequest;
use Modules\VasAccounting\Services\ComplianceProfileService;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class SetupController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected ComplianceProfileService $complianceProfileService
    )
    {
    }

    public function index(Request $request)
    {
        $this->authorizeSetupAccess();

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
        $localeOptions = $this->vasUtil->localeOptions();
        $complianceProfiles = collect($this->complianceProfileService->availableProfiles())
            ->mapWithKeys(fn (array $profile, string $key) => [$key => (string) ($profile['label'] ?? $key)])
            ->all();
        $activeComplianceProfile = $this->complianceProfileService->activeProfileForSettings($settings);
        $complianceCompletion = $this->vasUtil->complianceCompletionStatus($settings);

        return view('vasaccounting::setup.index', compact('settings', 'accounts', 'metrics', 'bootstrapStatus', 'selectedPostingMap', 'enterpriseDomains', 'documentStatuses', 'localeOptions', 'complianceProfiles', 'activeComplianceProfile', 'complianceCompletion') + [
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
        $complianceSettings = array_replace((array) $settings->compliance_settings, (array) ($validated['compliance_settings'] ?? []));
        $complianceSettings = array_replace([
            'standard' => 'tt99_2025',
            'effective_date' => '2026-01-01',
            'legacy_bridge_enabled' => false,
            'profile_version' => '2026.01',
        ], $complianceSettings);

        $integrationSettings = array_replace((array) $settings->integration_settings, (array) ($validated['integration_settings'] ?? []));

        $settings->fill([
            'book_currency' => $validated['book_currency'],
            'inventory_method' => $validated['inventory_method'],
            'is_enabled' => $request->boolean('is_enabled'),
            'posting_map' => $validated['posting_map'] ?? $settings->posting_map,
            'compliance_settings' => $complianceSettings,
            'einvoice_settings' => array_replace((array) $settings->einvoice_settings, (array) ($validated['einvoice_settings'] ?? [])),
            'depreciation_settings' => array_replace((array) $settings->depreciation_settings, (array) ($validated['depreciation_settings'] ?? [])),
            'tax_settings' => array_replace((array) $settings->tax_settings, (array) ($validated['tax_settings'] ?? [])),
            'feature_flags' => $featureFlags,
            'approval_settings' => $approvalSettings,
            'integration_settings' => $integrationSettings,
        ]);

        if (\Illuminate\Support\Facades\Schema::hasColumn('vas_business_settings', 'compliance_standard')) {
            $settings->compliance_standard = (string) ($complianceSettings['standard'] ?? 'tt99_2025');
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('vas_business_settings', 'compliance_effective_date')) {
            $settings->compliance_effective_date = (string) ($complianceSettings['effective_date'] ?? '2026-01-01');
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('vas_business_settings', 'compliance_legacy_bridge_enabled')) {
            $settings->compliance_legacy_bridge_enabled = (bool) ($complianceSettings['legacy_bridge_enabled'] ?? false);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('vas_business_settings', 'compliance_profile_version')) {
            $settings->compliance_profile_version = (string) ($complianceSettings['profile_version'] ?? '2026.01');
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('vas_business_settings', 'ui_settings')) {
            $settings->ui_settings = array_replace(
                [
                    'locale' => $this->vasUtil->businessLocale($businessId),
                    'navigation_mode' => 'advanced',
                ],
                (array) $settings->ui_settings,
                (array) ($validated['ui_settings'] ?? [])
            );
        }

        $settings->save();

        $this->vasUtil->applyVasLocale($businessId, $request);

        return redirect()
            ->route('vasaccounting.setup.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.setup_saved')]);
    }

    public function bootstrap(Request $request): RedirectResponse
    {
        $this->authorizeSetupAccess();

        $this->vasUtil->bootstrapBusiness($this->businessId($request), (int) auth()->id());

        return redirect()
            ->route('vasaccounting.setup.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.bootstrap_completed')]);
    }

    protected function authorizeSetupAccess(): void
    {
        if (
            ! auth()->check()
            || (
                ! auth()->user()->can('vas_accounting.setup.manage')
                && ! auth()->user()->can('vas_accounting.compliance.admin')
            )
        ) {
            abort(403, __('vasaccounting::lang.unauthorized_action'));
        }
    }
}
