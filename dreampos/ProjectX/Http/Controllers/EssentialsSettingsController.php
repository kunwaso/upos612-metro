<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use App\System;
use Illuminate\Http\Request;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsSettingsUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;

class EssentialsSettingsController extends Controller
{
    protected ProjectXEssentialsUtil $projectXEssentialsUtil;

    public function __construct(ProjectXEssentialsUtil $projectXEssentialsUtil)
    {
        $this->projectXEssentialsUtil = $projectXEssentialsUtil;
    }

    public function edit(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureSettingsPermission($business_id);

        $settings = $this->projectXEssentialsUtil->essentialsSettings($business_id);
        $module_version = System::getProperty('essentials_version');

        return view('projectx::essentials.settings.edit', compact('settings', 'module_version'));
    }

    public function update(EssentialsSettingsUpdateRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureSettingsPermission($business_id);

        try {
            $input = $request->only([
                'leave_ref_no_prefix',
                'leave_instructions',
                'payroll_ref_no_prefix',
                'essentials_todos_prefix',
                'grace_before_checkin',
                'grace_after_checkin',
                'grace_before_checkout',
                'grace_after_checkout',
            ]);

            $input['is_location_required'] = $request->boolean('is_location_required') ? 1 : 0;
            $input['calculate_sales_target_commission_without_tax'] = $request->boolean('calculate_sales_target_commission_without_tax') ? 1 : 0;

            $this->projectXEssentialsUtil->updateEssentialsSettings($business_id, $input, $request);

            return redirect()->back()->with('status', [
                'success' => true,
                'msg' => __('lang_v1.updated_succesfully'),
            ]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }
}
