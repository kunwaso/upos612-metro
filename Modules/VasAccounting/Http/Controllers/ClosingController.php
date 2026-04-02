<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\ProcurementDiscrepancyServiceInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Http\Requests\FinanceDocumentActionRequest;
use Modules\VasAccounting\Http\Requests\ReopenPeriodRequest;
use Modules\VasAccounting\Services\ReportSnapshotService;
use Modules\VasAccounting\Services\VasPeriodCloseService;

class ClosingController extends VasBaseController
{
    public function __construct(
        protected VasPeriodCloseService $periodCloseService,
        protected ReportSnapshotService $reportSnapshotService,
        protected ProcurementDiscrepancyServiceInterface $procurementDiscrepancyService
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
            'procurementAssigneeOptions' => User::forDropdown($businessId, false, false, false, false),
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

    public function assignProcurementDiscrepancy(FinanceDocumentActionRequest $request, int $period, int $exception): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.close.manage');

        VasAccountingPeriod::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($period);

        $ownerId = (int) $request->input('owner_id');
        if ($ownerId <= 0) {
            return redirect()
                ->route('vasaccounting.closing.index')
                ->with('status', ['success' => false, 'msg' => __('vasaccounting::lang.procurement_discrepancy_owner_required')]);
        }

        try {
            $exceptionModel = FinanceMatchException::query()
                ->where('business_id', $this->businessId($request))
                ->findOrFail($exception);

            $this->procurementDiscrepancyService->assignOwner(
                $exceptionModel,
                $ownerId,
                new ActionContext(
                    (int) auth()->id(),
                    $this->businessId($request),
                    $request->input('reason') ?: 'Procurement discrepancy reassigned from close control board',
                    $request->input('request_id'),
                    $request->ip(),
                    $request->userAgent(),
                    [
                        'source' => 'closing_control_board',
                        'closing_period_id' => $period,
                        'assigned_owner_id' => $ownerId,
                    ]
                )
            );

            return redirect()
                ->route('vasaccounting.closing.index')
                ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.procurement_discrepancy_reassigned')]);
        } catch (\Throwable $throwable) {
            return redirect()
                ->route('vasaccounting.closing.index')
                ->with('status', ['success' => false, 'msg' => $throwable->getMessage()]);
        }
    }

    public function assignUnassignedProcurementDiscrepanciesToMe(Request $request, int $period): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.close.manage');

        $periodModel = VasAccountingPeriod::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($period);

        $userId = (int) auth()->id();
        $context = new ActionContext(
            $userId,
            $this->businessId($request),
            $request->input('reason') ?: 'Unassigned procurement discrepancies claimed from close control board',
            $request->input('request_id'),
            $request->ip(),
            $request->userAgent(),
            [
                'source' => 'closing_control_board',
                'closing_period_id' => $period,
                'batch_assign_to_me' => true,
            ]
        );

        $assigned = 0;
        foreach ($this->periodCloseService->unassignedProcurementDiscrepancies($this->businessId($request), $periodModel) as $exceptionModel) {
            $this->procurementDiscrepancyService->assignOwner($exceptionModel, $userId, $context);
            $assigned++;
        }

        return redirect()
            ->route('vasaccounting.closing.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.procurement_discrepancy_batch_assigned', ['count' => $assigned])]);
    }

    public function assignUnassignedProcurementDiscrepancies(Request $request, int $period): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.close.manage');

        $periodModel = VasAccountingPeriod::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($period);

        $ownerId = (int) $request->input('owner_id');
        if ($ownerId <= 0) {
            return redirect()
                ->route('vasaccounting.closing.index')
                ->with('status', ['success' => false, 'msg' => __('vasaccounting::lang.procurement_discrepancy_owner_required')]);
        }

        $context = new ActionContext(
            (int) auth()->id(),
            $this->businessId($request),
            $request->input('reason') ?: 'Unassigned procurement discrepancies reassigned from close control board',
            $request->input('request_id'),
            $request->ip(),
            $request->userAgent(),
            [
                'source' => 'closing_control_board',
                'closing_period_id' => $period,
                'assigned_owner_id' => $ownerId,
                'batch_assign' => true,
            ]
        );

        $assigned = 0;
        foreach ($this->periodCloseService->unassignedProcurementDiscrepancies($this->businessId($request), $periodModel) as $exceptionModel) {
            $this->procurementDiscrepancyService->assignOwner($exceptionModel, $ownerId, $context);
            $assigned++;
        }

        return redirect()
            ->route('vasaccounting.closing.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.procurement_discrepancy_batch_reassigned', ['count' => $assigned])]);
    }
}
