<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Http\Requests\StoreCutoverSettingsRequest;
use Modules\VasAccounting\Http\Requests\UpdateCutoverPersonaRequest;
use Modules\VasAccounting\Services\CutoverService;

class CutoverController extends VasBaseController
{
    public function __construct(protected CutoverService $cutoverService)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.cutover.manage');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $selectedPeriod = $request->query('period') ? (string) $request->query('period') : null;
        $selectedBranches = collect((array) $request->query('branches', []))
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->values()
            ->all();
        if ($selectedLocationId && $selectedBranches === []) {
            $selectedBranches = [$selectedLocationId];
        }
        $parityReport = $this->cutoverService->parityReport($businessId, $selectedPeriod, $selectedBranches);
        $branchOptions = BusinessLocation::forDropdown($businessId);
        $branchRows = collect((array) data_get($parityReport, 'branches', []))
            ->when($selectedLocationId, function ($rows) use ($selectedLocationId) {
                return $rows->filter(function (array $row) use ($selectedLocationId) {
                    $branchId = (int) ($row['branch_id'] ?? $row['location_id'] ?? 0);

                    return $branchId === $selectedLocationId;
                });
            })
            ->values()
            ->all();
        $parityStats = [
            'total_sections' => count((array) data_get($parityReport, 'sections', [])),
            'aligned_sections' => collect((array) data_get($parityReport, 'sections', []))
                ->where('status', 'aligned')
                ->count(),
            'misaligned_sections' => collect((array) data_get($parityReport, 'sections', []))
                ->where('status', '!=', 'aligned')
                ->count(),
            'scoped_branch_rows' => count($branchRows),
        ];
        $activeScopeLabel = $selectedLocationId && isset($branchOptions[$selectedLocationId])
            ? (string) $branchOptions[$selectedLocationId]
            : 'All branches';

        return view('vasaccounting::cutover.index', [
            'readinessSummary' => $this->cutoverService->readinessSummary($businessId),
            'blockers' => $this->cutoverService->cutoverBlockers($businessId),
            'parity' => $this->cutoverService->paritySnapshot($businessId),
            'parityReport' => $parityReport,
            'parityBranchRows' => $branchRows,
            'parityStats' => $parityStats,
            'providerHealth' => $this->cutoverService->providerHealth($businessId),
            'cutoverSettings' => $this->cutoverService->cutoverSettings($businessId),
            'rolloutSettings' => $this->cutoverService->rolloutSettings($businessId),
            'uatPersonas' => $this->cutoverService->uatPersonas($businessId),
            'legacyRoutes' => $this->cutoverService->legacyRouteMappings(),
            'legacyModeOptions' => $this->cutoverService->legacyModeOptions(),
            'familyModeOptions' => $this->cutoverService->familyModeOptions(),
            'parallelRunOptions' => $this->cutoverService->parallelRunOptions(),
            'rolloutStatusOptions' => $this->cutoverService->rolloutStatusOptions(),
            'branchOptions' => $branchOptions,
            'locationOptions' => $branchOptions,
            'selectedLocationId' => $selectedLocationId,
            'activeScopeLabel' => $activeScopeLabel,
            'selectedPeriod' => $selectedPeriod ?: data_get($parityReport, 'period.token'),
            'selectedBranches' => $selectedBranches,
        ]);
    }

    public function updateSettings(StoreCutoverSettingsRequest $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cutover.manage');

        $cutoverSettings = (array) $request->input('cutover_settings', []);
        $cutoverSettings['hide_legacy_accounting_menu'] = $request->boolean('cutover_settings.hide_legacy_accounting_menu');
        $cutoverSettings['family_modes'] = collect((array) ($cutoverSettings['family_modes'] ?? []))
            ->map(fn ($value) => (string) $value)
            ->filter(fn (string $value) => $value !== '')
            ->all();

        $rolloutSettings = (array) $request->input('rollout_settings', []);
        $rolloutSettings['enabled_branch_ids'] = array_values(array_map('intval', (array) ($rolloutSettings['enabled_branch_ids'] ?? [])));
        $rolloutSettings['enabled_document_families'] = array_values(array_map('strval', (array) ($rolloutSettings['enabled_document_families'] ?? [])));

        $this->cutoverService->updateSettings(
            $this->businessId($request),
            $cutoverSettings,
            $rolloutSettings
        );

        return redirect()
            ->route('vasaccounting.cutover.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.cutover_settings_saved')]);
    }

    public function updatePersona(UpdateCutoverPersonaRequest $request, string $persona): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cutover.manage');

        $this->cutoverService->updatePersonaStatus(
            $this->businessId($request),
            $persona,
            $request->boolean('completed')
        );

        return redirect()
            ->route('vasaccounting.cutover.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.cutover_persona_updated')]);
    }
}
