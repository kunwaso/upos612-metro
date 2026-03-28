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

        return view('vasaccounting::cutover.index', [
            'readinessSummary' => $this->cutoverService->readinessSummary($businessId),
            'blockers' => $this->cutoverService->cutoverBlockers($businessId),
            'parity' => $this->cutoverService->paritySnapshot($businessId),
            'cutoverSettings' => $this->cutoverService->cutoverSettings($businessId),
            'rolloutSettings' => $this->cutoverService->rolloutSettings($businessId),
            'uatPersonas' => $this->cutoverService->uatPersonas($businessId),
            'legacyRoutes' => $this->cutoverService->legacyRouteMappings(),
            'legacyModeOptions' => $this->cutoverService->legacyModeOptions(),
            'parallelRunOptions' => $this->cutoverService->parallelRunOptions(),
            'rolloutStatusOptions' => $this->cutoverService->rolloutStatusOptions(),
            'branchOptions' => BusinessLocation::forDropdown($businessId),
        ]);
    }

    public function updateSettings(StoreCutoverSettingsRequest $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cutover.manage');

        $cutoverSettings = (array) $request->input('cutover_settings', []);
        $cutoverSettings['hide_legacy_accounting_menu'] = $request->boolean('cutover_settings.hide_legacy_accounting_menu');

        $rolloutSettings = (array) $request->input('rollout_settings', []);
        $rolloutSettings['enabled_branch_ids'] = array_values(array_map('intval', (array) ($rolloutSettings['enabled_branch_ids'] ?? [])));

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
