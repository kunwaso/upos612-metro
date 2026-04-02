<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Application\DTOs\DocumentCreateData;
use Modules\VasAccounting\Application\DTOs\PostingContext;
use Modules\VasAccounting\Application\DTOs\ReversalContext;
use Modules\VasAccounting\Contracts\FinanceDocumentServiceInterface;
use Modules\VasAccounting\Contracts\PostingRuleEngineInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchRun;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasTaxCode;
use Modules\VasAccounting\Http\Requests\FinanceDocumentActionRequest;
use Modules\VasAccounting\Http\Requests\StoreProcurementFinanceDocumentRequest;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class ProcurementController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected FinanceDocumentServiceInterface $financeDocumentService,
        protected PostingRuleEngineInterface $postingRuleEngine
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $closeScope = $this->closeScope($request, $businessId);
        $workspaceFocus = $this->workspaceFocus($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['p2p_v2'] ?? false) === false) {
            abort(404);
        }

        $allDocuments = Schema::hasTable('vas_fin_documents')
            ? FinanceDocument::query()
                ->with(['approvalInstances.steps', 'parentLinks.parentDocument', 'childLinks.childDocument', 'matchRuns.exceptions'])
                ->where('business_id', $businessId)
                ->whereIn('document_type', $this->supportedDocumentTypes())
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->when($closeScope['start_date'] || $closeScope['end_date'], fn ($query) => $this->applyDocumentDateScope($query, $closeScope['start_date'], $closeScope['end_date']))
                ->latest('document_date')
                ->latest('id')
                ->get()
            : collect();

        $procurementDocuments = match ($workspaceFocus) {
            'pending_documents' => $allDocuments->whereIn('workflow_status', ['draft', 'submitted', 'approved'])->values(),
            'receiving_queue' => $allDocuments
                ->where('document_type', 'purchase_order')
                ->whereIn('workflow_status', ['approved', 'ordered', 'partially_received'])
                ->values(),
            'discrepancy_queue' => $allDocuments
                ->filter(fn (FinanceDocument $document) => $document->document_type === 'supplier_invoice' && $this->latestMatchExceptionCount($document) > 0)
                ->values(),
            'pending_matching' => $allDocuments
                ->filter(function (FinanceDocument $document) {
                    if ($document->document_type !== 'supplier_invoice') {
                        return false;
                    }

                    return $document->workflow_status === 'approved'
                        || (int) data_get($document->meta, 'matching.blocking_exception_count', 0) > 0
                        || (int) data_get($document->meta, 'matching.warning_count', 0) > 0;
                })
                ->values(),
            default => $allDocuments->take(25)->values(),
        };

        $summary = [
            'documents' => $allDocuments->count(),
            'pending_documents' => $allDocuments->whereIn('workflow_status', ['draft', 'submitted', 'approved'])->count(),
            'receiving_queue' => $allDocuments
                ->where('document_type', 'purchase_order')
                ->whereIn('workflow_status', ['approved', 'ordered', 'partially_received'])
                ->count(),
            'pending_matching' => $allDocuments
                ->filter(fn (FinanceDocument $document) => $document->document_type === 'supplier_invoice' && (
                    $document->workflow_status === 'approved'
                    || (int) data_get($document->meta, 'matching.blocking_exception_count', 0) > 0
                    || (int) data_get($document->meta, 'matching.warning_count', 0) > 0
                ))
                ->count(),
            'open_discrepancies' => $allDocuments
                ->where('document_type', 'supplier_invoice')
                ->sum(fn (FinanceDocument $document) => $this->latestMatchExceptionCount($document)),
            'posted_documents' => $allDocuments->where('workflow_status', 'posted')->count(),
            'gross_amount' => round((float) $allDocuments->sum(fn (FinanceDocument $document) => (float) $document->gross_amount), 4),
        ];

        $discrepancyQueue = $this->discrepancyQueueItems($allDocuments);
        $discrepancySummary = [
            'total' => $discrepancyQueue->count(),
            'blocking' => $discrepancyQueue->where('severity', 'blocking')->count(),
            'warning' => $discrepancyQueue->where('severity', 'warning')->count(),
            'documents' => $discrepancyQueue->pluck('document_id')->unique()->count(),
        ];

        $documentTypeCounts = collect($this->supportedDocumentTypes())
            ->mapWithKeys(fn (string $type) => [$type => $allDocuments->where('document_type', $type)->count()]);

        $parentDocumentOptions = Schema::hasTable('vas_fin_documents')
            ? FinanceDocument::query()
                ->where('business_id', $businessId)
                ->whereIn('document_type', $this->supportedDocumentTypes())
                ->whereNotIn('workflow_status', ['cancelled', 'reversed'])
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->orderByDesc('document_date')
                ->orderByDesc('id')
                ->take(150)
                ->get(['id', 'document_no', 'document_type', 'workflow_status'])
            : collect();

        return view('vasaccounting::procurement.index', [
            'summary' => $summary,
            'documentTypeCounts' => $documentTypeCounts,
            'procurementDocuments' => $procurementDocuments,
            'discrepancyQueue' => $workspaceFocus === 'discrepancy_queue' ? $discrepancyQueue : $discrepancyQueue->take(12)->values(),
            'discrepancySummary' => $discrepancySummary,
            'workspaceFocus' => $workspaceFocus,
            'closePeriod' => $closeScope['period'],
            'selectedLocationId' => $selectedLocationId,
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'supplierOptions' => Contact::suppliersDropdown($businessId, true, true),
            'productOptions' => Product::query()
                ->where('business_id', $businessId)
                ->where('is_inactive', 0)
                ->orderBy('name')
                ->limit(300)
                ->get(['id', 'name'])
                ->mapWithKeys(fn ($product) => [(int) $product->id => $product->name]),
            'taxCodeOptions' => Schema::hasTable('vas_tax_codes')
                ? VasTaxCode::query()->where('business_id', $businessId)->orderBy('code')->get(['id', 'code', 'name'])
                : collect(),
            'chartOptions' => $this->vasUtil->chartOptions($businessId),
            'parentDocumentOptions' => $parentDocumentOptions,
            'supportedDocumentTypes' => $this->supportedDocumentTypes(),
        ]);
    }

    public function store(StoreProcurementFinanceDocumentRequest $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        $businessId = $this->businessId($request);
        $validated = $request->validated();
        $documentType = (string) $validated['document_type'];
        $quantity = (float) $validated['quantity'];
        $unitPrice = (float) $validated['unit_price'];
        $lineAmount = round($quantity * $unitPrice, 4);
        $taxAmount = round((float) ($validated['tax_amount'] ?? 0), 4);
        $grossAmount = round($lineAmount + $taxAmount, 4);

        $links = [];
        if (! empty($validated['parent_document_id'])) {
            $links[] = [
                'parent_document_id' => (int) $validated['parent_document_id'],
                'link_type' => 'source_document',
                'meta' => ['source' => 'procurement_workspace'],
            ];
        }

        try {
            $document = $this->financeDocumentService->create(new DocumentCreateData(
                [
                    'business_id' => $businessId,
                    'document_family' => $this->documentFamilyFor($documentType),
                    'document_type' => $documentType,
                    'document_no' => $validated['document_no'],
                    'external_reference' => $validated['external_reference'] ?? null,
                    'counterparty_type' => in_array($documentType, ['purchase_order', 'goods_receipt', 'supplier_invoice'], true) ? 'supplier' : null,
                    'counterparty_id' => $validated['counterparty_id'] ?? null,
                    'business_location_id' => $validated['business_location_id'] ?? null,
                    'currency_code' => config('vasaccounting.book_currency', 'VND'),
                    'exchange_rate' => 1,
                    'document_date' => $validated['document_date'],
                    'posting_date' => $validated['posting_date'] ?? $validated['document_date'],
                    'workflow_status' => 'draft',
                    'accounting_status' => 'not_ready',
                    'gross_amount' => $grossAmount,
                    'tax_amount' => $taxAmount,
                    'net_amount' => $lineAmount,
                    'open_amount' => 0,
                    'meta' => [
                        'procurement' => [
                            'parent_document_id' => $validated['parent_document_id'] ?? null,
                            'source_type' => $documentType,
                            'workspace' => 'procurement',
                        ],
                    ],
                ],
                [[
                    'line_type' => $this->lineTypeFor($documentType),
                    'product_id' => $validated['product_id'] ?? null,
                    'business_location_id' => $validated['business_location_id'] ?? null,
                    'tax_code_id' => $validated['tax_code_id'] ?? null,
                    'description' => $validated['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_amount' => $lineAmount,
                    'tax_amount' => $taxAmount,
                    'gross_amount' => $grossAmount,
                    'debit_account_id' => $validated['debit_account_id'] ?? null,
                    'credit_account_id' => $validated['credit_account_id'] ?? null,
                    'tax_account_id' => $validated['tax_account_id'] ?? null,
                    'dimensions' => array_filter([
                        'business_location_id' => $validated['business_location_id'] ?? null,
                        'contact_id' => $validated['counterparty_id'] ?? null,
                        'product_id' => $validated['product_id'] ?? null,
                    ], fn ($value) => ! is_null($value) && $value !== ''),
                    'payload' => array_filter([
                        'tax_entry_side' => $validated['tax_entry_side'] ?? 'debit',
                        'source_document_type' => $documentType,
                    ], fn ($value) => ! is_null($value) && $value !== ''),
                ]],
                $links
            ));

            return $this->redirectBackToProcurement($request, ['success' => true, 'msg' => __('vasaccounting::lang.procurement_document_saved', ['document' => $document->document_no])]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function submit(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        try {
            $documentModel = $this->procurementDocumentOrFail($this->businessId($request), $document);
            $this->financeDocumentService->submit($documentModel->id, $this->actionContext($request, 'Procurement document submitted'));

            return $this->redirectBackToProcurement($request, ['success' => true, 'msg' => __('vasaccounting::lang.procurement_document_submitted')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function approve(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        try {
            $documentModel = $this->procurementDocumentOrFail($this->businessId($request), $document);
            $documentModel = $this->financeDocumentService->approve($documentModel->id, $this->actionContext($request, 'Procurement document approved'));

            return $this->redirectBackToProcurement($request, [
                'success' => true,
                'msg' => __(
                    $documentModel->workflow_status === 'approved'
                        ? 'vasaccounting::lang.procurement_document_approved'
                        : 'vasaccounting::lang.procurement_document_approval_progressed'
                ),
            ]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function reject(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        $reason = trim((string) $request->input('reason', ''));
        if ($reason === '') {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => __('vasaccounting::lang.procurement_document_rejection_reason_required')]);
        }

        try {
            $documentModel = $this->procurementDocumentOrFail($this->businessId($request), $document);
            $this->financeDocumentService->reject($documentModel->id, $this->actionContext($request, 'Procurement document rejected'));

            return $this->redirectBackToProcurement($request, ['success' => true, 'msg' => __('vasaccounting::lang.procurement_document_rejected')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function fulfill(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        $completionState = (string) $request->input('completion_state', '');
        if ($completionState === '') {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => 'A completion state is required for this workflow action.']);
        }

        try {
            $documentModel = $this->procurementDocumentOrFail($this->businessId($request), $document);
            $context = $this->actionContext($request, 'Procurement workflow updated', ['completion_state' => $completionState]);
            $this->financeDocumentService->fulfill($documentModel->id, $context);

            return $this->redirectBackToProcurement($request, ['success' => true, 'msg' => __('vasaccounting::lang.procurement_document_fulfilled')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function match(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        try {
            $documentModel = $this->procurementDocumentOrFail($this->businessId($request), $document);
            $this->financeDocumentService->match($documentModel->id, $this->actionContext($request, 'Supplier invoice matched'));

            return $this->redirectBackToProcurement($request, ['success' => true, 'msg' => __('vasaccounting::lang.procurement_document_matched')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function post(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        try {
            $documentModel = $this->procurementDocumentOrFail($this->businessId($request), $document);
            $this->postingRuleEngine->post(
                $documentModel,
                (string) $request->input('event_type', 'post'),
                new PostingContext(
                    (int) auth()->id(),
                    $this->businessId($request),
                    $request->input('reason') ?: 'Procurement document posted',
                    $request->input('request_id') ?: (string) Str::uuid(),
                    $request->ip(),
                    $request->userAgent(),
                    (array) $request->input('meta', [])
                )
            );

            return $this->redirectBackToProcurement($request, ['success' => true, 'msg' => __('vasaccounting::lang.procurement_document_posted')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function close(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        try {
            $documentModel = $this->procurementDocumentOrFail($this->businessId($request), $document);
            $this->financeDocumentService->close($documentModel->id, $this->actionContext($request, 'Procurement document closed'));

            return $this->redirectBackToProcurement($request, ['success' => true, 'msg' => __('vasaccounting::lang.procurement_document_closed')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function reverse(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        try {
            $documentModel = $this->procurementDocumentOrFail($this->businessId($request), $document);
            $this->postingRuleEngine->reverse(
                $documentModel,
                (string) $request->input('event_type', 'post'),
                new ReversalContext(
                    (int) auth()->id(),
                    $this->businessId($request),
                    $request->input('reason') ?: 'Procurement document reversed',
                    $request->input('request_id') ?: (string) Str::uuid(),
                    $request->ip(),
                    $request->userAgent(),
                    (array) $request->input('meta', [])
                )
            );

            return $this->redirectBackToProcurement($request, ['success' => true, 'msg' => __('vasaccounting::lang.procurement_document_reversed')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToProcurement($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    protected function supportedDocumentTypes(): array
    {
        return ['purchase_requisition', 'purchase_order', 'goods_receipt', 'supplier_invoice'];
    }

    protected function documentFamilyFor(string $documentType): string
    {
        return $documentType === 'supplier_invoice' ? 'payables' : 'procurement';
    }

    protected function lineTypeFor(string $documentType): string
    {
        return match ($documentType) {
            'purchase_requisition' => 'requisition_line',
            'purchase_order' => 'purchase_order_line',
            'goods_receipt' => 'receipt_line',
            default => 'invoice_line',
        };
    }

    protected function actionContext(Request $request, string $defaultReason, array $meta = []): ActionContext
    {
        return new ActionContext(
            (int) auth()->id(),
            $this->businessId($request),
            $request->input('reason') ?: $defaultReason,
            $request->input('request_id') ?: (string) Str::uuid(),
            $request->ip(),
            $request->userAgent(),
            array_merge((array) $request->input('meta', []), $meta)
        );
    }

    protected function procurementDocumentOrFail(int $businessId, int $documentId): FinanceDocument
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->whereIn('document_type', $this->supportedDocumentTypes())
            ->findOrFail($documentId);
    }

    protected function redirectBackToProcurement(Request $request, array $status): RedirectResponse
    {
        $previousUrl = url()->previous();
        $procurementUrl = route('vasaccounting.procurement.index');

        if ($previousUrl && str_starts_with($previousUrl, $procurementUrl)) {
            return redirect()->to($previousUrl)->with('status', $status);
        }

        return redirect()
            ->route('vasaccounting.procurement.index', array_filter([
                'location_id' => $request->query('location_id'),
                'period_id' => $request->query('period_id'),
                'focus' => $this->workspaceFocus($request),
            ], fn ($value) => filled($value)))
            ->with('status', $status);
    }

    protected function closeScope(Request $request, int $businessId): array
    {
        $periodId = (int) $request->query('period_id', 0);
        if ($periodId <= 0) {
            return [
                'period' => null,
                'start_date' => null,
                'end_date' => null,
            ];
        }

        $period = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->find($periodId);

        if (! $period) {
            return [
                'period' => null,
                'start_date' => null,
                'end_date' => null,
            ];
        }

        return [
            'period' => $period,
            'start_date' => optional($period->start_date)->toDateString(),
            'end_date' => optional($period->end_date)->toDateString(),
        ];
    }

    protected function workspaceFocus(Request $request): ?string
    {
        $focus = (string) $request->query('focus', '');

        return in_array($focus, ['pending_documents', 'receiving_queue', 'pending_matching', 'discrepancy_queue'], true) ? $focus : null;
    }

    protected function applyDocumentDateScope($query, ?string $periodStart, ?string $periodEnd)
    {
        if (! $periodStart && ! $periodEnd) {
            return $query;
        }

        if ($periodStart) {
            $query->whereDate(DB::raw('COALESCE(posting_date, document_date)'), '>=', $periodStart);
        }

        if ($periodEnd) {
            $query->whereDate(DB::raw('COALESCE(posting_date, document_date)'), '<=', $periodEnd);
        }

        return $query;
    }

    protected function discrepancyQueueItems($documents)
    {
        return collect($documents)
            ->filter(fn (FinanceDocument $document) => $document->document_type === 'supplier_invoice')
            ->flatMap(function (FinanceDocument $document) {
                $matchRun = $this->latestMatchRun($document);
                if (! $matchRun) {
                    return collect();
                }

                return $matchRun->exceptions
                    ->where('status', 'open')
                    ->map(function (FinanceMatchException $exception) use ($document, $matchRun) {
                        return [
                            'document_id' => $document->id,
                            'document_no' => $document->document_no ?: ('#' . $document->id),
                            'document_type' => $document->document_type,
                            'document_date' => optional($document->document_date)->format('Y-m-d') ?: '-',
                            'workflow_status' => (string) $document->workflow_status,
                            'severity' => (string) $exception->severity,
                            'code' => (string) $exception->code,
                            'message' => (string) $exception->message,
                            'match_status' => (string) $matchRun->status,
                            'blocking_exception_count' => (int) $matchRun->blocking_exception_count,
                            'warning_count' => (int) $matchRun->warning_count,
                            'line_no' => (int) optional($exception->documentLine)->line_no,
                            'product_id' => (int) optional($exception->documentLine)->product_id,
                            'meta' => (array) ($exception->meta ?? []),
                        ];
                    });
            })
            ->sortBy([
                fn (array $item) => $item['severity'] === 'blocking' ? 0 : 1,
                fn (array $item) => $item['document_date'],
                fn (array $item) => $item['document_no'],
            ])
            ->values();
    }

    protected function latestMatchRun(FinanceDocument $document): ?FinanceMatchRun
    {
        $latestRunId = (int) data_get($document->meta, 'matching.latest_run_id', 0);

        if ($latestRunId > 0) {
            $run = $document->matchRuns->firstWhere('id', $latestRunId);
            if ($run instanceof FinanceMatchRun) {
                return $run;
            }
        }

        return $document->matchRuns->first();
    }

    protected function latestMatchExceptionCount(FinanceDocument $document): int
    {
        $run = $this->latestMatchRun($document);

        return $run ? $run->exceptions->where('status', 'open')->count() : 0;
    }
}
