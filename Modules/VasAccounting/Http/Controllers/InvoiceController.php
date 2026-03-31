<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\InvoiceLayout;
use App\InvoiceScheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Entities\VasBankAccount;
use Modules\VasAccounting\Entities\VasCashbook;
use Modules\VasAccounting\Entities\VasTaxCode;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Http\Requests\NativeDocumentActionRequest;
use Modules\VasAccounting\Http\Requests\StoreNativeInvoiceRequest;
use Modules\VasAccounting\Http\Requests\UpdateNativeInvoiceRequest;
use Modules\VasAccounting\Services\NativeInvoiceService;
use Modules\VasAccounting\Services\PaymentDocumentService;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class InvoiceController extends VasBaseController
{
    public function __construct(
        protected EnterpriseFinanceReportUtil $enterpriseReportUtil,
        protected NativeInvoiceService $nativeInvoiceService,
        protected PaymentDocumentService $paymentDocumentService,
        protected VasAccountingUtil $vasUtil
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $businessId = $this->businessId($request);
        $invoiceRegister = $this->enterpriseReportUtil->invoiceRegister($businessId);
        $salesInvoices = $invoiceRegister->whereIn('voucher_type', ['sales_invoice', 'sales_return', 'sales_credit_note'])->values();
        $purchaseInvoices = $invoiceRegister->whereIn('voucher_type', ['purchase_invoice', 'purchase_return', 'expense', 'purchase_debit_note'])->values();
        $noteCount = $invoiceRegister->whereIn('voucher_type', ['sales_return', 'sales_credit_note', 'purchase_return', 'purchase_debit_note'])->count();
        $issuedEInvoices = $invoiceRegister->filter(fn ($row) => ! empty($row->einvoice_document_no))->count();

        return view('vasaccounting::invoices.index', [
            'invoiceRegister' => $invoiceRegister,
            'salesInvoices' => $salesInvoices,
            'purchaseInvoices' => $purchaseInvoices,
            'nativeInvoices' => $this->nativeInvoiceService->paginateNativeInvoices($businessId, 10),
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

    public function create(Request $request)
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $businessId = $this->businessId($request);

        return view('vasaccounting::invoices.create', $this->formData(
            $businessId,
            null,
            [
                'invoice_kind' => (string) $request->query('invoice_kind', 'purchase_invoice'),
                'document_date' => now()->toDateString(),
                'posting_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'line_items' => [
                    ['account_id' => '', 'description' => '', 'net_amount' => '', 'tax_amount' => '', 'tax_code_id' => ''],
                ],
            ]
        ));
    }

    public function store(StoreNativeInvoiceRequest $request): RedirectResponse
    {
        $validated = $this->normalizeInvoiceRequestPayload($request->validated());
        $invoice = $this->nativeInvoiceService->createDraft($this->businessId($request), $validated, (int) auth()->id());
        $invoice = $this->applyRequestedWorkflowAction($invoice, (string) $request->input('action', 'save_draft'));
        $immediatePayment = $this->createImmediatePaymentIfRequested($invoice, $validated);

        return redirect()
            ->route($immediatePayment ? 'vasaccounting.payment_documents.show' : 'vasaccounting.invoices.show', $immediatePayment?->id ?: $invoice->id)
            ->with('status', ['success' => true, 'msg' => 'Native invoice saved.']);
    }

    public function show(Request $request, int $voucher)
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $invoice = $this->nativeInvoiceService->findNativeInvoice($this->businessId($request), $voucher);

        $isSalesInvoice = $this->isSalesInvoiceKind((string) $invoice->voucher_type);
        $publicToken = $this->nativeInvoiceService->resolvePublicToken($invoice);

        return view('vasaccounting::invoices.show', [
            'invoice' => $invoice,
            'voucher' => $invoice,
            'createPaymentUrl' => route('vasaccounting.payment_documents.create', [
                'payment_kind' => $isSalesInvoice ? 'bank_receipt' : 'bank_payment',
                'settle_voucher_id' => $invoice->id,
                'direction' => $isSalesInvoice ? 'receipt' : 'payment',
            ]),
            'createPaymentLabel' => $isSalesInvoice ? 'Create receipt' : 'Create payment',
            'publicInvoiceUrl' => $isSalesInvoice && $publicToken ? route('show_invoice', ['token' => $publicToken]) : null,
            'publicPaymentUrl' => $isSalesInvoice && $publicToken ? route('invoice_payment', ['token' => $publicToken]) : null,
        ]);
    }

    public function edit(Request $request, int $voucher)
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $businessId = $this->businessId($request);
        $invoice = $this->nativeInvoiceService->findNativeInvoice($businessId, $voucher);
        $invoiceMeta = (array) data_get((array) $invoice->meta, 'invoice', []);
        $snapshotLines = (array) data_get($invoiceMeta, 'line_snapshot', []);

        return view('vasaccounting::invoices.edit', $this->formData(
            $businessId,
            $invoice,
            [
                'invoice_kind' => (string) $invoice->voucher_type,
                'contact_id' => $invoice->contact_id,
                'business_location_id' => $invoice->business_location_id,
                'document_date' => optional($invoice->document_date)->toDateString(),
                'posting_date' => optional($invoice->posting_date)->toDateString(),
                'due_date' => data_get($invoiceMeta, 'due_date'),
                'reference' => $invoice->reference,
                'external_reference' => $invoice->external_reference,
                'description' => $invoice->description,
                'line_items' => collect($snapshotLines)->map(function ($line) {
                    return [
                        'account_id' => data_get($line, 'account_id'),
                        'description' => data_get($line, 'description'),
                        'net_amount' => data_get($line, 'meta.net_amount', max((float) data_get($line, 'debit', 0), (float) data_get($line, 'credit', 0))),
                        'tax_amount' => data_get($line, 'meta.tax_amount', 0),
                        'tax_code_id' => data_get($line, 'meta.tax_code_id'),
                        'product_id' => data_get($line, 'meta.product_id'),
                    ];
                })->filter(fn ($line) => (float) ($line['net_amount'] ?? 0) > 0)->values()->all(),
                'invoice_scheme_id' => data_get($invoiceMeta, 'scheme_id'),
                'invoice_layout_id' => data_get($invoiceMeta, 'layout_id'),
                'public_token' => data_get($invoiceMeta, 'public_token'),
            ]
        ));
    }

    public function update(UpdateNativeInvoiceRequest $request, int $voucher): RedirectResponse
    {
        $validated = $this->normalizeInvoiceRequestPayload($request->validated());
        $invoice = $this->nativeInvoiceService->findNativeInvoice($this->businessId($request), $voucher);
        $invoice = $this->nativeInvoiceService->updateDraft($invoice, $validated, (int) auth()->id());
        $invoice = $this->applyRequestedWorkflowAction($invoice, (string) $request->input('action', 'save_draft'));
        $immediatePayment = $this->createImmediatePaymentIfRequested($invoice, $validated);

        return redirect()
            ->route($immediatePayment ? 'vasaccounting.payment_documents.show' : 'vasaccounting.invoices.show', $immediatePayment?->id ?: $invoice->id)
            ->with('status', ['success' => true, 'msg' => 'Native invoice updated.']);
    }

    public function submit(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $invoice = $this->nativeInvoiceService->findNativeInvoice($this->businessId($request), $voucher);
        $invoice = $this->nativeInvoiceService->submit($invoice, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.invoices.show', $invoice->id)
            ->with('status', ['success' => true, 'msg' => 'Invoice submitted for approval.']);
    }

    public function approve(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $invoice = $this->nativeInvoiceService->findNativeInvoice($this->businessId($request), $voucher);
        $invoice = $this->nativeInvoiceService->approve($invoice, (int) auth()->id(), $request->input('comments'));

        return redirect()
            ->route('vasaccounting.invoices.show', $invoice->id)
            ->with('status', ['success' => true, 'msg' => 'Invoice approved.']);
    }

    public function reject(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $invoice = $this->nativeInvoiceService->findNativeInvoice($this->businessId($request), $voucher);
        $invoice = $this->nativeInvoiceService->reject($invoice, (int) auth()->id(), $request->input('comments'));

        return redirect()
            ->route('vasaccounting.invoices.show', $invoice->id)
            ->with('status', ['success' => true, 'msg' => 'Invoice sent back to draft.']);
    }

    public function cancel(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $invoice = $this->nativeInvoiceService->findNativeInvoice($this->businessId($request), $voucher);
        $invoice = $this->nativeInvoiceService->cancel($invoice, (int) auth()->id(), $request->input('comments'));

        return redirect()
            ->route('vasaccounting.invoices.show', $invoice->id)
            ->with('status', ['success' => true, 'msg' => 'Invoice cancelled.']);
    }

    public function post(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $invoice = $this->nativeInvoiceService->findNativeInvoice($this->businessId($request), $voucher);
        $invoice = $this->nativeInvoiceService->post($invoice, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.invoices.show', $invoice->id)
            ->with('status', ['success' => true, 'msg' => 'Invoice posted.']);
    }

    public function reverse(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.invoices.manage');

        $invoice = $this->nativeInvoiceService->findNativeInvoice($this->businessId($request), $voucher);
        $reversal = $this->nativeInvoiceService->reverse($invoice, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.vouchers.show', $reversal->id)
            ->with('status', ['success' => true, 'msg' => 'Invoice reversed into a journal reversal voucher.']);
    }

    protected function formData(int $businessId, ?VasVoucher $invoice = null, array $prefill = []): array
    {
        $invoiceKind = (string) ($prefill['invoice_kind'] ?? $invoice?->voucher_type ?? 'purchase_invoice');
        $isSalesInvoice = $this->isSalesInvoiceKind($invoiceKind);
        $taxCodes = VasTaxCode::query()
            ->where('is_active', true)
            ->whereIn('direction', ['input', 'output'])
            ->orderBy('direction')
            ->orderBy('code')
            ->get();

        return [
            'invoice' => $invoice,
            'voucher' => $invoice,
            'invoiceKindOptions' => [
                'purchase_invoice' => 'Purchase invoice',
                'purchase_debit_note' => 'Purchase debit note',
                'sales_invoice' => 'Sales invoice',
                'sales_credit_note' => 'Sales credit note',
            ],
            'invoiceKinds' => [
                'purchase_invoice' => 'Purchase invoice',
                'purchase_debit_note' => 'Purchase debit note',
                'sales_invoice' => 'Sales invoice',
                'sales_credit_note' => 'Sales credit note',
            ],
            'customerOptions' => Contact::customersDropdown($businessId, true),
            'supplierOptions' => Contact::suppliersDropdown($businessId, true),
            'contactOptions' => [
                'sales' => Contact::customersDropdown($businessId, true),
                'purchase' => Contact::suppliersDropdown($businessId, true),
            ],
            'accountOptions' => $this->vasUtil->chartOptions($businessId),
            'chartOptions' => $this->vasUtil->chartOptions($businessId),
            'taxCodes' => $taxCodes,
            'inputTaxCodes' => $taxCodes->where('direction', 'input')->values(),
            'outputTaxCodes' => $taxCodes->where('direction', 'output')->values(),
            'invoiceSchemeOptions' => InvoiceScheme::forDropdown($businessId),
            'invoiceLayoutOptions' => InvoiceLayout::forDropdown($businessId),
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'cashbooks' => VasCashbook::query()->where('business_id', $businessId)->orderBy('code')->get(),
            'bankAccounts' => VasBankAccount::query()->where('business_id', $businessId)->orderBy('account_code')->get(),
            'invoiceKind' => $invoiceKind,
            'isSalesInvoiceKind' => $isSalesInvoice,
            'prefill' => $prefill,
        ];
    }

    protected function normalizeInvoiceRequestPayload(array $payload): array
    {
        if (! empty($payload['line_items'])) {
            return $payload;
        }

        $payload['line_items'] = collect((array) ($payload['line_items'] ?? $payload['lines'] ?? []))
            ->map(function (array $line) {
                return [
                    'account_id' => $line['account_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'net_amount' => $line['net_amount'] ?? $line['amount'] ?? null,
                    'tax_amount' => $line['tax_amount'] ?? 0,
                    'tax_code_id' => $line['tax_code_id'] ?? null,
                    'product_id' => $line['product_id'] ?? null,
                ];
            })
            ->all();

        return $payload;
    }

    protected function applyRequestedWorkflowAction(VasVoucher $invoice, string $action): VasVoucher
    {
        return match ($action) {
            'submit' => $this->nativeInvoiceService->submit($invoice, (int) auth()->id()),
            'save_and_post' => $this->nativeInvoiceService->post($invoice, (int) auth()->id()),
            default => $invoice,
        };
    }

    protected function createImmediatePaymentIfRequested(VasVoucher $invoice, array $validated): ?VasVoucher
    {
        if (! $this->isEligibleForImmediateSettlement($invoice)) {
            return null;
        }

        $amount = round((float) data_get($validated, 'immediate_payment.amount', 0), 4);
        if ($amount <= 0) {
            return null;
        }
        $isSalesInvoice = $this->isSalesInvoiceKind((string) $invoice->voucher_type);
        $defaultPaymentKind = $isSalesInvoice ? 'bank_receipt' : 'bank_payment';
        $requestedPaymentKind = (string) data_get($validated, 'immediate_payment.payment_kind', $defaultPaymentKind);
        if ($isSalesInvoice && ! in_array($requestedPaymentKind, ['cash_receipt', 'bank_receipt'], true)) {
            $requestedPaymentKind = $defaultPaymentKind;
        }
        if (! $isSalesInvoice && ! in_array($requestedPaymentKind, ['cash_payment', 'bank_payment'], true)) {
            $requestedPaymentKind = $defaultPaymentKind;
        }
        $defaultInstrument = $isSalesInvoice ? 'bank_transfer' : 'bank_transfer';
        $paidOn = data_get($validated, 'immediate_payment.paid_on', optional($invoice->posting_date)->toDateString() ?: now()->toDateString());

        $payment = $this->paymentDocumentService->createDraft((int) $invoice->business_id, [
            'payment_kind' => $requestedPaymentKind,
            'contact_id' => $invoice->contact_id,
            'business_location_id' => $invoice->business_location_id,
            'document_date' => $paidOn,
            'posting_date' => $paidOn,
            'currency_code' => $invoice->currency_code,
            'exchange_rate' => $invoice->exchange_rate,
            'amount' => $amount,
            'payment_instrument' => data_get($validated, 'immediate_payment.payment_method', $defaultInstrument),
            'cashbook_id' => data_get($validated, 'immediate_payment.cashbook_id'),
            'bank_account_id' => data_get($validated, 'immediate_payment.bank_account_id'),
            'external_reference' => data_get($validated, 'immediate_payment.external_reference'),
            'description' => ($isSalesInvoice ? 'Immediate receipt for ' : 'Immediate payment for ') . $invoice->voucher_no,
            'notes' => data_get($validated, 'immediate_payment.notes'),
            'legacy_transaction_id' => $invoice->transaction_id,
            'settlement_targets' => [[
                'target_voucher_id' => $invoice->id,
                'amount' => $amount,
                'legacy_transaction_id' => $invoice->transaction_id,
            ]],
        ], (int) auth()->id());

        return $this->paymentDocumentService->post($payment, (int) auth()->id());
    }

    protected function isSalesInvoiceKind(string $invoiceKind): bool
    {
        return in_array($invoiceKind, ['sales_invoice', 'sales_credit_note'], true);
    }

    protected function isEligibleForImmediateSettlement(VasVoucher $invoice): bool
    {
        if ($invoice->status !== 'posted') {
            return false;
        }

        return in_array((string) $invoice->voucher_type, ['purchase_invoice', 'sales_invoice'], true);
    }
}
