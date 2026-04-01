<?php

namespace Modules\VasAccounting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\TreasuryReconciliationServiceInterface;
use Modules\VasAccounting\Contracts\TreasuryExceptionServiceInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryReconciliation;
use Modules\VasAccounting\Entities\VasBankStatementLine;
use Modules\VasAccounting\Http\Requests\TreasuryReconciliationActionRequest;
use Modules\VasAccounting\Http\Requests\TreasuryReconciliationCandidatesRequest;

class TreasuryReconciliationController extends Controller
{
    public function __construct(
        protected TreasuryReconciliationServiceInterface $treasuryReconciliationService,
        protected TreasuryExceptionServiceInterface $treasuryExceptionService
    )
    {
    }

    public function candidates(TreasuryReconciliationCandidatesRequest $request, VasBankStatementLine $statementLine): JsonResponse
    {
        $this->guardStatementLineScope($request, $statementLine);

        return response()->json([
            'statement_line_id' => $statementLine->id,
            'candidates' => $this->treasuryReconciliationService->suggestCandidates(
                $statementLine,
                (int) $request->user()->business_id,
                $request->integer('limit') ?: null
            ),
        ]);
    }

    public function reconcile(TreasuryReconciliationActionRequest $request, VasBankStatementLine $statementLine): JsonResponse
    {
        $this->guardStatementLineScope($request, $statementLine);
        $document = FinanceDocument::query()
            ->where('business_id', (int) $request->user()->business_id)
            ->findOrFail((int) $request->input('finance_document_id'));

        $reconciliation = $this->treasuryReconciliationService->reconcile(
            $statementLine,
            $document,
            $this->actionContext($request),
            $request->filled('finance_open_item_id') ? (int) $request->input('finance_open_item_id') : null
        );
        $this->treasuryExceptionService->refreshForStatementLine($statementLine, (int) $request->user()->business_id, $this->actionContext($request));

        return response()->json([
            'reconciliation' => $this->reconciliationPayload($reconciliation),
        ]);
    }

    public function reverse(TreasuryReconciliationActionRequest $request, FinanceTreasuryReconciliation $reconciliation): JsonResponse
    {
        $this->guardReconciliationScope($request, $reconciliation);
        $reconciliation = $this->treasuryReconciliationService->reverse($reconciliation, $this->actionContext($request));
        $this->treasuryExceptionService->refreshForStatementLine($reconciliation->statementLine, (int) $request->user()->business_id, $this->actionContext($request));

        return response()->json([
            'reconciliation' => $this->reconciliationPayload($reconciliation),
        ]);
    }

    protected function actionContext(TreasuryReconciliationActionRequest $request): ActionContext
    {
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

    protected function guardStatementLineScope(TreasuryReconciliationActionRequest $request, VasBankStatementLine $statementLine): void
    {
        if ((int) $request->user()->business_id !== (int) $statementLine->business_id) {
            abort(404);
        }
    }

    protected function guardReconciliationScope(
        TreasuryReconciliationActionRequest $request,
        FinanceTreasuryReconciliation $reconciliation
    ): void {
        if ((int) $request->user()->business_id !== (int) $reconciliation->business_id) {
            abort(404);
        }
    }

    protected function reconciliationPayload(FinanceTreasuryReconciliation $reconciliation): array
    {
        return [
            'id' => $reconciliation->id,
            'statement_line_id' => $reconciliation->statement_line_id,
            'document_id' => $reconciliation->document_id,
            'open_item_id' => $reconciliation->open_item_id,
            'status' => $reconciliation->status,
            'direction' => $reconciliation->direction,
            'match_confidence' => (string) $reconciliation->match_confidence,
            'statement_amount' => (string) $reconciliation->statement_amount,
            'document_amount' => (string) $reconciliation->document_amount,
            'matched_amount' => (string) $reconciliation->matched_amount,
            'reconciled_at' => optional($reconciliation->reconciled_at)->toDateTimeString(),
            'reversed_at' => optional($reconciliation->reversed_at)->toDateTimeString(),
        ];
    }
}
