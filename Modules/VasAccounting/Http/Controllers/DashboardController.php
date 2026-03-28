<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\Request;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class DashboardController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected VasInventoryValuationService $inventoryValuationService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.access');

        $businessId = $this->businessId($request);
        $bootstrap = $this->vasUtil->ensureBusinessBootstrapped($businessId, (int) auth()->id());
        $metrics = $this->vasUtil->dashboardMetrics($businessId);
        $inventoryTotals = $this->inventoryValuationService->totals($businessId);
        $recentVouchers = VasVoucher::query()->where('business_id', $businessId)->latest()->take(8)->get();
        $periods = VasAccountingPeriod::query()->where('business_id', $businessId)->latest('start_date')->take(6)->get();
        $failures = VasPostingFailure::query()->where('business_id', $businessId)->whereNull('resolved_at')->latest()->take(5)->get();

        return view('vasaccounting::dashboard.index', compact('metrics', 'inventoryTotals', 'recentVouchers', 'periods', 'failures') + [
            'autoBootstrapped' => $bootstrap['bootstrapped'],
        ]);
    }
}
