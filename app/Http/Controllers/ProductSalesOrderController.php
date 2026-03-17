<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductOrderHoldStatusRequest;
use App\Http\Requests\UpdateProductSalesOrderRequest;
use App\ProductQuote;
use App\Transaction;
use App\Utils\SalesOrderEditUtil;
use App\Utils\TransactionUtil;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Yajra\DataTables\Facades\DataTables;

class ProductSalesOrderController extends Controller
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
            abort(403, __('product.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $sells = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->join('product_quotes as pq', function ($join) use ($business_id) {
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
                    'pq.line_count',
                ]);

            return DataTables::of($sells)
                ->addColumn('status_badge', function ($row) {
                    return $this->renderOrderStatusBadge((string) $row->status, (string) $row->sub_status);
                })
                ->addColumn('quote_number', function ($row) {
                    return $row->quote_number ?: '-';
                })
                ->addColumn('quote_type_badge', function ($row) {
                    if ((int) ($row->line_count ?? 0) > 1) {
                        return '<span class="badge badge-light-primary">' . __('product.quote_type_product') . '</span>';
                    }

                    return '<span class="badge badge-light-info">' . __('product.quote_type_product') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $actions = [];
                    $actions[] = '<a href="' . route('product.sales.orders.show', $row->id) . '" class="btn btn-sm btn-light-primary me-2"><i class="ki-duotone ki-eye fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> ' . __('product.view') . '</a>';
                    $actions[] = '<a href="' . route('product.quotes.show', ['id' => $row->quote_id]) . '" class="btn btn-sm btn-light-info me-2"><i class="ki-duotone ki-document fs-5"><span class="path1"></span><span class="path2"></span></i> ' . __('product.view_quote') . '</a>';

                    if (auth()->user()->can('product_sales_order.edit')) {
                        $actions[] = '<a href="' . route('product.sales.orders.edit', ['id' => $row->id]) . '" class="btn btn-sm btn-light-success me-2"><i class="ki-duotone ki-notepad-edit fs-5"><span class="path1"></span><span class="path2"></span></i> ' . __('product.edit_order') . '</a>';
                    }

                    if (auth()->user()->can('product_sales_order.update_status')) {
                        $isOnHold = ((string) $row->sub_status) === 'on_hold' ? '1' : '0';
                        $label = $isOnHold === '1' ? __('product.remove_hold') : __('product.mark_on_hold');
                        $btnClass = $isOnHold === '1' ? 'btn-light-danger' : 'btn-light-warning';
                        $actions[] = '<button type="button" class="btn btn-sm ' . $btnClass . ' js-toggle-order-hold" data-order-id="' . (int) $row->id . '" data-is-on-hold="' . $isOnHold . '">' . $label . '</button>';
                    }

                    if (auth()->user()->can('sell.delete') || auth()->user()->can('direct_sell.delete') || auth()->user()->can('so.delete')) {
                        $actions[] = '<form method="POST" action="' . route('product.sales.orders.destroy', ['id' => $row->id]) . '" class="d-inline-block ms-2" onsubmit="return confirm(\'' . e(__('product.delete')) . '?\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-sm btn-light-danger"><i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i> ' . __('product.delete') . '</button></form>';
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
                    $label = __('product.' . $row->payment_status);

                    return '<span class="badge ' . $class . '">' . $label . '</span>';
                })
                ->rawColumns(['action', 'payment_status', 'status_badge', 'quote_type_badge'])
                ->make(true);
        }

        return view('product.sales.orders-index');
    }

    public function edit($id, Request $request)
    {
        if (! auth()->user()->can('product_sales_order.edit')) {
            abort(403, __('product.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $transaction = $this->getSalesOrderEditUtil()->getProductQuoteSellTransactionForEdit($business_id, (int) $id);

        $viewData = $this->getSalesOrderEditUtil()->buildEditViewData($business_id, $transaction);

        return view('product.sales.orders-edit', $viewData);
    }

    public function update(UpdateProductSalesOrderRequest $request, $id)
    {
        if (! auth()->user()->can('product_sales_order.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $transaction = $this->getSalesOrderEditUtil()->getProductQuoteSellTransactionForEdit($business_id, (int) $id);

            $rootResponse = $this->getSellPosController()->update($request, (int) $transaction->id);
            $normalizedResult = $this->normalizeRootUpdateResponse($rootResponse);

            if ($normalizedResult['success']) {
                $this->getSalesOrderEditUtil()->persistDeliveryDate(
                    $business_id,
                    (int) $transaction->id,
                    $request->input('delivery_date')
                );

                return redirect()
                    ->route('product.sales.orders.show', ['id' => $transaction->id])
                    ->with('status', [
                        'success' => true,
                        'msg' => $normalizedResult['msg'] ?: __('product.order_updated_success'),
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
        if (! auth()->user()->can('product_sales_order.edit')) {
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
            \Log::warning('Product sales order product search failed', [
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
            abort(403, __('product.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        $quote = ProductQuote::forBusiness($business_id)
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

        return view('product.sales.orders-show', compact(
            'transaction',
            'currency',
            'quote',
            'statusBadge',
            'transactionDateFormatted',
            'deliveryDateFormatted'
        ));
    }

    public function updateHoldStatus(UpdateProductOrderHoldStatusRequest $request, int $id)
    {
        if (! auth()->user()->can('product_sales_order.update_status')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $isOnHold = (bool) $request->boolean('is_on_hold');

            $transaction = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->whereExists(function ($query) use ($business_id) {
                    $query->select(DB::raw(1))
                        ->from('product_quotes as pq')
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
                return $this->respondSuccess(__('product.order_hold_updated_success'), $response);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('product.order_hold_updated_success')]);
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

    public function destroy(Request $request, int $id)
    {
        if (! auth()->user()->can('sell.delete') && ! auth()->user()->can('direct_sell.delete') && ! auth()->user()->can('so.delete')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->findOrFail($id);

            DB::beginTransaction();
            $output = app(TransactionUtil::class)->deleteSale($business_id, (int) $transaction->id);
            $success = is_array($output) && ! empty($output['success']);
            $message = is_array($output) ? (string) ($output['msg'] ?? '') : '';

            if ($success) {
                ProductQuote::where('business_id', $business_id)
                    ->where('transaction_id', (int) $transaction->id)
                    ->update(['transaction_id' => null]);
            }

            DB::commit();

            if ($message === '') {
                $message = $success ? __('lang_v1.deleted_success') : __('messages.something_went_wrong');
            }

            if ($request->expectsJson() || $request->ajax()) {
                if ($success) {
                    return $this->respondSuccess($message);
                }

                return $this->respondWithError($message !== '' ? $message : __('messages.something_went_wrong'));
            }

            if ($success) {
                return redirect()
                    ->route('product.sales.orders.index')
                    ->with('status', ['success' => true, 'msg' => $message]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => $message !== '' ? $message : __('messages.something_went_wrong')]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
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
        $statusLabel = __('product.' . $status);
        $html = '<span class="badge ' . $statusClass . ' me-1">' . e($statusLabel) . '</span>';

        if ($subStatus !== '') {
            $subClass = $subStatusClassMap[$subStatus] ?? 'badge-light-info';
            $subLabel = __('product.' . $subStatus);
            $html .= '<span class="badge ' . $subClass . '">' . e($subLabel) . '</span>';
        }

        return $html;
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
