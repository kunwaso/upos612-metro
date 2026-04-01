<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Application\DTOs\DocumentCreateData;
use Modules\VasAccounting\Application\DTOs\PostingContext;
use Modules\VasAccounting\Application\DTOs\ReversalContext;
use Modules\VasAccounting\Contracts\ExpenseSettlementServiceInterface;
use Modules\VasAccounting\Contracts\FinanceDocumentServiceInterface;
use Modules\VasAccounting\Contracts\PostingRuleEngineInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasCostCenter;
use Modules\VasAccounting\Entities\VasDepartment;
use Modules\VasAccounting\Entities\VasProject;
use Modules\VasAccounting\Entities\VasTaxCode;
use Modules\VasAccounting\Http\Requests\FinanceDocumentActionRequest;
use Modules\VasAccounting\Http\Requests\StoreExpenseFinanceDocumentRequest;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class ExpenseController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected FinanceDocumentServiceInterface $financeDocumentService,
        protected PostingRuleEngineInterface $postingRuleEngine,
        protected ExpenseSettlementServiceInterface $expenseSettlementService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.expenses.manage');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $closeScope = $this->closeScope($request, $businessId);
        $closePeriod = $closeScope['period'];
        $periodStart = $closeScope['start_date'];
        $periodEnd = $closeScope['end_date'];
        $workspaceFocus = $this->workspaceFocus($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['expense_v2'] ?? true) === false) {
            abort(404);
        }

        $expenseDocuments = Schema::hasTable('vas_fin_documents')
            ? FinanceDocument::query()
                ->with(['approvalInstances.steps', 'parentLinks.parentDocument', 'childLinks.childDocument'])
                ->where('business_id', $businessId)
                ->where('document_family', 'expense_management')
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->when($periodStart || $periodEnd, fn ($query) => $this->applyDocumentDateScope($query, $periodStart, $periodEnd))
                ->when($workspaceFocus === 'pending_documents', fn ($query) => $query->whereIn('workflow_status', ['draft', 'submitted', 'approved']))
                ->when($workspaceFocus === 'outstanding_balances', fn ($query) => $query
                    ->whereIn('document_type', ['expense_claim', 'advance_request'])
                    ->where('open_amount', '>', 0)
                    ->whereNotIn('workflow_status', ['cancelled', 'reversed', 'closed']))
                ->latest('document_date')
                ->latest('id')
                ->take(20)
                ->get()
            : collect();

        $summary = [
            'documents' => $expenseDocuments->count(),
            'open_workflow' => $expenseDocuments->whereIn('workflow_status', ['draft', 'submitted', 'approved'])->count(),
            'posted_documents' => $expenseDocuments->where('workflow_status', 'posted')->count(),
            'gross_amount' => round((float) $expenseDocuments->sum(fn (FinanceDocument $document) => (float) $document->gross_amount), 4),
        ];

        $documentTypeCounts = [
            'expense_claim' => $expenseDocuments->where('document_type', 'expense_claim')->count(),
            'advance_request' => $expenseDocuments->where('document_type', 'advance_request')->count(),
            'advance_settlement' => $expenseDocuments->where('document_type', 'advance_settlement')->count(),
            'reimbursement_voucher' => $expenseDocuments->where('document_type', 'reimbursement_voucher')->count(),
        ];

        $activeExpenseDocumentQuery = Schema::hasTable('vas_fin_documents')
            ? FinanceDocument::query()
                ->where('business_id', $businessId)
                ->where('document_family', 'expense_management')
                ->whereNotIn('workflow_status', ['cancelled', 'reversed'])
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->when($periodStart || $periodEnd, fn ($query) => $this->applyDocumentDateScope($query, $periodStart, $periodEnd))
            : null;

        return view('vasaccounting::expenses.index', [
            'summary' => $summary,
            'documentTypeCounts' => $documentTypeCounts,
            'expenseDocuments' => $expenseDocuments,
            'employeeOptions' => User::forDropdown($businessId, true, false, false, true),
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'selectedLocationId' => $selectedLocationId,
            'closePeriod' => $closePeriod,
            'workspaceFocus' => $workspaceFocus,
            'departmentOptions' => Schema::hasTable('vas_departments')
                ? VasDepartment::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'costCenterOptions' => Schema::hasTable('vas_cost_centers')
                ? VasCostCenter::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'projectOptions' => Schema::hasTable('vas_projects')
                ? VasProject::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'taxCodeOptions' => Schema::hasTable('vas_tax_codes')
                ? VasTaxCode::query()->where('business_id', $businessId)->orderBy('code')->get(['id', 'code', 'name'])
                : collect(),
            'chartOptions' => $this->vasUtil->chartOptions($businessId),
            'advanceRequestOptions' => $activeExpenseDocumentQuery
                ? (clone $activeExpenseDocumentQuery)
                    ->where('document_type', 'advance_request')
                    ->orderByDesc('document_date')
                    ->get(['id', 'document_no', 'gross_amount', 'open_amount'])
                : collect(),
            'expenseClaimOptions' => $activeExpenseDocumentQuery
                ? (clone $activeExpenseDocumentQuery)
                    ->where('document_type', 'expense_claim')
                    ->orderByDesc('document_date')
                    ->get(['id', 'document_no', 'gross_amount', 'open_amount', 'meta'])
                : collect(),
        ]);
    }

    public function store(StoreExpenseFinanceDocumentRequest $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.expenses.manage');

        $businessId = $this->businessId($request);
        $validated = $request->validated();
        $claimantUserId = (int) ($validated['claimant_user_id'] ?? 0);
        $claimantName = $claimantUserId > 0
            ? (string) (User::forDropdown($businessId, false, false, false, true)->get($claimantUserId) ?? '')
            : null;
        $links = $this->expenseSettlementService->buildCreationLinks($businessId, (string) $validated['document_type'], $validated);

        try {
            $document = $this->financeDocumentService->create(new DocumentCreateData(
                [
                    'business_id' => $businessId,
                    'document_family' => 'expense_management',
                    'document_type' => $validated['document_type'],
                    'document_no' => $validated['document_no'],
                    'external_reference' => $validated['external_reference'] ?? null,
                    'business_location_id' => $validated['business_location_id'] ?? null,
                    'currency_code' => config('vasaccounting.book_currency', 'VND'),
                    'exchange_rate' => 1,
                    'document_date' => $validated['document_date'],
                    'posting_date' => $validated['posting_date'] ?? $validated['document_date'],
                    'workflow_status' => 'draft',
                    'accounting_status' => 'not_ready',
                    'gross_amount' => (float) $validated['amount'] + (float) ($validated['tax_amount'] ?? 0),
                    'tax_amount' => (float) ($validated['tax_amount'] ?? 0),
                    'net_amount' => (float) $validated['amount'],
                    'open_amount' => 0,
                    'meta' => [
                        'expense' => [
                            'claimant_user_id' => $claimantUserId > 0 ? $claimantUserId : null,
                            'claimant_name' => $claimantName,
                            'department_id' => $validated['department_id'] ?? null,
                            'cost_center_id' => $validated['cost_center_id'] ?? null,
                            'project_id' => $validated['project_id'] ?? null,
                            'advance_request_id' => $validated['advance_request_id'] ?? null,
                            'expense_claim_id' => $validated['expense_claim_id'] ?? null,
                        ],
                    ],
                ],
                [[
                    'line_type' => $this->lineTypeFor((string) $validated['document_type']),
                    'business_location_id' => $validated['business_location_id'] ?? null,
                    'tax_code_id' => $validated['tax_code_id'] ?? null,
                    'description' => $validated['description'],
                    'quantity' => 1,
                    'unit_price' => $validated['amount'],
                    'line_amount' => $validated['amount'],
                    'tax_amount' => $validated['tax_amount'] ?? 0,
                    'gross_amount' => (float) $validated['amount'] + (float) ($validated['tax_amount'] ?? 0),
                    'debit_account_id' => (int) $validated['debit_account_id'],
                    'credit_account_id' => (int) $validated['credit_account_id'],
                    'tax_account_id' => $validated['tax_account_id'] ?? null,
                    'dimensions' => array_filter([
                        'business_location_id' => $validated['business_location_id'] ?? null,
                        'department_id' => $validated['department_id'] ?? null,
                        'cost_center_id' => $validated['cost_center_id'] ?? null,
                        'project_id' => $validated['project_id'] ?? null,
                        'claimant_user_id' => $claimantUserId > 0 ? $claimantUserId : null,
                    ], fn ($value) => ! is_null($value) && $value !== ''),
                    'payload' => array_filter([
                        'expense_document_type' => $validated['document_type'],
                        'tax_entry_side' => $validated['tax_entry_side'] ?? 'debit',
                        'claimant_user_id' => $claimantUserId > 0 ? $claimantUserId : null,
                    ], fn ($value) => ! is_null($value) && $value !== ''),
                ]],
                $links,
            ));

            return $this->redirectBackToExpenses($request, ['success' => true, 'msg' => __('vasaccounting::lang.expense_document_saved', ['document' => $document->document_no])]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToExpenses($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function submit(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.expenses.manage');

        try {
            $documentModel = $this->expenseDocumentOrFail($this->businessId($request), $document);
            $this->financeDocumentService->submit($documentModel->id, $this->actionContext($request, 'Expense document submitted'));

            return $this->redirectBackToExpenses($request, ['success' => true, 'msg' => __('vasaccounting::lang.expense_document_submitted')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToExpenses($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function approve(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.expenses.manage');

        try {
            $documentModel = $this->expenseDocumentOrFail($this->businessId($request), $document);
            $documentModel = $this->financeDocumentService->approve($documentModel->id, $this->actionContext($request, 'Expense document approved'));

            return $this->redirectBackToExpenses($request, [
                'success' => true,
                'msg' => __(
                    $documentModel->workflow_status === 'approved'
                        ? 'vasaccounting::lang.expense_document_approved'
                        : 'vasaccounting::lang.expense_document_approval_progressed'
                ),
            ]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToExpenses($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function post(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.expenses.manage');

        try {
            $documentModel = $this->expenseDocumentOrFail($this->businessId($request), $document);
            $this->postingRuleEngine->post(
                $documentModel,
                (string) $request->input('event_type', 'post'),
                new PostingContext(
                    (int) auth()->id(),
                    $this->businessId($request),
                    $request->input('reason') ?: 'Expense document posted',
                    $request->input('request_id') ?: (string) Str::uuid(),
                    $request->ip(),
                    $request->userAgent(),
                    (array) $request->input('meta', [])
                )
            );

            return $this->redirectBackToExpenses($request, ['success' => true, 'msg' => __('vasaccounting::lang.expense_document_posted')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToExpenses($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function reverse(FinanceDocumentActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.expenses.manage');

        try {
            $documentModel = $this->expenseDocumentOrFail($this->businessId($request), $document);
            $this->postingRuleEngine->reverse(
                $documentModel,
                (string) $request->input('event_type', 'post'),
                new ReversalContext(
                    (int) auth()->id(),
                    $this->businessId($request),
                    $request->input('reason') ?: 'Expense document reversed',
                    $request->input('request_id') ?: (string) Str::uuid(),
                    $request->ip(),
                    $request->userAgent(),
                    (array) $request->input('meta', [])
                )
            );

            return $this->redirectBackToExpenses($request, ['success' => true, 'msg' => __('vasaccounting::lang.expense_document_reversed')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToExpenses($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    protected function lineTypeFor(string $documentType): string
    {
        return match ($documentType) {
            'advance_request' => 'advance',
            'advance_settlement' => 'settlement',
            'reimbursement_voucher' => 'reimbursement',
            default => 'expense',
        };
    }

    protected function actionContext(Request $request, string $defaultReason): ActionContext
    {
        return new ActionContext(
            (int) auth()->id(),
            $this->businessId($request),
            $request->input('reason') ?: $defaultReason,
            $request->input('request_id') ?: (string) Str::uuid(),
            $request->ip(),
            $request->userAgent(),
            (array) $request->input('meta', [])
        );
    }

    protected function expenseDocumentOrFail(int $businessId, int $documentId): FinanceDocument
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->where('document_family', 'expense_management')
            ->whereIn('document_type', ['expense_claim', 'advance_request', 'advance_settlement', 'reimbursement_voucher'])
            ->findOrFail($documentId);
    }

    protected function redirectBackToExpenses(Request $request, array $status): RedirectResponse
    {
        $previousUrl = url()->previous();
        $expensesUrl = route('vasaccounting.expenses.index');

        if ($previousUrl && str_starts_with($previousUrl, $expensesUrl)) {
            return redirect()->to($previousUrl)->with('status', $status);
        }

        return redirect()
            ->route('vasaccounting.expenses.index', array_filter([
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

        return in_array($focus, ['pending_documents', 'outstanding_balances'], true) ? $focus : null;
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
}
