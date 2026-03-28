<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Http\Requests\StoreVasPeriodRequest;
use Modules\VasAccounting\Services\VasPeriodCloseService;

class PeriodController extends VasBaseController
{
    public function __construct(protected VasPeriodCloseService $periodCloseService)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.periods.manage');

        $businessId = $this->businessId($request);
        $periods = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->orderByDesc('start_date')
            ->get();

        return view('vasaccounting::periods.index', compact('periods'));
    }

    public function store(StoreVasPeriodRequest $request): RedirectResponse
    {
        VasAccountingPeriod::create([
            'business_id' => $this->businessId($request),
            'name' => $request->validated()['name'],
            'start_date' => $request->validated()['start_date'],
            'end_date' => $request->validated()['end_date'],
            'status' => 'open',
            'is_adjustment_period' => (bool) ($request->validated()['is_adjustment_period'] ?? false),
        ]);

        return redirect()
            ->route('vasaccounting.periods.index')
            ->with('status', ['success' => true, 'msg' => __('messages.success')]);
    }

    public function close(Request $request, int $period): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.close.manage');

        $periodModel = VasAccountingPeriod::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($period);

        $this->periodCloseService->closePeriod($periodModel, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.periods.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.period_closed')]);
    }
}
