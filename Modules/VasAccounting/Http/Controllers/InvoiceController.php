<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\Request;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;

class InvoiceController extends VasBaseController
{
    public function __construct(protected EnterpriseFinanceReportUtil $enterpriseReportUtil)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $businessId = $this->businessId($request);
        $invoiceRegister = $this->enterpriseReportUtil->invoiceRegister($businessId);
        $salesInvoices = $invoiceRegister->whereIn('voucher_type', ['sales_invoice', 'sales_return'])->values();
        $purchaseInvoices = $invoiceRegister->whereIn('voucher_type', ['purchase_invoice', 'purchase_return', 'expense'])->values();
        $noteCount = $invoiceRegister->whereIn('voucher_type', ['sales_return', 'purchase_return'])->count();
        $issuedEInvoices = $invoiceRegister->filter(fn ($row) => ! empty($row->einvoice_document_no))->count();

        return view('vasaccounting::invoices.index', [
            'invoiceRegister' => $invoiceRegister,
            'salesInvoices' => $salesInvoices,
            'purchaseInvoices' => $purchaseInvoices,
            'summary' => [
                'sales_count' => $salesInvoices->count(),
                'sales_amount' => round((float) $salesInvoices->sum('amount'), 2),
                'purchase_count' => $purchaseInvoices->count(),
                'purchase_amount' => round((float) $purchaseInvoices->sum('amount'), 2),
                'note_count' => $noteCount,
                'issued_einvoices' => $issuedEInvoices,
            ],
        ]);
    }
}
