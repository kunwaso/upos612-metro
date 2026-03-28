<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasBankAccount;
use Modules\VasAccounting\Entities\VasContract;
use Modules\VasAccounting\Entities\VasLoan;
use Modules\VasAccounting\Entities\VasLoanRepaymentSchedule;
use Modules\VasAccounting\Http\Requests\DisburseLoanRequest;
use Modules\VasAccounting\Http\Requests\SettleLoanRepaymentRequest;
use Modules\VasAccounting\Http\Requests\StoreLoanRepaymentScheduleRequest;
use Modules\VasAccounting\Http\Requests\StoreLoanRequest;
use Modules\VasAccounting\Services\LoanAccountingService;
use Modules\VasAccounting\Utils\EnterprisePlanningReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class LoanController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected EnterprisePlanningReportUtil $planningReportUtil,
        protected LoanAccountingService $loanAccountingService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.loans.manage');

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['loans'] ?? true) === false) {
            abort(404);
        }

        return view('vasaccounting::loans.index', [
            'summary' => $this->planningReportUtil->loanSummary($businessId),
            'loanRows' => $this->planningReportUtil->loanRows($businessId),
            'scheduleRows' => $this->planningReportUtil->loanScheduleRows($businessId),
            'bankAccountOptions' => Schema::hasTable('vas_bank_accounts')
                ? VasBankAccount::query()->where('business_id', $businessId)->orderBy('account_code')->get()
                    ->mapWithKeys(fn (VasBankAccount $account) => [$account->id => $account->account_code . ' - ' . $account->bank_name . ' / ' . $account->account_number])
                : collect(),
            'loanOptions' => Schema::hasTable('vas_loans')
                ? VasLoan::query()->where('business_id', $businessId)->orderBy('loan_no')->pluck('loan_no', 'id')
                : collect(),
            'contractOptions' => Schema::hasTable('vas_contracts')
                ? VasContract::query()->where('business_id', $businessId)->orderBy('contract_no')->pluck('contract_no', 'id')
                : collect(),
        ]);
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        VasLoan::create([
            'business_id' => $this->businessId($request),
            'loan_no' => strtoupper((string) $request->input('loan_no')),
            'lender_name' => $request->input('lender_name'),
            'bank_account_id' => $request->input('bank_account_id'),
            'contract_id' => $request->input('contract_id'),
            'principal_amount' => $request->input('principal_amount'),
            'interest_rate' => $request->input('interest_rate', 0),
            'disbursement_date' => $request->input('disbursement_date'),
            'maturity_date' => $request->input('maturity_date'),
            'status' => $request->input('status', 'draft'),
        ]);

        return redirect()
            ->route('vasaccounting.loans.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.loan_saved')]);
    }

    public function storeSchedule(StoreLoanRepaymentScheduleRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $loan = VasLoan::query()->where('business_id', $businessId)->findOrFail((int) $request->input('loan_id'));

        VasLoanRepaymentSchedule::create([
            'business_id' => $businessId,
            'loan_id' => (int) $loan->id,
            'due_date' => $request->input('due_date'),
            'principal_due' => $request->input('principal_due'),
            'interest_due' => $request->input('interest_due', 0),
            'status' => $request->input('status', 'planned'),
        ]);

        return redirect()
            ->route('vasaccounting.loans.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.loan_schedule_saved')]);
    }

    public function disburse(DisburseLoanRequest $request, int $loan): RedirectResponse
    {
        $loanModel = VasLoan::query()
            ->where('business_id', $this->businessId($request))
            ->with('bankAccount')
            ->findOrFail($loan);

        $this->loanAccountingService->disburse($loanModel, (int) auth()->id(), $request->input('disbursed_at'));

        return redirect()
            ->route('vasaccounting.loans.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.loan_disbursed')]);
    }

    public function settleSchedule(SettleLoanRepaymentRequest $request, int $schedule): RedirectResponse
    {
        $scheduleModel = VasLoanRepaymentSchedule::query()
            ->where('business_id', $this->businessId($request))
            ->with(['loan.bankAccount'])
            ->findOrFail($schedule);

        $this->loanAccountingService->settleSchedule($scheduleModel, (int) auth()->id(), $request->input('settled_at'));

        return redirect()
            ->route('vasaccounting.loans.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.loan_schedule_settled')]);
    }
}
