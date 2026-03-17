<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Contracts\QuoteMailerInterface;
use App\Http\Requests\ClearQuoteSignatureRequest;
use App\Http\Requests\ReleaseQuoteInvoiceRequest;
use App\Http\Requests\RevertQuoteToDraftRequest;
use App\Http\Requests\SendQuoteRequest;
use App\Http\Requests\SetPublicQuotePasswordRequest;
use App\Http\Requests\StoreProductBudgetQuoteRequest;
use App\Http\Requests\StoreProductQuoteRequest;
use App\Http\Requests\UpdateProductQuoteRequest;
use App\Product;
use App\ProductQuote;
use App\Utils\NumberFormatUtil;
use App\Utils\ProductCostingUtil;
use App\Utils\QuoteDisplayPresenter;
use App\Utils\QuoteInvoiceReleaseService;
use App\Utils\QuoteUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductQuoteController extends Controller
{
    protected QuoteUtil $quoteUtil;

    protected ProductCostingUtil $productCostingUtil;

    protected QuoteInvoiceReleaseService $quoteInvoiceReleaseService;

    protected QuoteMailerInterface $quoteMailer;

    protected QuoteDisplayPresenter $quoteDisplayPresenter;

    public function __construct(
        QuoteUtil $quoteUtil,
        ProductCostingUtil $productCostingUtil,
        QuoteInvoiceReleaseService $quoteInvoiceReleaseService,
        QuoteMailerInterface $quoteMailer,
        QuoteDisplayPresenter $quoteDisplayPresenter
    ) {
        $this->quoteUtil = $quoteUtil;
        $this->productCostingUtil = $productCostingUtil;
        $this->quoteInvoiceReleaseService = $quoteInvoiceReleaseService;
        $this->quoteMailer = $quoteMailer;
        $this->quoteDisplayPresenter = $quoteDisplayPresenter;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('product_quote.view')) {
            abort(403, __('product.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $quotes = ProductQuote::forBusiness($business_id)
                ->leftJoin('contacts as c', 'product_quotes.contact_id', '=', 'c.id')
                ->leftJoin('business_locations as bl', 'product_quotes.location_id', '=', 'bl.id')
                ->select([
                    'product_quotes.id',
                    'product_quotes.uuid',
                    'product_quotes.quote_number',
                    'product_quotes.quote_date',
                    'product_quotes.expires_at',
                    'product_quotes.grand_total',
                    'product_quotes.line_count',
                    'product_quotes.currency',
                    'product_quotes.incoterm',
                    'product_quotes.sent_at',
                    'product_quotes.confirmed_at',
                    'product_quotes.created_at',
                    'product_quotes.transaction_id',
                    'bl.name as location_name',
                    DB::raw('COALESCE(product_quotes.customer_name, c.name, c.supplier_business_name) as customer_name'),
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
                    if ((int) $row->line_count > 1) {
                        return '<span class="badge badge-light-primary">' . __('product.multi_product_quote') . '</span>';
                    }

                    return '<span class="badge badge-light-info">' . __('product.single_product_quote') . '</span>';
                })
                ->addColumn('quote_state', function ($row) {
                    if (! empty($row->transaction_id)) {
                        return '<span class="badge badge-light-success">' . __('product.quote_state_converted') . '</span>';
                    }
                    if (! empty($row->confirmed_at)) {
                        return '<span class="badge badge-light-primary">' . __('product.quote_state_confirmed') . '</span>';
                    }
                    if (! empty($row->sent_at)) {
                        return '<span class="badge badge-light-warning">' . __('product.quote_state_sent') . '</span>';
                    }

                    return '<span class="badge badge-light-secondary">' . __('product.quote_state_draft') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $canEdit = auth()->user()->can('product_quote.edit');
                    $canDelete = auth()->user()->can('product_quote.delete');
                    $canCreateSale = auth()->user()->can('direct_sell.access');
                    $canAdminOverride = auth()->user()->can('product_quote.admin_override');
                    $isEditable = empty($row->transaction_id) && empty($row->confirmed_at);
                    $isConfirmedUnlinked = ! empty($row->confirmed_at) && empty($row->transaction_id);

                    $actions = [];
                    $actions[] = '<a href="' . route('product.quotes.show', ['id' => $row->id]) . '" class="btn btn-sm btn-light-primary me-2"><i class="ki-duotone ki-eye fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> ' . __('product.view') . '</a>';

                    if (($isEditable || $canAdminOverride) && $canEdit) {
                        $actions[] = '<a href="' . route('product.quotes.edit', ['id' => $row->id]) . '" class="btn btn-sm btn-light-info me-2"><i class="ki-duotone ki-notepad-edit fs-5"><span class="path1"></span><span class="path2"></span></i> ' . __('product.edit') . '</a>';
                    }

                    if (($isEditable || $canAdminOverride) && $canDelete) {
                        $actions[] = '<form method="POST" action="' . route('product.quotes.destroy', ['id' => $row->id]) . '" class="d-inline-block me-2" onsubmit="return confirm(\'' . e(__('product.delete_quote_confirm')) . '\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-sm btn-light-danger"><i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i> ' . __('product.delete') . '</button></form>';
                    }

                    if ($isConfirmedUnlinked && $canCreateSale) {
                        $actions[] = '<a href="' . route('sells.create', ['product_quote_id' => $row->id]) . '" class="btn btn-sm btn-primary me-2"><i class="ki-duotone ki-basket fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i> ' . __('product.create_sale_from_quote') . '</a>';
                    }

                    if (! empty($row->transaction_id)) {
                        $actions[] = '<a href="' . route('product.sales.orders.show', ['id' => $row->transaction_id]) . '" class="btn btn-sm btn-light-success"><i class="ki-duotone ki-document fs-5"><span class="path1"></span><span class="path2"></span></i> ' . __('product.view_order') . '</a>';
                    }

                    return implode('', $actions);
                })
                ->rawColumns(['quote_type', 'quote_state', 'action'])
                ->make(true);
        }

        return view('product.quotes.index');
    }

    public function create(Request $request)
    {
        if (! auth()->user()->can('product_quote.create')) {
            abort(403, __('product.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        $customersDropdown = Contact::customersDropdown($business_id, false, true);
        $locationsDropdown = BusinessLocation::forDropdown($business_id, false, false);
        $costingDropdowns = $this->productCostingUtil->getDropdownOptions($business_id);
        $defaultCurrencyCode = $this->productCostingUtil->getDefaultCurrencyCode($business_id);
        $products = $this->getQuoteProductsForBusiness($business_id);
        $fabrics = $products;
        $trims = collect();

        $lineDefaults = $this->resolveQuoteLineDefaults($costingDropdowns, $defaultCurrencyCode);
        $quoteLines = $this->normalizeQuoteLinesForView((array) old('lines', []), $lineDefaults);
        $formatPayload = app(NumberFormatUtil::class)->buildViewPayload($this->resolveBusinessFromSession($request));

        return view('product.quotes.create', compact(
            'customersDropdown',
            'locationsDropdown',
            'costingDropdowns',
            'defaultCurrencyCode',
            'products',
            'fabrics',
            'trims',
            'quoteLines'
        ))->with($formatPayload);
    }

    public function store(StoreProductQuoteRequest $request)
    {
        if (! auth()->user()->can('product_quote.create')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->createMultiProductQuote(
                $business_id,
                $request->validated(),
                (int) auth()->id()
            );

            return redirect()
                ->route('product.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('product.quote_created_success')]);
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

    public function storeFromProduct(StoreProductBudgetQuoteRequest $request, int $product_id)
    {
        if (! auth()->user()->can('product_quote.create')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->createSingleProductQuote(
                $business_id,
                $product_id,
                $request->validated(),
                (int) auth()->id()
            );

            return redirect()
                ->route('product.detail', ['id' => $product_id, 'tab' => 'quotes', 'quote_id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('product.quote_created_success')]);
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
        if (! auth()->user()->can('product_quote.view')) {
            abort(403, __('product.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
        $publicUrl = route('product.quotes.public', ['publicToken' => $quote->public_token]);
        $quoteDisplay = $this->quoteDisplayPresenter->presentQuote($quote);

        $canAdminOverride = auth()->user()->can('product_quote.admin_override');
        $canEditQuote = auth()->user()->can('product_quote.edit');
        $canDeleteQuote = auth()->user()->can('product_quote.delete');
        $canOverrideAndEdit = $canAdminOverride && $canEditQuote;

        $quoteActionFlags = [
            'showEdit' => ((bool) ($quoteDisplay['isEditable'] ?? false) || $canAdminOverride) && $canEditQuote,
            'showDelete' => ((bool) ($quoteDisplay['isEditable'] ?? false) || $canAdminOverride) && $canDeleteQuote,
            'showRevertToDraft' => ! empty($quote->transaction_id) && $canOverrideAndEdit,
            'showClearSignature' => (bool) ($quoteDisplay['isConfirmedUnlinked'] ?? false) && $canOverrideAndEdit,
            'showOverrideNotice' => ! ((bool) ($quoteDisplay['isEditable'] ?? false))
                && (((bool) ($quoteDisplay['isEditable'] ?? false) || $canAdminOverride) && $canEditQuote),
            'canCreateSaleFromQuote' => (bool) ($quoteDisplay['isConfirmedUnlinked'] ?? false) && auth()->user()->can('direct_sell.access'),
            'canSendQuote' => auth()->user()->can('product_quote.send'),
        ];

        $recipientEmail = (string) ($quote->customer_email ?: (optional($quote->contact)->email ?? ''));

        return view('product.quotes.show', compact(
            'quote',
            'publicUrl',
            'quoteDisplay',
            'quoteActionFlags',
            'recipientEmail'
        ));
    }

    public function edit(int $id, Request $request)
    {
        if (! auth()->user()->can('product_quote.edit')) {
            abort(403, __('product.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
        $canAdminOverride = auth()->user()->can('product_quote.admin_override');

        if (! $quote->isEditable() && ! $canAdminOverride) {
            return redirect()
                ->route('product.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => false, 'msg' => __('product.quote_not_editable')]);
        }

        $customersDropdown = Contact::customersDropdown($business_id, false, true);
        $locationsDropdown = BusinessLocation::forDropdown($business_id, false, false);
        $costingDropdowns = $this->productCostingUtil->getDropdownOptions($business_id);
        $defaultCurrencyCode = $this->productCostingUtil->getDefaultCurrencyCode($business_id);
        $products = $this->getQuoteProductsForBusiness($business_id);
        $fabrics = $products;
        $trims = collect();

        $lineDefaults = $this->resolveQuoteLineDefaults($costingDropdowns, $defaultCurrencyCode);
        $oldLines = (array) old('lines', []);
        $quoteLines = ! empty($oldLines)
            ? $this->normalizeQuoteLinesForView($oldLines, $lineDefaults)
            : $this->buildQuoteLinesFromQuote($quote, $lineDefaults);
        $formatPayload = app(NumberFormatUtil::class)->buildViewPayload($this->resolveBusinessFromSession($request));

        return view('product.quotes.edit', compact(
            'quote',
            'customersDropdown',
            'locationsDropdown',
            'costingDropdowns',
            'defaultCurrencyCode',
            'products',
            'fabrics',
            'trims',
            'quoteLines'
        ))->with($formatPayload);
    }

    public function update(UpdateProductQuoteRequest $request, int $id)
    {
        if (! auth()->user()->can('product_quote.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
            $allowAdminOverride = auth()->user()->can('product_quote.admin_override');
            $quote = $this->quoteUtil->updateQuote($business_id, $quote, $request->validated(), $allowAdminOverride);

            return redirect()
                ->route('product.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('product.quote_updated_success')]);
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
        if (! auth()->user()->can('product_quote.delete')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
            $allowAdminOverride = auth()->user()->can('product_quote.admin_override');
            $this->quoteUtil->deleteQuote($business_id, $quote, $allowAdminOverride);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('product.quote_deleted_success'));
            }

            return redirect()
                ->route('product.quotes.index')
                ->with('status', ['success' => true, 'msg' => __('product.quote_deleted_success')]);
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
        if (! auth()->user()->can('product_quote.edit') || ! auth()->user()->can('product_quote.admin_override')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
            $quote = $this->quoteUtil->revertQuoteToDraft($business_id, $quote);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('product.quote_reverted_to_draft_success'), [
                    'quote_id' => (int) $quote->id,
                ]);
            }

            return redirect()
                ->route('product.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('product.quote_reverted_to_draft_success')]);
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
        if (! auth()->user()->can('product_quote.edit') || ! auth()->user()->can('product_quote.admin_override')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $quote = $this->quoteUtil->getQuoteByIdForBusiness($business_id, $id);
            $quote = $this->quoteUtil->clearQuoteConfirmation($business_id, $quote);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('product.quote_signature_cleared_success'), [
                    'quote_id' => (int) $quote->id,
                ]);
            }

            return redirect()
                ->route('product.quotes.show', ['id' => $quote->id])
                ->with('status', ['success' => true, 'msg' => __('product.quote_signature_cleared_success')]);
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
        if (! auth()->user()->can('product_quote.edit')) {
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
                ? __('product.password_removed_success')
                : __('product.password_set_success');

            return redirect()
                ->route('product.quotes.show', ['id' => $quote->id])
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
        if (! auth()->user()->can('product_quote.send')) {
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
                throw new \InvalidArgumentException(__('product.quote_recipient_required'));
            }

            $options = [];
            if (! empty($validated['subject'])) {
                $options['subject'] = $validated['subject'];
            }
            $options['public_url'] = route('product.quotes.public', ['publicToken' => $quote->public_token]);

            $this->quoteMailer->sendQuoteEmail($quote, $to, $options);

            $quote->sent_at = now();
            if (empty($quote->customer_email) && $to !== '') {
                $quote->customer_email = $to;
            }
            $quote->save();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('product.quote_sent_success'));
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('product.quote_sent_success')]);
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

    protected function getQuoteProductsForBusiness(int $business_id)
    {
        $products = Product::where('business_id', $business_id)
            ->where('is_inactive', 0)
            ->with([
                'variations' => function ($query) {
                    $query->select(['id', 'product_id', 'default_sell_price'])
                        ->orderBy('id');
                },
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $products->each(function (Product $product): void {
            $firstVariation = optional($product->variations)->first();
            $product->setAttribute('selling_price', (float) optional($firstVariation)->default_sell_price);
        });

        return $products;
    }

    protected function resolveQuoteLineDefaults(array $costingDropdowns, ?string $defaultCurrencyCode = null): array
    {
        $currencyOptions = (array) ($costingDropdowns['currency'] ?? []);
        $purchaseOptions = array_values(array_unique(array_filter(array_values((array) ($costingDropdowns['purchase_uom'] ?? [])))));

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

            $lineType = strtolower((string) ($line['line_type'] ?? ''));
            if ($lineType !== 'trim') {
                $lineType = 'fabric';
            }

            $productId = (string) (
                $line['product_id']
                ?? $line['id']
                ?? ($lineType === 'trim' ? ($line['trim_id'] ?? '') : '')
                ?? ''
            );

            $normalizedLines[] = [
                'line_type' => $lineType,
                'id' => (string) ($line['id'] ?? ($lineType !== 'trim' ? $productId : '')),
                'trim_id' => (string) ($line['trim_id'] ?? ($lineType === 'trim' ? $productId : '')),
                'product_id' => $productId,
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
            'id' => '',
            'trim_id' => '',
            'product_id' => '',
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

    protected function buildQuoteLinesFromQuote(ProductQuote $quote, array $defaults): array
    {
        $lineInputs = [];

        foreach ($quote->lines as $line) {
            $input = (array) ($line->costing_input ?? []);

            $lineInputs[] = [
                'line_type' => 'fabric',
                'id' => $line->product_id,
                'trim_id' => '',
                'product_id' => $line->product_id,
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

    protected function resolveBusinessFromSession(Request $request): ?object
    {
        $business = $request->session()->get('business');

        if (is_object($business)) {
            return $business;
        }

        if (is_array($business)) {
            return (object) $business;
        }

        return null;
    }

    public function releaseInvoice(ReleaseQuoteInvoiceRequest $request, int $id)
    {
        $message = __('product.quote_release_deprecated');

        if ($request->expectsJson() || $request->ajax()) {
            return $this->respondWithError($message);
        }

        return redirect()
            ->back()
            ->with('status', ['success' => false, 'msg' => $message]);
    }
}
