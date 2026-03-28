<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\VasAccounting\Entities\VasReceivableAllocation;
use Modules\VasAccounting\Http\Requests\StoreReceivableAllocationRequest;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;

class ReceivableController extends VasBaseController
{
    public function __construct(protected EnterpriseFinanceReportUtil $enterpriseReportUtil)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.receivables.manage');

        $businessId = $this->businessId($request);

        return view('vasaccounting::receivables.index', [
            'aging' => $this->enterpriseReportUtil->receivableAging($businessId),
            'openItems' => $this->enterpriseReportUtil->receivableOpenItems($businessId),
            'receiptItems' => $this->enterpriseReportUtil->receivableReceiptItems($businessId),
            'recentAllocations' => $this->recentAllocations($businessId),
        ]);
    }

    public function storeAllocation(StoreReceivableAllocationRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $invoiceItem = $this->enterpriseReportUtil->receivableOpenItems($businessId)
            ->first(fn ($item) => (int) $item->id === (int) $request->input('invoice_voucher_id'));
        $paymentItem = $this->enterpriseReportUtil->receivableReceiptItems($businessId)
            ->first(fn ($item) => (int) $item->id === (int) $request->input('payment_voucher_id'));

        if (! $invoiceItem || ! $paymentItem) {
            throw ValidationException::withMessages([
                'amount' => 'Choose a posted invoice and a receipt with available balance.',
            ]);
        }

        $maxAllocatable = min((float) $invoiceItem->outstanding_amount, (float) $paymentItem->available_amount);
        if ((float) $request->input('amount') > $maxAllocatable + 0.0001) {
            throw ValidationException::withMessages([
                'amount' => 'Allocation amount exceeds the invoice outstanding or receipt available balance.',
            ]);
        }

        VasReceivableAllocation::create([
            'business_id' => $businessId,
            'voucher_id' => (int) $request->input('payment_voucher_id'),
            'invoice_voucher_id' => (int) $request->input('invoice_voucher_id'),
            'payment_voucher_id' => (int) $request->input('payment_voucher_id'),
            'contact_id' => $request->input('contact_id') ?: $invoiceItem->contact_id,
            'allocation_date' => $request->input('allocation_date'),
            'amount' => $request->input('amount'),
            'meta' => [
                'notes' => $request->input('notes'),
            ],
        ]);

        return redirect()
            ->route('vasaccounting.receivables.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.receivable_allocated')]);
    }

    protected function recentAllocations(int $businessId)
    {
        if (! Schema::hasTable('vas_receivable_allocations')) {
            return collect();
        }

        return DB::table('vas_receivable_allocations as allocation')
            ->leftJoin('vas_vouchers as invoice', 'invoice.id', '=', 'allocation.invoice_voucher_id')
            ->leftJoin('vas_vouchers as payment', 'payment.id', '=', 'allocation.payment_voucher_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'allocation.contact_id')
            ->where('allocation.business_id', $businessId)
            ->select(
                'allocation.allocation_date',
                'allocation.amount',
                'invoice.voucher_no as invoice_voucher_no',
                'payment.voucher_no as payment_voucher_no',
                DB::raw("COALESCE(NULLIF(contacts.supplier_business_name, ''), NULLIF(contacts.name, ''), 'Contact') as contact_name")
            )
            ->orderByDesc('allocation.allocation_date')
            ->orderByDesc('allocation.id')
            ->limit(20)
            ->get();
    }
}
