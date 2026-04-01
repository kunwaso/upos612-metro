<?php

namespace Modules\VasAccounting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Application\DTOs\DocumentCreateData;
use Modules\VasAccounting\Application\DTOs\PostingContext;
use Modules\VasAccounting\Application\DTOs\ReversalContext;
use Modules\VasAccounting\Contracts\DocumentTraceServiceInterface;
use Modules\VasAccounting\Contracts\FinanceDocumentServiceInterface;
use Modules\VasAccounting\Contracts\PostingRuleEngineInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Http\Requests\FinanceDocumentActionRequest;
use Modules\VasAccounting\Http\Requests\StoreFinanceDocumentRequest;

class FinanceDocumentController extends Controller
{
    public function __construct(
        protected FinanceDocumentServiceInterface $documentService,
        protected PostingRuleEngineInterface $postingEngine,
        protected DocumentTraceServiceInterface $traceService
    ) {
    }

    public function store(StoreFinanceDocumentRequest $request, string $family): JsonResponse
    {
        $businessId = (int) $request->user()->business_id;
        $document = $this->documentService->create(new DocumentCreateData(
            array_merge($request->safe()->except(['lines', 'links']), [
                'business_id' => $businessId,
                'document_family' => $family,
            ]),
            $request->input('lines', []),
            $request->input('links', [])
        ));

        return response()->json([
            'document' => [
                'id' => $document->id,
                'document_no' => $document->document_no,
                'document_family' => $document->document_family,
                'document_type' => $document->document_type,
                'workflow_status' => $document->workflow_status,
                'accounting_status' => $document->accounting_status,
            ],
        ], 201);
    }

    public function submit(FinanceDocumentActionRequest $request, FinanceDocument $document): JsonResponse
    {
        $document = $this->documentService->submit($document->id, $this->actionContext($request, $document));

        return response()->json(['document' => $this->documentPayload($document)]);
    }

    public function approve(FinanceDocumentActionRequest $request, FinanceDocument $document): JsonResponse
    {
        $document = $this->documentService->approve($document->id, $this->actionContext($request, $document));

        return response()->json(['document' => $this->documentPayload($document)]);
    }

    public function match(FinanceDocumentActionRequest $request, FinanceDocument $document): JsonResponse
    {
        $document = $this->documentService->match($document->id, $this->actionContext($request, $document));

        return response()->json(['document' => $this->documentPayload($document)]);
    }

    public function fulfill(FinanceDocumentActionRequest $request, FinanceDocument $document): JsonResponse
    {
        $document = $this->documentService->fulfill($document->id, $this->actionContext($request, $document));

        return response()->json(['document' => $this->documentPayload($document)]);
    }

    public function close(FinanceDocumentActionRequest $request, FinanceDocument $document): JsonResponse
    {
        $document = $this->documentService->close($document->id, $this->actionContext($request, $document));

        return response()->json(['document' => $this->documentPayload($document)]);
    }

    public function post(FinanceDocumentActionRequest $request, FinanceDocument $document): JsonResponse
    {
        $this->guardBusinessScope($request, $document);
        $result = $this->postingEngine->post(
            $document,
            (string) $request->input('event_type', 'post'),
            $this->postingContext($request, $document)
        );

        return response()->json(['result' => $result->toArray()]);
    }

    public function reverse(FinanceDocumentActionRequest $request, FinanceDocument $document): JsonResponse
    {
        $this->guardBusinessScope($request, $document);
        $result = $this->postingEngine->reverse(
            $document,
            (string) $request->input('event_type', 'post'),
            $this->reversalContext($request, $document)
        );
        $document = $document->fresh();

        return response()->json([
            'document' => $this->documentPayload($document),
            'result' => $result->toArray(),
        ]);
    }

    public function trace(FinanceDocumentActionRequest $request, FinanceDocument $document): JsonResponse
    {
        $this->guardBusinessScope($request, $document);

        return response()->json([
            'trace' => $this->traceService->traceDocument($document->id)->toArray(),
        ]);
    }

    protected function actionContext(FinanceDocumentActionRequest $request, FinanceDocument $document): ActionContext
    {
        $this->guardBusinessScope($request, $document);

        return new ActionContext(
            (int) $request->user()->id,
            (int) $request->user()->business_id,
            $request->input('reason'),
            $request->input('request_id'),
            $request->ip(),
            $request->userAgent(),
            (array) $request->input('meta', [])
        );
    }

    protected function postingContext(FinanceDocumentActionRequest $request, FinanceDocument $document): PostingContext
    {
        $baseContext = $this->actionContext($request, $document);

        return new PostingContext(
            $baseContext->userId(),
            $baseContext->businessId(),
            $baseContext->reason(),
            $baseContext->requestId(),
            $baseContext->ipAddress(),
            $baseContext->userAgent(),
            $baseContext->meta()
        );
    }

    protected function reversalContext(FinanceDocumentActionRequest $request, FinanceDocument $document): ReversalContext
    {
        $baseContext = $this->actionContext($request, $document);

        return new ReversalContext(
            $baseContext->userId(),
            $baseContext->businessId(),
            $baseContext->reason(),
            $baseContext->requestId(),
            $baseContext->ipAddress(),
            $baseContext->userAgent(),
            $baseContext->meta()
        );
    }

    protected function guardBusinessScope(FinanceDocumentActionRequest $request, FinanceDocument $document): void
    {
        if ((int) $request->user()->business_id !== (int) $document->business_id) {
            abort(404);
        }
    }

    protected function documentPayload(FinanceDocument $document): array
    {
        return [
            'id' => $document->id,
            'document_no' => $document->document_no,
            'workflow_status' => $document->workflow_status,
            'accounting_status' => $document->accounting_status,
            'submitted_at' => optional($document->submitted_at)->toDateTimeString(),
            'approved_at' => optional($document->approved_at)->toDateTimeString(),
            'posted_at' => optional($document->posted_at)->toDateTimeString(),
            'reversed_at' => optional($document->reversed_at)->toDateTimeString(),
            'open_amount' => (string) $document->open_amount,
            'latest_match_run_id' => data_get($document->meta, 'matching.latest_run_id'),
            'latest_match_status' => data_get($document->meta, 'matching.latest_status'),
            'latest_match_blocking_exception_count' => data_get($document->meta, 'matching.blocking_exception_count'),
            'latest_match_warning_count' => data_get($document->meta, 'matching.warning_count'),
            'treasury_summary' => data_get($document->meta, 'treasury'),
            'o2c_summary' => data_get($document->meta, 'o2c'),
        ];
    }
}
