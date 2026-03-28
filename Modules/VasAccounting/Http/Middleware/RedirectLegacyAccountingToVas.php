<?php

namespace Modules\VasAccounting\Http\Middleware;

use App\Utils\ModuleUtil;
use Closure;
use Illuminate\Http\Request;
use Modules\VasAccounting\Services\CutoverService;

class RedirectLegacyAccountingToVas
{
    public function __construct(
        protected ModuleUtil $moduleUtil,
        protected CutoverService $cutoverService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        if ($businessId <= 0 || ! $this->moduleUtil->isModuleInstalled('VasAccounting')) {
            return $next($request);
        }

        $action = $this->cutoverService->legacyRouteAction($businessId, $request);
        if (! is_array($action)) {
            return $next($request);
        }

        if ($request->ajax() || $request->expectsJson()) {
            $status = $action['mode'] === 'disabled' ? 410 : 409;

            return response()->json([
                'success' => false,
                'message' => $action['message'],
                'redirect_to' => $action['target_url'],
                'legacy_mode' => $action['mode'],
            ], $status);
        }

        if ($action['mode'] === 'disabled') {
            abort(410, __('vasaccounting::lang.legacy_accounting_disabled'));
        }

        return redirect($action['target_url'])
            ->with('status', ['success' => true, 'msg' => $action['message']]);
    }
}
