<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\SellPosController;
use App\Http\Controllers\Controller;
use App\Transaction;
use App\Utils\TransactionUtil;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Modules\ProjectX\Entities\Quote;
use Modules\ProjectX\Http\Requests\UpdateProjectxSalesOrderRequest;
use Modules\ProjectX\Http\Requests\UpdateProjectxOrderHoldStatusRequest;
use Modules\ProjectX\Utils\SalesOrderEditUtil;
use Yajra\DataTables\Facades\DataTables;

class SalesController extends Controller
{
    protected ?SalesOrderEditUtil $salesOrderEditUtil;

    protected ?SellPosController $sellPosController;

    public function __construct(?SalesOrderEditUtil $salesOrderEditUtil = null, ?SellPosController $sellPosController = null)
    {
        $this->salesOrderEditUtil = $salesOrderEditUtil;
        $this->sellPosController = $sellPosController;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('sell.view') && ! auth()->user()->can('direct_sell.view')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $sells = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->join('projectx_quotes as pq', function ($join) use ($business_id) {
                    $join->on('pq.transaction_id', '=', 'transactions.id')
                        ->where('pq.business_id', '=', $business_id);
                })
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
                ->select([
                    'transactions.id',
                    'transactions.invoice_no',
                    'transactions.transaction_date',
                    'transactions.final_total',
                    'transactions.payment_status',
                    'transactions.status',
                    'transactions.sub_status',
                    'contacts.name as customer_name',
                    'bl.name as location_name',
                    'pq.id as quote_id',
                    'pq.quote_number',
                    DB::raw('EXISTS(SELECT 1 FROM projectx_quote_lines ql_trim WHERE ql_trim.quote_id = pq.id AND ql_trim.trim_id IS NOT NULL) as has_trim_lines'),
                    DB::raw('EXISTS(SELECT 1 FROM projectx_quote_lines ql_fabric WHERE ql_fabric.quote_id = pq.id AND ql_fabric.fabric_id IS NOT NULL) as has_fabric_lines'),
                ]);

            return DataTables::of($sells)
                ->addColumn('status_badge', function ($row) {
                    return $this->renderOrderStatusBadge((string) $row->status, (string) $row->sub_status);
                })
                ->addColumn('quote_number', function ($row) {
                    return $row->quote_number ?: '-';
                })
                ->addColumn('quote_type_badge', function ($row) {
                    $hasTrimLines = (int) ($row->has_trim_lines ?? 0) === 1;
                    $hasFabricLines = (int) ($row->has_fabric_lines ?? 0) === 1;

                    return $this->renderQuoteTypeBadge($hasTrimLines, $hasFabricLines);
                })
                ->addColumn('action', function ($row) {
                    $actions = [];
                    $actions[] = '<a href="' . route('projectx.sales.orders.show', $row->id) . '" class="btn btn-sm btn-light-primary me-2"><i class="ki-duotone ki-eye fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> ' . __('projectx::lang.view') . '</a>';
                    $actions[] = '<a href="' . route('projectx.quotes.show', ['id' => $row->quote_id]) . '" class="btn btn-sm btn-light-info me-2"><i class="ki-duotone ki-document fs-5"><span class="path1"></span><span class="path2"></span></i> ' . __('projectx::lang.view_quote') . '</a>';

                    if (auth()->user()->can('projectx.sales_order.edit')) {
                        $actions[] = '<a href="' . route('projectx.sales.orders.edit', ['id' => $row->id]) . '" class="btn btn-sm btn-light-success me-2"><i class="ki-duotone ki-notepad-edit fs-5"><span class="path1"></span><span class="path2"></span></i> ' . __('projectx::lang.edit_order') . '</a>';
                    }

                    if (auth()->user()->can('projectx.sales_order.update_status')) {
                        $isOnHold = ((string) $row->sub_status) === 'on_hold' ? '1' : '0';
                        $label = $isOnHold === '1' ? __('projectx::lang.remove_hold') : __('projectx::lang.mark_on_hold');
                        $btnClass = $isOnHold === '1' ? 'btn-light-danger' : 'btn-light-warning';
                        $actions[] = '<button type="button" class="btn btn-sm ' . $btnClass . ' js-toggle-order-hold" data-order-id="' . (int) $row->id . '" data-is-on-hold="' . $isOnHold . '">' . $label . '</button>';
                    }

                    return implode('', $actions);
                })
                ->editColumn('final_total', function ($row) {
                    $currency = session('currency');
                    $symbol = ! empty($currency['symbol']) ? $currency['symbol'] : '$';
                    $currencyPrecision = max(0, (int) session('business.currency_precision', 2));
                    $decimalSeparator = (string) ($currency['decimal_separator'] ?? '.');
                    $thousandSeparator = (string) ($currency['thousand_separator'] ?? ',');

                    return $symbol . ' ' . number_format((float) $row->final_total, $currencyPrecision, $decimalSeparator, $thousandSeparator);
                })
                ->editColumn('transaction_date', function ($row) {
                    return \Carbon\Carbon::parse($row->transaction_date)->format('M d, Y h:i A');
                })
                ->editColumn('payment_status', function ($row) {
                    $badges = [
                        'paid' => 'badge-light-success',
                        'due' => 'badge-light-danger',
                        'partial' => 'badge-light-warning',
                        'overdue' => 'badge-light-danger',
                    ];
                    $class = $badges[$row->payment_status] ?? 'badge-light-info';
                    $label = __('projectx::lang.' . $row->payment_status);

                    return '<span class="badge ' . $class . '">' . $label . '</span>';
                })
                ->rawColumns(['action', 'payment_status', 'status_badge', 'quote_type_badge'])
                ->make(true);
        }

        return view('projectx::sales.orders-index');
    }

    public function edit($id, Request $request)
    {
        if (! auth()->user()->can('projectx.sales_order.edit')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $transaction = $this->getSalesOrderEditUtil()->getProjectxSellTransactionForEdit($business_id, (int) $id);

        $viewData = $this->getSalesOrderEditUtil()->buildEditViewData($business_id, $transaction);

        return view('projectx::sales.orders-edit', $viewData);
    }

    public function update(UpdateProjectxSalesOrderRequest $request, $id)
    {
        if (! auth()->user()->can('projectx.sales_order.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $transaction = $this->getSalesOrderEditUtil()->getProjectxSellTransactionForEdit($business_id, (int) $id);

            $rootResponse = $this->getSellPosController()->update($request, (int) $transaction->id);
            $normalizedResult = $this->normalizeRootUpdateResponse($rootResponse);

            if ($normalizedResult['success']) {
                $this->getSalesOrderEditUtil()->persistDeliveryDate(
                    $business_id,
                    (int) $transaction->id,
                    $request->input('delivery_date')
                );

                return redirect()
                    ->route('projectx.sales.orders.show', ['id' => $transaction->id])
                    ->with('status', [
                        'success' => true,
                        'msg' => $normalizedResult['msg'] ?: __('projectx::lang.order_updated_success'),
                    ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', [
                    'success' => false,
                    'msg' => $normalizedResult['msg'] ?: __('messages.something_went_wrong'),
                ]);
        } catch (ModelNotFoundException $e) {
            abort(404);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function productSearch(Request $request)
    {
        if (! auth()->user()->can('projectx.sales_order.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $location_id = (int) $request->input('location_id', 0);

            if ($location_id <= 0) {
                return response()->json([
                    'results' => [],
                    'pagination' => ['more' => false],
                ]);
            }

            $search = $request->input('term', $request->input('q'));
            $page = max(1, (int) $request->input('page', 1));

            $data = $this->getSalesOrderEditUtil()->searchSellableVariations(
                $business_id,
                $location_id,
                is_string($search) ? $search : null,
                $page
            );

            return response()->json([
                'results' => $data['results'],
                'pagination' => ['more' => (bool) $data['has_more']],
            ]);
        } catch (\Exception $e) {
            \Log::warning('ProjectX sales order product search failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
        }
    }

    public function show($id, Request $request)
    {
        if (! auth()->user()->can('sell.view') && ! auth()->user()->can('direct_sell.view')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        $quote = Quote::forBusiness($business_id)
            ->where('transaction_id', (int) $id)
            ->firstOrFail();

        $transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->with([
                'contact',
                'location',
                'sell_lines.product',
                'sell_lines.variations',
                'payment_lines',
            ])
            ->findOrFail($id);

        $currency = session('currency');
        $statusBadge = $this->renderOrderStatusBadge((string) $transaction->status, (string) $transaction->sub_status);
        $transactionUtil = app(TransactionUtil::class);
        $transactionDateFormatted = ! empty($transaction->transaction_date)
            ? $transactionUtil->format_date($transaction->transaction_date, true)
            : null;
        $deliveryDateFormatted = ! empty($transaction->delivery_date)
            ? $transactionUtil->format_date($transaction->delivery_date)
            : null;

        return view('projectx::sales.orders-show', compact(
            'transaction',
            'currency',
            'quote',
            'statusBadge',
            'transactionDateFormatted',
            'deliveryDateFormatted'
        ));
    }

    public function updateHoldStatus(UpdateProjectxOrderHoldStatusRequest $request, int $id)
    {
        if (! auth()->user()->can('projectx.sales_order.update_status')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $isOnHold = (bool) $request->boolean('is_on_hold');

            $transaction = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->whereExists(function ($query) use ($business_id) {
                    $query->select(DB::raw(1))
                        ->from('projectx_quotes as pq')
                        ->whereColumn('pq.transaction_id', 'transactions.id')
                        ->where('pq.business_id', $business_id);
                })
                ->findOrFail($id);

            $transaction->sub_status = $isOnHold ? 'on_hold' : null;
            $transaction->save();

            $response = [
                'is_on_hold' => $isOnHold,
                'status_badge' => $this->renderOrderStatusBadge((string) $transaction->status, (string) $transaction->sub_status),
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('projectx::lang.order_hold_updated_success'), $response);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.order_hold_updated_success')]);
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

    protected function renderOrderStatusBadge(string $status, string $subStatus = ''): string
    {
        $statusClassMap = [
            'final' => 'badge-light-success',
            'draft' => 'badge-light-secondary',
            'ordered' => 'badge-light-primary',
            'partial' => 'badge-light-warning',
        ];
        $subStatusClassMap = [
            'on_hold' => 'badge-light-danger',
        ];

        $statusClass = $statusClassMap[$status] ?? 'badge-light-info';
        $statusLabel = __('projectx::lang.' . $status);
        $html = '<span class="badge ' . $statusClass . ' me-1">' . e($statusLabel) . '</span>';

        if ($subStatus !== '') {
            $subClass = $subStatusClassMap[$subStatus] ?? 'badge-light-info';
            $subLabel = __('projectx::lang.' . $subStatus);
            $html .= '<span class="badge ' . $subClass . '">' . e($subLabel) . '</span>';
        }

        return $html;
    }

    protected function renderQuoteTypeBadge(bool $hasTrimLines, bool $hasFabricLines): string
    {
        if ($hasTrimLines && $hasFabricLines) {
            return '<span class="badge badge-light-warning">' . __('projectx::lang.quote_type_mixed') . '</span>';
        }

        if ($hasTrimLines) {
            return '<span class="badge badge-light-info">' . __('projectx::lang.quote_type_trims') . '</span>';
        }

        return '<span class="badge badge-light-primary">' . __('projectx::lang.quote_type_fabric') . '</span>';
    }

    protected function getSalesOrderEditUtil(): SalesOrderEditUtil
    {
        if (! $this->salesOrderEditUtil) {
            $this->salesOrderEditUtil = app(SalesOrderEditUtil::class);
        }

        return $this->salesOrderEditUtil;
    }

    protected function getSellPosController(): SellPosController
    {
        if (! $this->sellPosController) {
            $this->sellPosController = app(SellPosController::class);
        }

        return $this->sellPosController;
    }

    protected function normalizeRootUpdateResponse($rootResponse): array
    {
        if (is_array($rootResponse)) {
            return [
                'success' => (bool) ($rootResponse['success'] ?? false),
                'msg' => (string) ($rootResponse['msg'] ?? ''),
            ];
        }

        if ($rootResponse instanceof RedirectResponse) {
            $status = null;
            $session = $rootResponse->getSession();
            if ($session) {
                $status = $session->get('status');
            }

            if (is_array($status)) {
                return [
                    'success' => (bool) ($status['success'] ?? false),
                    'msg' => (string) ($status['msg'] ?? ''),
                ];
            }
        }

        return [
            'success' => false,
            'msg' => '',
        ];
    }
}
