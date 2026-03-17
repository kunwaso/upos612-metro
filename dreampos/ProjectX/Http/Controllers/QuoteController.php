<?php

namespace Modules\ProjectX\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ProjectX\Contracts\QuoteMailerInterface;
use Modules\ProjectX\Entities\Fabric;
use Modules\ProjectX\Entities\Quote;
use Modules\ProjectX\Entities\Trim;
use Modules\ProjectX\Http\Requests\ClearQuoteSignatureRequest;
use Modules\ProjectX\Http\Requests\ReleaseQuoteInvoiceRequest;
use Modules\ProjectX\Http\Requests\RevertQuoteToDraftRequest;
use Modules\ProjectX\Http\Requests\SendQuoteRequest;
use Modules\ProjectX\Http\Requests\SetPublicQuotePasswordRequest;
use Modules\ProjectX\Http\Requests\StoreBudgetQuoteRequest;
use Modules\ProjectX\Http\Requests\StoreQuoteRequest;
use Modules\ProjectX\Http\Requests\StoreTrimBudgetQuoteRequest;
use Modules\ProjectX\Http\Requests\UpdateQuoteRequest;
use Modules\ProjectX\Utils\FabricCostingUtil;
use Modules\ProjectX\Utils\FabricProductSyncUtil;
use Modules\ProjectX\Utils\ProjectXQuoteDisplayPresenter;
use Modules\ProjectX\Utils\QuoteInvoiceReleaseService;
use Modules\ProjectX\Utils\QuoteUtil;
use Yajra\DataTables\Facades\DataTables;

class QuoteController extends Controller
{
    protected QuoteUtil $quoteUtil;

    protected FabricCostingUtil $fabricCostingUtil;

    protected QuoteInvoiceReleaseService $quoteInvoiceReleaseService;

    protected QuoteMailerInterface $quoteMailer;

    protected ProjectXQuoteDisplayPresenter $quoteDisplayPresenter;

    protected FabricProductSyncUtil $fabricProductSyncUtil;

    public function __construct(
        QuoteUtil $quoteUtil,
        FabricCostingUtil $fabricCostingUtil,
        QuoteInvoiceReleaseService $quoteInvoiceReleaseService,
        QuoteMailerInterface $quoteMailer,
        ProjectXQuoteDisplayPresenter $quoteDisplayPresenter,
        FabricProductSyncUtil $fabricProductSyncUtil
    ) {
        $this->quoteUtil = $quoteUtil;
        $this->fabricCostingUtil = $fabricCostingUtil;
        $this->quoteInvoiceReleaseService = $quoteInvoiceReleaseService;
        $this->quoteMailer = $quoteMailer;
        $this->quoteDisplayPresenter = $quoteDisplayPresenter;
        $this->fabricProductSyncUtil = $fabricProductSyncUtil;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('projectx.quote.view')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $quotes = Quote::forBusiness($business_id)
                ->leftJoin('contacts as c', 'projectx_quotes.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 'projectx_quotes.location_id', '=', 'bl.id')
                ->select([
                    'projectx_quotes.id',
                    'projectx_quotes.uuid',
                    'projectx_quotes.quote_number',
                    'projectx_quotes.quote_date',
                    'projectx_quotes.expires_at',
                    'projectx_quotes.grand_total',
                    'projectx_quotes.line_count',
                    'projectx_quotes.currency',
                    'projectx_quotes.incoterm',
                    'projectx_quotes.sent_at',
                    'projectx_quotes.confirmed_at',
                    'projectx_quotes.created_at',
                    'projectx_quotes.transaction_id',
                    DB::raw('EXISTS(SELECT 1 FROM projectx_quote_lines ql_trim WHERE ql_trim.quote_id = projectx_quotes.id AND ql_trim.trim_id IS NOT NULL) as has_trim_lines'),
                    DB::raw('EXISTS(SELECT 1 FROM projectx_quote_lines ql_fabric WHERE ql_fabric.quote_id = projectx_quotes.id AND ql_fabric.fabric_id IS NOT NULL) as has_fabric_lines'),
                    'bl.name as location_name',
                    DB::raw('COALESCE(projectx_quotes.customer_name, c.name, c.supplier_business_name) as customer_name'),
                ]);

            return DataTables::of($quotes)
                ->editColumn('created_at', function ($row) {
                    $date = $row->quote_date ?? $row->created_at;

                    return $date
                        ? \Carbon\Carbon::parse($date)->format('M d, Y h:i A')
                        : '-';
                })
                ->editColumn('sent_at', function ($row) {
                    return $row->sent_at
                        ? \Carbon\Carbon::parse($row->sent_at)->format('M d, Y h:i A')
                        : '-';
                })
                ->editColumn('expires_at', function ($row) {
                    return $row->expires_at
                        ? \Carbon\Carbon::parse($row->expires_at)->format('M d, Y')
                        : '-';
                })
                ->editColumn('grand_total', function ($row) {
                    $symbol = session('currency.symbol') ?? '$';
                    $currency = (array) session('currency', []);
                    $currencyPrecision = max(0, (int) session('business.currency_precision', 2));
                    $decimalSeparator = (string) ($currency['decimal_separator'] ?? '.');
                    $thousandSeparator = (string) ($currency['thousand_separator'] ?? ',');

                    return $symbol . ' ' . number_format((float) $row->grand_total, $currencyPrecision, $decimalSeparator, $thousandSeparator);
                })
                ->editColumn('quote_number', function ($row) {
                    return $row->quote_number ?: $row->uuid;
                })
                ->addColumn('quote_type', function ($row) {
                    $hasTrimLines = (int) ($row->has_trim_lines ?? 0) === 1;
                    $hasFabricLines = (int) ($row->has_fabric_lines ?? 0) === 1;

                    if ($hasTrimLines && $hasFabricLines) {
                        return '<span class="badge badge-light-warning">' . __('projectx::lang.mixed_quote') . '</span>';
                    }

                    if ($hasTrimLines) {
                        return '<span class="badge badge-light-info">' . __('projectx::lang.single_trim_quote') . '</span>';
                    }

                    if ((int) $row->line_count > 1) {
                        return '<span class="badge badge-light-primary">' . __('projectx::lang.multi_fabric_quote') . '</span>';
                    }

                    return '<span class="badge badge-light-info">' . __('projectx::lang.single_fabric_quote') . '</span>';
                })
                ->addColumn('quote_state', function ($row) {
                    if (! empty($row->transaction_id)) {
                        return '<span class="badge badge-light-success">' . __('projectx::lang.quote_state_converted') . '</span>';
                    }
                    if (! empty($row->confirmed_at)) {
                        return '<span class="badge badge-light-primary">' . __('projectx::lang.quote_state_confirmed') . '</span>';
                    }
                    if (! empty($row->sent_at)) {
                        return '<span class="badge badge-light-warning">' . __('projectx::lang.quote_state_sent') . '</span>';
                    }

                    return '<span class="badge badge-light-secondary">' . __('projectx::lang.quote_state_draft') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $canEdit = auth()->user()->can('projectx.quote.edit');
                    $canDelete = auth()->user()->can('projectx.quote.delete');
                    $canCreateSale = auth()->user()->can('direct_sell.access');
                    $canAdminOverride = auth()->user()->can('projectx.quote.admin_override');
                    $isEditable = empty($row->transaction_id) && empty($row->confirmed_at);
                    $isConfirmedUnlinked = ! empty($row->confirmed_at) && empty($row->transaction_id);

                    $actions = [];
                    $actions[] = '<a href="' . route('projectx.quotes.show', ['id' => $row->id]) . '" class="btn btn-sm btn-light-primary me-2"><i class="ki-duotone ki-eye fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> ' . __('projectx::lang.view') . '</a>';

                    if (($isEditable || $canAdminOverride) && $canEdit) {
                        $actions[] = '<a href="' . route('projectx.quotes.edit', ['id' => $row->id]) . '" class="btn btn-sm btn-light-info me-2"><i class="ki-duotone ki-notepad-edit fs-5"><span class="path1"></span><span class="path2"></span></i> ' . __('projectx::lang.edit') . '</a>';
                    }

                    if (($isEditable || $canAdminOverride) && $canDelete) {
                        $actions[] = '<form method="POST" action="' . route('projectx.quotes.destroy', ['id' => $row->id]) . '" class="d-inline-block me-2" onsubmit="return confirm(\'' . e(__('projectx::lang.delete_quote_confirm')) . '\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-sm btn-light-danger"><i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i> ' . __('projectx::lang.delete') . '</button></form>';
                    }

                    if ($isConfirmedUnlinked && $canCreateSale) {
                        $actions[] = '<a href="' . route('sells.create', ['projectx_quote_id' => $row->id]) . '" class="btn btn-sm btn-primary me-2"><i class="ki-duotone ki-basket fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i> ' . __('projectx::lang.create_sale_from_quote') . '</a>';
                    }

                    if (! empty($row->transaction_id)) {
                        $actions[] = '<a href="' . route('projectx.sales.orders.show', ['id' => $row->transaction_id]) . '" class="btn btn-sm btn-light-success"><i class="ki-duotone ki-document fs-5"><span class="path1"></span><span class="path2"></span></i> ' . __('projectx::lang.view_order') . '</a>';
                    }

                    return implode('', $actions);
                })
                ->rawColumns(['quote_type', 'quote_state', 'action'])
                ->make(true);
        }

        return view('projectx::sales.index');
    }

    public function create(Request $request)
    {
        if (! auth()->user()->can('projectx.quote.create')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        $customersDropdown = Contact::customersDropdown($business_id, false, true);
        $locationsDropdown = BusinessLocation::forDropdown($business_id, false, false);
        $costingDropdowns = $this->fabricCostingUtil->getDropdownOptions($business_id);
        $costingDropdowns['purchase_uom'] = array_values(array_unique(array_filter(array_merge(
            array_values((array) ($costingDropdowns['purchase_uom'] ?? [])),
            Trim::UOM_OPTIONS
        ))));
        $defaultCurrencyCode = $this->fabricCostingUtil->getDefaultCurrencyCode($business_id);
        $fabrics = Fabric::forBusiness($business_id)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'fabric_sku',
                'mill_article_no',
                'price_500_yds',
            ]);
        $trims = Trim::forBusiness($business_id)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'part_number',
                'unit_of_measure',
                'unit_cost',
            ]);

        $lineDefaults = $this->resolveQuoteLineDefaults($costingDropdowns, $defaultCurrencyCode);
        $quoteLines = $this->normalizeQuoteLinesForView((array) old('lines', []), $lineDefaults);

        return view('projectx::sales.quote-create', compact(
            'customersDropdown',
            'locationsDropdown',
            'costingDropdowns',
            'defaultCurrencyCode',
            'fabrics',
            'trims',
            'quoteLines'
        ));
    }

    public function store(StoreQuoteRequest $request)
    {
        if (! auth()->user()->can('projectx.quote.create')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->createMultiFabricQuote(
                $business_id,
                $request->validated(),
                (int) auth()->id()
            );

            return redirect()
                ->route('projectx.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_created_success')]);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function storeFromFabric(StoreBudgetQuoteRequest $request, int $fabric_id)
    {
        if (! auth()->user()->can('projectx.quote.create')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->createSingleFabricQuote(
                $business_id,
                $fabric_id,
                $request->validated(),
                (int) auth()->id()
            );

            $redirectProductId = (int) $request->input('redirect_to_product_id', 0);
            if ($redirectProductId > 0) {
                $linkedFabric = $this->fabricProductSyncUtil->findLinkedFabric($business_id, $redirectProductId);

                if (! empty($linkedFabric) && (int) $linkedFabric->id === $fabric_id) {
                    return redirect()
                        ->route('product.detail', ['id' => $redirectProductId, 'tab' => 'quotes', 'quote_id' => $quote->id])
                        ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_created_success')]);
                }
            }

            return redirect()
                ->route('projectx.fabric_manager.budget', ['fabric_id' => $fabric_id, 'quote_id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_created_success')]);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function storeFromTrim(StoreTrimBudgetQuoteRequest $request, int $id)
    {
        if (! auth()->user()->can('projectx.quote.create')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->createSingleTrimQuote(
                $business_id,
                $id,
                $request->validated(),
                (int) auth()->id()
            );

            return redirect()
                ->route('projectx.trim_manager.budget', ['id' => $id, 'quote_id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_created_success')]);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function show(int $id, Request $request)
    {
        if (! auth()->user()->can('projectx.quote.view')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
        $publicUrl = route('projectx.quotes.public', ['publicToken' => $quote->public_token]);
        $quoteDisplay = $this->quoteDisplayPresenter->presentQuote($quote);

        $canAdminOverride = auth()->user()->can('projectx.quote.admin_override');
        $canEditQuote = auth()->user()->can('projectx.quote.edit');
        $canDeleteQuote = auth()->user()->can('projectx.quote.delete');
        $canOverrideAndEdit = $canAdminOverride && $canEditQuote;

        $quoteActionFlags = [
            'showEdit' => ((bool) ($quoteDisplay['isEditable'] ?? false) || $canAdminOverride) && $canEditQuote,
            'showDelete' => ((bool) ($quoteDisplay['isEditable'] ?? false) || $canAdminOverride) && $canDeleteQuote,
            'showRevertToDraft' => ! empty($quote->transaction_id) && $canOverrideAndEdit,
            'showClearSignature' => (bool) ($quoteDisplay['isConfirmedUnlinked'] ?? false) && $canOverrideAndEdit,
            'showOverrideNotice' => ! ((bool) ($quoteDisplay['isEditable'] ?? false))
                && (((bool) ($quoteDisplay['isEditable'] ?? false) || $canAdminOverride) && $canEditQuote),
            'canCreateSaleFromQuote' => (bool) ($quoteDisplay['isConfirmedUnlinked'] ?? false) && auth()->user()->can('direct_sell.access'),
            'canSendQuote' => auth()->user()->can('projectx.quote.send'),
        ];

        $recipientEmail = (string) ($quote->customer_email ?: (optional($quote->contact)->email ?? ''));

        return view('projectx::sales.quote-show', compact(
            'quote',
            'publicUrl',
            'quoteDisplay',
            'quoteActionFlags',
            'recipientEmail'
        ));
    }

    public function edit(int $id, Request $request)
    {
        if (! auth()->user()->can('projectx.quote.edit')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
        $canAdminOverride = auth()->user()->can('projectx.quote.admin_override');

        if (! $quote->isEditable() && ! $canAdminOverride) {
            return redirect()
                ->route('projectx.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => false, 'msg' => __('projectx::lang.quote_not_editable')]);
        }

        $customersDropdown = Contact::customersDropdown($business_id, false, true);
        $locationsDropdown = BusinessLocation::forDropdown($business_id, false, false);
        $costingDropdowns = $this->fabricCostingUtil->getDropdownOptions($business_id);
        $costingDropdowns['purchase_uom'] = array_values(array_unique(array_filter(array_merge(
            array_values((array) ($costingDropdowns['purchase_uom'] ?? [])),
            Trim::UOM_OPTIONS
        ))));
        $defaultCurrencyCode = $this->fabricCostingUtil->getDefaultCurrencyCode($business_id);
        $fabrics = Fabric::forBusiness($business_id)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'fabric_sku',
                'mill_article_no',
                'price_500_yds',
            ]);
        $trims = Trim::forBusiness($business_id)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'part_number',
                'unit_of_measure',
                'unit_cost',
            ]);

        $lineDefaults = $this->resolveQuoteLineDefaults($costingDropdowns, $defaultCurrencyCode);
        $oldLines = (array) old('lines', []);
        $quoteLines = ! empty($oldLines)
            ? $this->normalizeQuoteLinesForView($oldLines, $lineDefaults)
            : $this->buildQuoteLinesFromQuote($quote, $lineDefaults);

        return view('projectx::sales.quote-edit', compact(
            'quote',
            'customersDropdown',
            'locationsDropdown',
            'costingDropdowns',
            'defaultCurrencyCode',
            'fabrics',
            'trims',
            'quoteLines'
        ));
    }

    public function update(UpdateQuoteRequest $request, int $id)
    {
        if (! auth()->user()->can('projectx.quote.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
            $allowAdminOverride = auth()->user()->can('projectx.quote.admin_override');
            $quote = $this->quoteUtil->updateQuote($business_id, $quote, $request->validated(), $allowAdminOverride);

            return redirect()
                ->route('projectx.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_updated_success')]);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroy(Request $request, int $id)
    {
        if (! auth()->user()->can('projectx.quote.delete')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
            $allowAdminOverride = auth()->user()->can('projectx.quote.admin_override');
            $this->quoteUtil->deleteQuote($business_id, $quote, $allowAdminOverride);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('projectx::lang.quote_deleted_success'));
            }

            return redirect()
                ->route('projectx.sales')
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_deleted_success')]);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWithError($e->getMessage());
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($e);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function revertToDraft(RevertQuoteToDraftRequest $request, int $id)
    {
        if (! auth()->user()->can('projectx.quote.edit') || ! auth()->user()->can('projectx.quote.admin_override')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
            $quote = $this->quoteUtil->revertQuoteToDraft($business_id, $quote);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('projectx::lang.quote_reverted_to_draft_success'), [
                    'quote_id' => (int) $quote->id,
                ]);
            }

            return redirect()
                ->route('projectx.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_reverted_to_draft_success')]);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWithError($e->getMessage());
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($e);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function clearSignature(ClearQuoteSignatureRequest $request, int $id)
    {
        if (! auth()->user()->can('projectx.quote.edit') || ! auth()->user()->can('projectx.quote.admin_override')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
            $quote = $this->quoteUtil->clearQuoteConfirmation($business_id, $quote);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('projectx::lang.quote_signature_cleared_success'), [
                    'quote_id' => (int) $quote->id,
                ]);
            }

            return redirect()
                ->route('projectx.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_signature_cleared_success')]);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWithError($e->getMessage());
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($e);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function setPublicPassword(SetPublicQuotePasswordRequest $request, int $id)
    {
        if (! auth()->user()->can('projectx.quote.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
            $validated = $request->validated();

            $password = (bool) ($validated['remove_password'] ?? false)
                ? null
                : ($validated['password'] ?? null);

            $quote = $this->quoteUtil->updatePublicLinkPassword($business_id, $quote, $password);
            $message = empty($quote->public_link_password)
                ? __('projectx::lang.password_removed_success')
                : __('projectx::lang.password_set_success');

            return redirect()
                ->route('projectx.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => $message]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function sellPrefill(Request $request, int $id)
    {
        if (! auth()->user()->can('direct_sell.access')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getConfirmedQuoteForSellPrefill($business_id, $id);

            $lines = [];
            foreach ($quote->lines as $line) {
                $mappedLine = $this->quoteInvoiceReleaseService->buildSellLinePayload(
                    $business_id,
                    $quote,
                    $line,
                    (int) auth()->id()
                );

                $lines[] = [
                    'variation_id' => (int) $mappedLine['variation_id'],
                    'quantity' => (float) $mappedLine['quantity'],
                    'unit_price' => (float) $mappedLine['unit_price'],
                    'unit_price_inc_tax' => (float) $mappedLine['unit_price_inc_tax'],
                    'sell_line_note' => (string) ($mappedLine['sell_line_note'] ?? ''),
                ];
            }

            return $this->respondSuccess(__('lang_v1.success'), [
                'quote_id' => (int) $quote->id,
                'quote_number' => (string) ($quote->quote_number ?: $quote->uuid),
                'contact_id' => (int) $quote->contact_id,
                'contact_name' => (string) ($quote->customer_name ?: ($quote->contact->supplier_business_name ?? $quote->contact->name ?? '')),
                'location_id' => (int) $quote->location_id,
                'lines' => $lines,
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->respondWithError($e->getMessage());
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    public function send(SendQuoteRequest $request, int $id)
    {
        if (! auth()->user()->can('projectx.quote.send')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);

            $validated = $request->validated();
            $to = trim((string) ($validated['to_email'] ?? ''));
            if ($to === '') {
                $to = trim((string) ($quote->customer_email ?? optional($quote->contact)->email ?? ''));
            }
            if ($to === '') {
                throw new \InvalidArgumentException(__('projectx::lang.quote_recipient_required'));
            }

            $options = [];
            if (! empty($validated['subject'])) {
                $options['subject'] = $validated['subject'];
            }
            $options['public_url'] = route('projectx.quotes.public', ['publicToken' => $quote->public_token]);

            $this->quoteMailer->sendQuoteEmail($quote, $to, $options);

            $quote->sent_at = now();
            if (empty($quote->customer_email) && $to !== '') {
                $quote->customer_email = $to;
            }
            $quote->save();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('projectx::lang.quote_sent_success'));
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_sent_success')]);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWithError($e->getMessage());
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($e);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    protected function resolveQuoteLineDefaults(array $costingDropdowns, ?string $defaultCurrencyCode = null): array
    {
        $currencyOptions = (array) ($costingDropdowns['currency'] ?? []);
        $purchaseOptions = array_values(array_unique(array_filter(array_merge(
            array_values((array) ($costingDropdowns['purchase_uom'] ?? [])),
            Trim::UOM_OPTIONS
        ))));

        return [
            'currency' => (string) ($defaultCurrencyCode ?: (array_key_first($currencyOptions) ?? '')),
            'incoterm' => (string) ($costingDropdowns['incoterm'][0] ?? ''),
            'purchase_uom' => (string) ($purchaseOptions[0] ?? ''),
        ];
    }

    protected function normalizeQuoteLinesForView(array $lineInputs, array $defaults): array
    {
        $normalizedLines = [];

        foreach ($lineInputs as $line) {
            if (! is_array($line)) {
                continue;
            }

            $lineType = strtolower(trim((string) ($line['line_type'] ?? '')));
            $hasTrim = (int) ($line['trim_id'] ?? 0) > 0;
            if (! in_array($lineType, ['fabric', 'trim'], true)) {
                $lineType = $hasTrim ? 'trim' : 'fabric';
            }

            $normalizedLines[] = [
                'line_type' => $lineType,
                'fabric_id' => (string) ($line['fabric_id'] ?? ''),
                'trim_id' => (string) ($line['trim_id'] ?? ''),
                'qty' => $line['qty'] ?? 1,
                'purchase_uom' => $line['purchase_uom'] ?? ($defaults['purchase_uom'] ?? ''),
                'base_mill_price' => $line['base_mill_price'] ?? 0,
                'test_cost' => $line['test_cost'] ?? 0,
                'surcharge' => $line['surcharge'] ?? 0,
                'finish_uplift_pct' => $line['finish_uplift_pct'] ?? 0,
                'waste_pct' => $line['waste_pct'] ?? 0,
                'currency' => $line['currency'] ?? ($defaults['currency'] ?? ''),
                'incoterm' => $line['incoterm'] ?? ($defaults['incoterm'] ?? ''),
            ];
        }

        if (! empty($normalizedLines)) {
            return $normalizedLines;
        }

        return [[
            'line_type' => 'fabric',
            'fabric_id' => '',
            'trim_id' => '',
            'qty' => 1,
            'purchase_uom' => $defaults['purchase_uom'] ?? '',
            'base_mill_price' => 0,
            'test_cost' => 0,
            'surcharge' => 0,
            'finish_uplift_pct' => 0,
            'waste_pct' => 0,
            'currency' => $defaults['currency'] ?? '',
            'incoterm' => $defaults['incoterm'] ?? '',
        ]];
    }

    protected function buildQuoteLinesFromQuote(Quote $quote, array $defaults): array
    {
        $lineInputs = [];

        foreach ($quote->lines as $line) {
            $input = (array) ($line->costing_input ?? []);

            $lineInputs[] = [
                'line_type' => ! empty($line->trim_id) ? 'trim' : 'fabric',
                'fabric_id' => $line->fabric_id,
                'trim_id' => $line->trim_id,
                'qty' => $input['qty'] ?? 1,
                'purchase_uom' => $input['purchase_uom'] ?? ($defaults['purchase_uom'] ?? ''),
                'base_mill_price' => $input['base_mill_price'] ?? 0,
                'test_cost' => $input['test_cost'] ?? 0,
                'surcharge' => $input['surcharge'] ?? 0,
                'finish_uplift_pct' => $input['finish_uplift_pct'] ?? 0,
                'waste_pct' => $input['waste_pct'] ?? 0,
                'currency' => $input['currency'] ?? ($quote->currency ?: ($defaults['currency'] ?? '')),
                'incoterm' => $input['incoterm'] ?? ($quote->incoterm ?: ($defaults['incoterm'] ?? '')),
            ];
        }

        return $this->normalizeQuoteLinesForView($lineInputs, $defaults);
    }

    public function releaseInvoice(ReleaseQuoteInvoiceRequest $request, int $id)
    {
        $message = __('projectx::lang.quote_release_deprecated');

        if ($request->expectsJson() || $request->ajax()) {
            return $this->respondWithError($message);
        }

        return redirect()
            ->back()
            ->with('status', ['success' => false, 'msg' => $message]);
    }
}
