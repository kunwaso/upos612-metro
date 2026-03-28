<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\VasAccounting\Entities\VasPayableAllocation;
use Modules\VasAccounting\Http\Requests\StorePayableAllocationRequest;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;

class PayableController extends VasBaseController
{
    public function __construct(protected EnterpriseFinanceReportUtil $enterpriseReportUtil)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.payables.manage');

        $businessId = $this->businessId($request);

        return view('vasaccounting::payables.index', [
            'aging' => $this->enterpriseReportUtil->payableAging($businessId),
            'openItems' => $this->enterpriseReportUtil->payableOpenItems($businessId),
            'paymentItems' => $this->enterpriseReportUtil->payablePaymentItems($businessId),
            'recentAllocations' => $this->recentAllocations($businessId),
        ]);
    }

    public function storeAllocation(StorePayableAllocationRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $billItem = $this->enterpriseReportUtil->payableOpenItems($businessId)
            ->first(fn ($item) => (int) $item->id === (int) $request->input('bill_voucher_id'));
        $paymentItem = $this->enterpriseReportUtil->payablePaymentItems($businessId)
            ->first(fn ($item) => (int) $item->id === (int) $request->input('payment_voucher_id'));

        if (! $billItem || ! $paymentItem) {
            throw ValidationException::withMessages([
                'amount' => 'Choose a posted bill and a payment with available balance.',
            ]);
        }

        $maxAllocatable = min((float) $billItem->outstanding_amount, (float) $paymentItem->available_amount);
        if ((float) $request->input('amount') > $maxAllocatable + 0.0001) {
            throw ValidationException::withMessages([
                'amount' => 'Allocation amount exceeds the bill outstanding or payment available balance.',
            ]);
        }

        VasPayableAllocation::create([
            'business_id' => $businessId,
            'voucher_id' => (int) $request->input('payment_voucher_id'),
            'bill_voucher_id' => (int) $request->input('bill_voucher_id'),
            'payment_voucher_id' => (int) $request->input('payment_voucher_id'),
            'contact_id' => $request->input('contact_id') ?: $billItem->contact_id,
            'allocation_date' => $request->input('allocation_date'),
            'amount' => $request->input('amount'),
            'meta' => [
                'notes' => $request->input('notes'),
            ],
        ]);

        return redirect()
            ->route('vasaccounting.payables.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.payable_allocated')]);
    }

    protected function recentAllocations(int $businessId)
    {
        if (! Schema::hasTable('vas_payable_allocations')) {
            return collect();
        }

        return DB::table('vas_payable_allocations as allocation')
            ->leftJoin('vas_vouchers as bill', 'bill.id', '=', 'allocation.bill_voucher_id')
            ->leftJoin('vas_vouchers as payment', 'payment.id', '=', 'allocation.payment_voucher_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'allocation.contact_id')
            ->where('allocation.business_id', $businessId)
            ->select(
                'allocation.allocation_date',
                'allocation.amount',
                'bill.voucher_no as bill_voucher_no',
                'payment.voucher_no as payment_voucher_no',
                DB::raw("COALESCE(NULLIF(contacts.supplier_business_name, ''), NULLIF(contacts.name, ''), 'Contact') as contact_name")
            )
            ->orderByDesc('allocation.allocation_date')
            ->orderByDesc('allocation.id')
            ->limit(20)
            ->get();
    }
}
