<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Http\Requests\BridgePayrollBatchRequest;
use Modules\VasAccounting\Services\VasPayrollBridgeService;
use Modules\VasAccounting\Utils\EnterprisePlanningReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class PayrollController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected EnterprisePlanningReportUtil $planningReportUtil,
        protected VasPayrollBridgeService $payrollBridgeService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.payroll.manage');

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['payroll'] ?? true) === false) {
            abort(404);
        }

        return view('vasaccounting::payroll.index', [
            'summary' => $this->planningReportUtil->payrollSummary($businessId),
            'payrollGroups' => $this->planningReportUtil->payrollGroupRows($businessId),
            'payrollBatches' => $this->planningReportUtil->payrollBatchRows($businessId),
        ]);
    }

    public function bridgeGroup(BridgePayrollBatchRequest $request): RedirectResponse
    {
        $this->payrollBridgeService->bridgeGroup(
            $this->businessId($request),
            (int) $request->input('payroll_group_id'),
            (int) auth()->id()
        );

        return redirect()
            ->route('vasaccounting.payroll.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.payroll_batch_bridged')]);
    }

    public function bridgePayments(BridgePayrollBatchRequest $request): RedirectResponse
    {
        $result = $this->payrollBridgeService->bridgePayments(
            $this->businessId($request),
            (int) $request->input('payroll_group_id'),
            (int) auth()->id()
        );

        return redirect()
            ->route('vasaccounting.payroll.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.payroll_payments_bridged', ['count' => $result['payments_bridged']])]);
    }
}
