<?php

namespace Modules\VasAccounting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\VasAccounting\Contracts\PostingRuleEngineInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Http\Requests\PreviewPostingRequest;

class PostingPreviewController extends Controller
{
    public function __construct(protected PostingRuleEngineInterface $postingEngine)
    {
    }

    public function __invoke(PreviewPostingRequest $request): JsonResponse
    {
        $businessId = (int) $request->user()->business_id;
        $document = $request->filled('document_id')
            ? $this->loadPersistedDocument($businessId, (int) $request->input('document_id'))
            : $this->makeTransientDocument($businessId, $request->validated());

        $preview = $this->postingEngine->preview($document, (string) $request->input('event_type'));

        return response()->json([
            'preview' => $preview->toArray(),
        ]);
    }

    protected function loadPersistedDocument(int $businessId, int $documentId): FinanceDocument
    {
        return FinanceDocument::query()
            ->with('lines')
            ->where('business_id', $businessId)
            ->findOrFail($documentId);
    }

    protected function makeTransientDocument(int $businessId, array $payload): FinanceDocument
    {
        $document = new FinanceDocument();
        $document->business_id = $businessId;
        $document->document_family = (string) $payload['document_family'];
        $document->document_type = (string) $payload['document_type'];
        $document->document_no = (string) ($payload['document_no'] ?? 'PREVIEW');
        $document->business_location_id = $payload['business_location_id'] ?? null;
        $document->currency_code = (string) ($payload['currency_code'] ?? config('vasaccounting.book_currency', 'VND'));
        $document->exchange_rate = $payload['exchange_rate'] ?? 1;
        $document->document_date = $payload['document_date'];
        $document->posting_date = $payload['posting_date'] ?? $payload['document_date'];
        $document->workflow_status = 'approved';
        $document->accounting_status = 'ready_to_post';
        $document->exists = false;

        $lines = collect((array) ($payload['lines'] ?? []))->values()->map(function (array $line, int $index) use ($businessId) {
            $documentLine = new FinanceDocumentLine();
            $documentLine->business_id = $businessId;
            $documentLine->line_no = (int) ($line['line_no'] ?? ($index + 1));
            $documentLine->description = $line['description'] ?? null;
            $documentLine->line_amount = $line['line_amount'] ?? 0;
            $documentLine->tax_amount = $line['tax_amount'] ?? 0;
            $documentLine->gross_amount = $line['gross_amount'] ?? 0;
            $documentLine->tax_code_id = $line['tax_code_id'] ?? null;
            $documentLine->debit_account_id = $line['debit_account_id'] ?? null;
            $documentLine->credit_account_id = $line['credit_account_id'] ?? null;
            $documentLine->tax_account_id = $line['tax_account_id'] ?? null;
            $documentLine->dimensions = $line['dimensions'] ?? [];
            $documentLine->payload = $line['payload'] ?? [];
            $documentLine->exists = false;

            return $documentLine;
        });

        $document->setRelation('lines', $lines);

        return $document;
    }
}
