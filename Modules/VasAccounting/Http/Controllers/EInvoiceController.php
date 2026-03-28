<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Http\Requests\EInvoiceActionRequest;
use Modules\VasAccounting\Entities\VasEInvoiceDocument;
use Modules\VasAccounting\Entities\VasEInvoiceLog;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Services\EInvoiceAdapterManager;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class EInvoiceController extends VasBaseController
{
    public function __construct(
        protected EInvoiceAdapterManager $adapterManager,
        protected VasAccountingUtil $vasUtil
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.einvoice.manage');

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $documents = VasEInvoiceDocument::query()
            ->with(['logs' => fn ($query) => $query->latest()->limit(5)])
            ->where('business_id', $businessId)
            ->latest()
            ->get();

        $recentVouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('status', 'posted')
            ->whereIn('voucher_type', ['sales_invoice', 'sales_return', 'purchase_invoice', 'purchase_return', 'expense'])
            ->latest('posting_date')
            ->take(15)
            ->get();

        return view('vasaccounting::einvoices.index', [
            'documents' => $documents,
            'recentVouchers' => $recentVouchers,
            'recentLogs' => VasEInvoiceLog::query()->where('business_id', $businessId)->latest()->take(25)->get(),
            'providerOptions' => array_keys((array) config('vasaccounting.einvoice_adapters', [])),
            'defaultProvider' => (string) (((array) $settings->einvoice_settings)['provider'] ?? 'sandbox'),
        ]);
    }

    public function issue(EInvoiceActionRequest $request, int $voucher): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $voucherModel = VasVoucher::query()
            ->where('business_id', $businessId)
            ->findOrFail($voucher);

        $provider = $this->resolveProvider($request);
        $adapter = $this->adapterManager->resolve($provider);
        $result = $adapter->issue([
            'voucher_id' => $voucherModel->id,
            'reference' => $voucherModel->voucher_no,
            'notes' => $request->input('notes'),
        ]);

        $document = VasEInvoiceDocument::updateOrCreate(
            [
                'business_id' => $voucherModel->business_id,
                'voucher_id' => $voucherModel->id,
            ],
            [
                'transaction_id' => $voucherModel->transaction_id,
                'provider' => $result['provider'] ?? $provider,
                'provider_document_id' => $result['provider_document_id'] ?? null,
                'document_no' => $result['document_no'] ?? null,
                'serial_no' => $result['serial_no'] ?? null,
                'status' => $result['status'] ?? 'issued',
                'issued_at' => $result['issued_at'] ?? now(),
                'source_payload' => ['voucher_id' => $voucherModel->id],
                'response_payload' => $result['response_payload'] ?? $result,
                'last_synced_at' => now(),
            ]
        );

        $this->logDocumentAction($document, 'issue', ['voucher_id' => $voucherModel->id, 'provider' => $provider], $result);

        return redirect()
            ->route('vasaccounting.einvoices.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.einvoice_issued')]);
    }

    public function sync(EInvoiceActionRequest $request, int $document): RedirectResponse
    {
        $documentModel = VasEInvoiceDocument::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($document);

        $adapter = $this->adapterManager->resolve($documentModel->provider);
        $result = $adapter->syncStatus([
            'provider_document_id' => $documentModel->provider_document_id,
            'status' => $documentModel->status,
        ]);

        $documentModel->status = $result['status'] ?? $documentModel->status;
        $documentModel->last_synced_at = $result['last_synced_at'] ?? now();
        $documentModel->response_payload = $result['response_payload'] ?? $result;
        $documentModel->save();

        $this->logDocumentAction($documentModel, 'sync', ['provider_document_id' => $documentModel->provider_document_id], $result);

        return redirect()
            ->route('vasaccounting.einvoices.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.einvoice_synced')]);
    }

    public function cancel(EInvoiceActionRequest $request, int $document): RedirectResponse
    {
        $documentModel = $this->loadDocument($request, $document);
        $adapter = $this->adapterManager->resolve($this->resolveProvider($request, $documentModel->provider));
        $result = $adapter->cancel([
            'provider_document_id' => $documentModel->provider_document_id,
            'notes' => $request->input('notes'),
        ]);

        $this->applyDocumentResult($documentModel, 'cancel', $result);

        return redirect()
            ->route('vasaccounting.einvoices.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.einvoice_cancelled')]);
    }

    public function correct(EInvoiceActionRequest $request, int $document): RedirectResponse
    {
        $documentModel = $this->loadDocument($request, $document);
        $adapter = $this->adapterManager->resolve($this->resolveProvider($request, $documentModel->provider));
        $result = $adapter->correct([
            'provider_document_id' => $documentModel->provider_document_id,
            'notes' => $request->input('notes'),
        ]);

        $this->applyDocumentResult($documentModel, 'correct', $result);

        return redirect()
            ->route('vasaccounting.einvoices.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.einvoice_corrected')]);
    }

    public function replace(EInvoiceActionRequest $request, int $document): RedirectResponse
    {
        $documentModel = $this->loadDocument($request, $document);
        $adapter = $this->adapterManager->resolve($this->resolveProvider($request, $documentModel->provider));
        $result = $adapter->replace([
            'provider_document_id' => $documentModel->provider_document_id,
            'notes' => $request->input('notes'),
        ]);

        $this->applyDocumentResult($documentModel, 'replace', $result);

        return redirect()
            ->route('vasaccounting.einvoices.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.einvoice_replaced')]);
    }

    protected function loadDocument(EInvoiceActionRequest $request, int $document): VasEInvoiceDocument
    {
        return VasEInvoiceDocument::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($document);
    }

    protected function resolveProvider(EInvoiceActionRequest $request, ?string $fallback = null): string
    {
        if ($request->filled('provider')) {
            return (string) $request->input('provider');
        }

        if (! empty($fallback)) {
            return (string) $fallback;
        }

        $settings = $this->vasUtil->getOrCreateBusinessSettings($this->businessId($request));

        return (string) (((array) $settings->einvoice_settings)['provider'] ?? 'sandbox');
    }

    protected function applyDocumentResult(VasEInvoiceDocument $document, string $action, array $result): void
    {
        $document->provider = $result['provider'] ?? $document->provider;
        $document->provider_document_id = $result['provider_document_id'] ?? $document->provider_document_id;
        $document->document_no = $result['document_no'] ?? $document->document_no;
        $document->serial_no = $result['serial_no'] ?? $document->serial_no;
        $document->status = $result['status'] ?? $document->status;
        $document->cancelled_at = $action === 'cancel' ? ($result['cancelled_at'] ?? now()) : $document->cancelled_at;
        $document->last_synced_at = $result['last_synced_at'] ?? now();
        $document->response_payload = $result['response_payload'] ?? $result;
        $document->save();

        $this->logDocumentAction($document, $action, ['provider_document_id' => $document->provider_document_id], $result);
    }

    protected function logDocumentAction(VasEInvoiceDocument $document, string $action, array $requestPayload, array $responsePayload): void
    {
        VasEInvoiceLog::create([
            'business_id' => $document->business_id,
            'einvoice_document_id' => $document->id,
            'action' => $action,
            'status' => $document->status,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
            'created_by' => auth()->id(),
        ]);
    }
}
