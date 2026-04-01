<?php

namespace Modules\VasAccounting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\VasAccounting\Contracts\TreasuryExceptionServiceInterface;
use Modules\VasAccounting\Entities\VasBankStatementLine;
use Modules\VasAccounting\Http\Requests\TreasuryReconciliationActionRequest;

class TreasuryExceptionController extends Controller
{
    public function __construct(protected TreasuryExceptionServiceInterface $treasuryExceptionService)
    {
    }

    public function index(TreasuryReconciliationActionRequest $request): JsonResponse
    {
        $businessId = (int) $request->user()->business_id;

        return response()->json([
            'summary' => $this->treasuryExceptionService->queueSummary($businessId),
            'queue' => $this->treasuryExceptionService->queue($businessId, (int) $request->input('limit', 20)),
        ]);
    }

    public function refresh(TreasuryReconciliationActionRequest $request, VasBankStatementLine $statementLine): JsonResponse
    {
        if ((int) $request->user()->business_id !== (int) $statementLine->business_id) {
            abort(404);
        }

        $exception = $this->treasuryExceptionService->refreshForStatementLine(
            $statementLine,
            (int) $request->user()->business_id
        );

        return response()->json([
            'exception' => [
                'id' => $exception->id,
                'status' => $exception->status,
                'severity' => $exception->severity,
                'exception_code' => $exception->exception_code,
                'top_match_score' => (string) $exception->top_match_score,
                'recommended_document_id' => $exception->recommended_document_id,
            ],
        ]);
    }
}
