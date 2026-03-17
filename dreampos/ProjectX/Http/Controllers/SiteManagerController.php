<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\ProjectX\Http\Requests\SiteManager\UpdateSiteSettingsRequest;
use Modules\ProjectX\Utils\SiteManagerUtil;

class SiteManagerController extends Controller
{
    protected SiteManagerUtil $siteManagerUtil;

    public function __construct(SiteManagerUtil $siteManagerUtil)
    {
        $this->siteManagerUtil = $siteManagerUtil;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('projectx.site_manager.edit')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $settings = $this->siteManagerUtil->getSettings($business_id, SiteManagerUtil::WELCOME_KEYS);

        return view('projectx::site_manager.index', compact('settings', 'business_id'));
    }

    public function edit(Request $request)
    {
        if (! auth()->user()->can('projectx.site_manager.edit')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $settings = $this->siteManagerUtil->getSettings($business_id, SiteManagerUtil::WELCOME_KEYS);

        return view('projectx::site_manager.edit', compact('settings', 'business_id'));
    }

    public function update(UpdateSiteSettingsRequest $request)
    {
        if (! auth()->user()->can('projectx.site_manager.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $validated = $request->validated();

            $keyValue = [
                'site_name' => $validated['site_name'] ?? null,
                'hero_title' => $validated['hero_title'] ?? null,
                'hero_subtitle' => $validated['hero_subtitle'] ?? null,
                'cta_label' => $validated['cta_label'] ?? null,
                'cta_url' => $validated['cta_url'] ?? null,
                'footer_copyright' => $validated['footer_copyright'] ?? null,
                'logo_url' => $validated['logo_url'] ?? null,
                'nav_items' => $validated['nav_items'] ?? [],
            ];

            $this->siteManagerUtil->setSettings($business_id, $keyValue);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('projectx::lang.site_settings_updated'));
            }

            return redirect()
                ->route('projectx.site_manager.index')
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.site_settings_updated')]);
        } catch (\Exception $e) {
            Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($e);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        }
    }
}
