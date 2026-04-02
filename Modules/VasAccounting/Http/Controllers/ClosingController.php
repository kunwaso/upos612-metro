<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Http\Requests\ReopenPeriodRequest;
use Modules\VasAccounting\Services\ReportSnapshotService;
use Modules\VasAccounting\Services\VasPeriodCloseService;

class ClosingController extends VasBaseController
{
    public function __construct(
        protected VasPeriodCloseService $periodCloseService,
        protected ReportSnapshotService $reportSnapshotService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.close.manage');

        $businessId = $this->businessId($request);
        $periods = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->orderByDesc('start_date')
            ->get();

        $blockers = [];
        $checklists = [];
        $treasuryInsights = [];
        $procurementInsights = [];
        $expenseInsights = [];
        foreach ($periods as $period) {
            $blockers[$period->id] = $this->periodCloseService->blockers($businessId, $period);
            $checklists[$period->id] = $this->periodCloseService->checklistForPeriod($businessId, $period);
            $treasuryInsights[$period->id] = $this->periodCloseService->treasuryCloseInsights($businessId, $period);
            $procurementInsights[$period->id] = $this->periodCloseService->procurementCloseInsights($businessId, $period);
            $expenseInsights[$period->id] = $this->periodCloseService->expenseCloseInsights($businessId, $period);
        }

        return view('vasaccounting::closing.index', [
            'periods' => $periods,
            'blockers' => $blockers,
            'checklists' => $checklists,
            'treasuryInsights' => $treasuryInsights,
            'procurementInsights' => $procurementInsights,
            'expenseInsights' => $expenseInsights,
            'recentPackets' => $this->reportSnapshotService->recentSnapshots($businessId, 10)->where('report_key', 'close_packet'),
        ]);
    }

    public function softLock(Request $request, int $period): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.close.manage');

        $periodModel = VasAccountingPeriod::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($period);

        $this->periodCloseService->softLockPeriod($periodModel, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.closing.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.period_soft_locked')]);
    }

    public function close(Request $request, int $period): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.close.manage');

        $periodModel = VasAccountingPeriod::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($period);

        $this->periodCloseService->closePeriod($periodModel, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.closing.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.period_closed')]);
    }

    public function reopen(ReopenPeriodRequest $request, int $period): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.close.manage');

        $periodModel = VasAccountingPeriod::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($period);

        $this->periodCloseService->reopenPeriod($periodModel, (int) auth()->id(), (string) $request->input('reason'));

        return redirect()
            ->route('vasaccounting.closing.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.period_reopened')]);
    }

    public function packet(Request $request, int $period): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.close.manage');

        VasAccountingPeriod::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($period);

        $this->reportSnapshotService->queueSnapshot($this->businessId($request), 'close_packet', [
            'period_id' => $period,
            'snapshot_name' => 'Close Packet #' . $period,
        ], (int) auth()->id());

        return redirect()
            ->route('vasaccounting.closing.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.close_packet_queued')]);
    }
}
