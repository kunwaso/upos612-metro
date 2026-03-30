<?php

namespace Modules\VasAccounting\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class ApplyVasLocale
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $businessId = 0;
        if ($request->hasSession()) {
            $businessId = (int) $request->session()->get('user.business_id', 0);
        }

        if ($businessId <= 0) {
            $businessId = (int) data_get($request->user(), 'business_id', 0);
        }

        if ($businessId <= 0) {
            $businessId = (int) $request->input('business_id', 0);
        }

        $this->vasUtil->applyVasLocale($businessId > 0 ? $businessId : null, $request);

        return $next($request);
    }
}
