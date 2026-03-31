<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Entities\VasBankAccount;
use Modules\VasAccounting\Entities\VasCashbook;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Http\Requests\NativeDocumentActionRequest;
use Modules\VasAccounting\Http\Requests\StorePaymentDocumentRequest;
use Modules\VasAccounting\Http\Requests\UpdatePaymentDocumentRequest;
use Modules\VasAccounting\Services\PaymentDocumentService;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class PaymentDocumentController extends VasBaseController
{
    public function __construct(
        protected PaymentDocumentService $paymentDocumentService,
        protected EnterpriseFinanceReportUtil $enterpriseReportUtil,
        protected VasAccountingUtil $vasUtil
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);
        $paymentDocuments = $this->paymentDocumentService->paginateNativePayments($businessId);

        return view('vasaccounting::payment_documents.index', [
            'paymentDocuments' => $paymentDocuments,
            'documents' => $paymentDocuments,
            'payableOpenItems' => $this->enterpriseReportUtil->payableOpenItems($businessId)->take(12),
            'receivableOpenItems' => $this->enterpriseReportUtil->receivableOpenItems($businessId)->take(12),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);
        $paymentKind = (string) $request->query('payment_kind', 'bank_payment');

        return view('vasaccounting::payment_documents.create', $this->formData(
            $businessId,
            $paymentKind,
            null,
            $this->prefillFromSettlementTarget($businessId, $request)
        ));
    }

    public function store(StorePaymentDocumentRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $paymentDocument = $this->paymentDocumentService->createDraft($businessId, $request->validated(), (int) auth()->id());
        $paymentDocument = $this->applyRequestedWorkflowAction($paymentDocument, (string) $request->input('action', 'save_draft'));

        return redirect()
            ->route('vasaccounting.payment_documents.show', $paymentDocument->id)
            ->with('status', ['success' => true, 'msg' => 'Native payment document saved.']);
    }

    public function show(Request $request, int $voucher)
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $paymentDocument = $this->paymentDocumentService->findNativePayment($this->businessId($request), $voucher);

        return view('vasaccounting::payment_documents.show', [
            'paymentDocument' => $paymentDocument,
            'voucher' => $paymentDocument,
            'createReceiptUrl' => route('vasaccounting.payment_documents.create', [
                'payment_kind' => str_contains((string) $paymentDocument->voucher_type, 'receipt') ? $paymentDocument->voucher_type : 'bank_receipt',
            ]),
        ]);
    }

    public function edit(Request $request, int $voucher)
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);
        $paymentDocument = $this->paymentDocumentService->findNativePayment($businessId, $voucher);
        $paymentMeta = (array) data_get((array) $paymentDocument->meta, 'payment', []);

        return view('vasaccounting::payment_documents.edit', $this->formData(
            $businessId,
            (string) $paymentDocument->voucher_type,
            $paymentDocument,
            [
                'contact_id' => $paymentDocument->contact_id,
                'business_location_id' => $paymentDocument->business_location_id,
                'cashbook_id' => data_get($paymentMeta, 'cashbook_id'),
                'bank_account_id' => data_get($paymentMeta, 'bank_account_id'),
                'payment_instrument' => data_get($paymentMeta, 'instrument'),
                'amount' => max((float) $paymentDocument->total_debit, (float) $paymentDocument->total_credit),
                'reference' => $paymentDocument->reference,
                'external_reference' => $paymentDocument->external_reference,
                'description' => $paymentDocument->description,
                'posting_date' => optional($paymentDocument->posting_date)->toDateString(),
                'document_date' => optional($paymentDocument->document_date)->toDateString(),
                'settlement_targets' => (array) data_get($paymentMeta, 'settlement_targets', []),
            ]
        ));
    }

    public function update(UpdatePaymentDocumentRequest $request, int $voucher): RedirectResponse
    {
        $paymentDocument = $this->paymentDocumentService->findNativePayment($this->businessId($request), $voucher);
        $paymentDocument = $this->paymentDocumentService->updateDraft($paymentDocument, $request->validated(), (int) auth()->id());
        $paymentDocument = $this->applyRequestedWorkflowAction($paymentDocument, (string) $request->input('action', 'save_draft'));

        return redirect()
            ->route('vasaccounting.payment_documents.show', $paymentDocument->id)
            ->with('status', ['success' => true, 'msg' => 'Native payment document updated.']);
    }

    public function submit(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $paymentDocument = $this->paymentDocumentService->findNativePayment($this->businessId($request), $voucher);
        $paymentDocument = $this->paymentDocumentService->submit($paymentDocument, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.payment_documents.show', $paymentDocument->id)
            ->with('status', ['success' => true, 'msg' => 'Payment document submitted for approval.']);
    }

    public function approve(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $paymentDocument = $this->paymentDocumentService->findNativePayment($this->businessId($request), $voucher);
        $paymentDocument = $this->paymentDocumentService->approve($paymentDocument, (int) auth()->id(), $request->input('comments'));

        return redirect()
            ->route('vasaccounting.payment_documents.show', $paymentDocument->id)
            ->with('status', ['success' => true, 'msg' => 'Payment document approved.']);
    }

    public function reject(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $paymentDocument = $this->paymentDocumentService->findNativePayment($this->businessId($request), $voucher);
        $paymentDocument = $this->paymentDocumentService->reject($paymentDocument, (int) auth()->id(), $request->input('comments'));

        return redirect()
            ->route('vasaccounting.payment_documents.show', $paymentDocument->id)
            ->with('status', ['success' => true, 'msg' => 'Payment document sent back to draft.']);
    }

    public function cancel(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $paymentDocument = $this->paymentDocumentService->findNativePayment($this->businessId($request), $voucher);
        $paymentDocument = $this->paymentDocumentService->cancel($paymentDocument, (int) auth()->id(), $request->input('comments'));

        return redirect()
            ->route('vasaccounting.payment_documents.show', $paymentDocument->id)
            ->with('status', ['success' => true, 'msg' => 'Payment document cancelled.']);
    }

    public function post(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $paymentDocument = $this->paymentDocumentService->findNativePayment($this->businessId($request), $voucher);
        $paymentDocument = $this->paymentDocumentService->post($paymentDocument, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.payment_documents.show', $paymentDocument->id)
            ->with('status', ['success' => true, 'msg' => 'Payment document posted.']);
    }

    public function reverse(NativeDocumentActionRequest $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $paymentDocument = $this->paymentDocumentService->findNativePayment($this->businessId($request), $voucher);
        $reversal = $this->paymentDocumentService->reverse($paymentDocument, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.vouchers.show', $reversal->id)
            ->with('status', ['success' => true, 'msg' => 'Payment document reversed into a journal reversal voucher.']);
    }

    protected function formData(int $businessId, string $paymentKind, ?VasVoucher $paymentDocument = null, array $prefill = []): array
    {
        $contactOptionsFlat = Contact::query()
            ->where('business_id', $businessId)
            ->whereIn('type', ['customer', 'supplier', 'both'])
            ->orderByRaw("COALESCE(NULLIF(supplier_business_name, ''), name)")
            ->get()
            ->mapWithKeys(fn (Contact $contact) => [$contact->id => ($contact->supplier_business_name ?: $contact->name ?: ('Contact #' . $contact->id))])
            ->all();

        return [
            'paymentDocument' => $paymentDocument,
            'voucher' => $paymentDocument,
            'paymentKindOptions' => [
                'cash_receipt' => 'Cash receipt',
                'cash_payment' => 'Cash payment',
                'bank_receipt' => 'Bank receipt',
                'bank_payment' => 'Bank payment',
            ],
            'paymentKinds' => [
                'cash_receipt' => 'Cash receipt',
                'cash_payment' => 'Cash payment',
                'bank_receipt' => 'Bank receipt',
                'bank_payment' => 'Bank payment',
            ],
            'contactOptions' => [
                'payment' => Contact::suppliersDropdown($businessId, true),
                'receipt' => Contact::customersDropdown($businessId, true),
            ],
            'contactOptionsFlat' => $contactOptionsFlat,
            'cashbooks' => VasCashbook::query()->where('business_id', $businessId)->orderBy('code')->get(),
            'bankAccounts' => VasBankAccount::query()->where('business_id', $businessId)->orderBy('account_code')->get(),
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'selectedPaymentKind' => $paymentKind,
            'paymentKind' => $paymentKind,
            'payableOpenItems' => $this->enterpriseReportUtil->payableOpenItems($businessId),
            'receivableOpenItems' => $this->enterpriseReportUtil->receivableOpenItems($businessId),
            'prefill' => $prefill,
        ];
    }

    protected function prefillFromSettlementTarget(int $businessId, Request $request): array
    {
        $targetVoucherId = (int) $request->query('settle_voucher_id', 0);
        if ($targetVoucherId <= 0) {
            return [
                'posting_date' => now()->toDateString(),
                'document_date' => now()->toDateString(),
                'settlement_targets' => [],
            ];
        }

        $targetVoucher = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('status', 'posted')
            ->find($targetVoucherId);
        if (! $targetVoucher) {
            return [
                'posting_date' => now()->toDateString(),
                'document_date' => now()->toDateString(),
                'settlement_targets' => [],
            ];
        }

        $direction = (string) $request->query('direction', 'payment');
        $openItems = $direction === 'receipt'
            ? $this->enterpriseReportUtil->receivableOpenItems($businessId)->keyBy('id')
            : $this->enterpriseReportUtil->payableOpenItems($businessId)->keyBy('id');
        $openItem = $openItems->get($targetVoucherId);

        return [
            'contact_id' => $targetVoucher->contact_id,
            'business_location_id' => $targetVoucher->business_location_id,
            'amount' => $openItem ? (float) $openItem->outstanding_amount : max((float) $targetVoucher->total_debit, (float) $targetVoucher->total_credit),
            'reference' => null,
            'external_reference' => null,
            'description' => null,
            'posting_date' => now()->toDateString(),
            'document_date' => now()->toDateString(),
            'settlement_targets' => [[
                'target_voucher_id' => (int) $targetVoucher->id,
                'amount' => $openItem ? (float) $openItem->outstanding_amount : max((float) $targetVoucher->total_debit, (float) $targetVoucher->total_credit),
            ]],
        ];
    }

    protected function applyRequestedWorkflowAction(VasVoucher $paymentDocument, string $action): VasVoucher
    {
        return match ($action) {
            'submit' => $this->paymentDocumentService->submit($paymentDocument, (int) auth()->id()),
            'save_and_post' => $this->paymentDocumentService->post($paymentDocument, (int) auth()->id()),
            default => $paymentDocument,
        };
    }
}
