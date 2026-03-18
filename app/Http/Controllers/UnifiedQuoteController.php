<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Utils\UnifiedQuoteListUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UnifiedQuoteController extends Controller
{
    public function index(Request $request)
    {
        $canSales = $request->user()->can('quotation.view_all') || $request->user()->can('quotation.view_own');
        $canProduct = $request->user()->can('product_quote.view');

        if (! $canSales && ! $canProduct) {
            abort(403, __('messages.unauthorized'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        return view('quotes.unified_index', [
            'canSales' => $canSales,
            'canProduct' => $canProduct,
            'business_locations' => $business_locations,
            'customers' => $customers,
        ]);
    }

    public function data(Request $request, UnifiedQuoteListUtil $unifiedQuoteListUtil)
    {
        $canSales = $request->user()->can('quotation.view_all') || $request->user()->can('quotation.view_own');
        $canProduct = $request->user()->can('product_quote.view');

        if (! $canSales && ! $canProduct) {
            abort(403, __('messages.unauthorized'));
        }

        $business_id = (int) $request->session()->get('user.business_id');

        $input = array_merge($request->all(), [
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'location_id' => $request->input('location_id'),
                'customer_id' => $request->input('customer_id'),
                'quote_kind_filter' => $request->input('quote_kind_filter', 'all'),
            ]);

            $query = $unifiedQuoteListUtil->buildWrappedQuery(
                $business_id,
                $canSales,
                $canProduct,
                $input,
                $request->user()
            );

            $symbol = session('currency.symbol') ?? '$';
            $currency = (array) session('currency', []);
            $currencyPrecision = max(0, (int) session('business.currency_precision', 2));
            $decimalSeparator = (string) ($currency['decimal_separator'] ?? '.');
            $thousandSeparator = (string) ($currency['thousand_separator'] ?? ',');

            return DataTables::of($query)
                ->orderColumn('quote_sort_at', 'unified_quotes.quote_sort_at $1')
                ->orderColumn('ref_no', 'unified_quotes.ref_no $1')
                ->orderColumn('customer_name', 'unified_quotes.customer_name $1')
                ->orderColumn('location_name', 'unified_quotes.location_name $1')
                ->orderColumn('amount', 'unified_quotes.amount $1')
                ->filterColumn('ref_no', function ($q, $keyword) {
                    $q->where('unified_quotes.ref_no', 'like', '%' . $keyword . '%');
                })
                ->filterColumn('customer_name', function ($q, $keyword) {
                    $q->where('unified_quotes.customer_name', 'like', '%' . $keyword . '%');
                })
                ->filterColumn('location_name', function ($q, $keyword) {
                    $q->where('unified_quotes.location_name', 'like', '%' . $keyword . '%');
                })
                ->editColumn('quote_sort_at', function ($row) {
                    return $row->quote_sort_at
                        ? \Carbon\Carbon::parse($row->quote_sort_at)->format('M d, Y h:i A')
                        : '-';
                })
                ->editColumn('amount', function ($row) use ($symbol, $currencyPrecision, $decimalSeparator, $thousandSeparator) {
                    return $symbol . ' ' . number_format((float) $row->amount, $currencyPrecision, $decimalSeparator, $thousandSeparator);
                })
                ->addColumn('quote_type', function ($row) {
                    if ($row->quote_kind === 'sales_quotation') {
                        return '<span class="badge badge-light-primary">' . e(__('lang_v1.quote_kind_sales_quotation')) . '</span>';
                    }

                    return '<span class="badge badge-light-info">' . e(__('lang_v1.quote_kind_product_quote')) . '</span>';
                })
                ->addColumn('status_label', function ($row) {
                    if ($row->quote_kind === 'sales_quotation') {
                        return '<span class="badge badge-light-secondary">' . e(__('lang_v1.quotation')) . '</span>';
                    }
                    $map = [
                        'converted' => ['class' => 'badge-light-success', 'key' => 'product.quote_state_converted'],
                        'confirmed' => ['class' => 'badge-light-primary', 'key' => 'product.quote_state_confirmed'],
                        'sent' => ['class' => 'badge-light-warning', 'key' => 'product.quote_state_sent'],
                        'draft' => ['class' => 'badge-light-secondary', 'key' => 'product.quote_state_draft'],
                    ];
                    $s = $map[$row->status_raw] ?? $map['draft'];

                    return '<span class="badge ' . $s['class'] . '">' . e(__($s['key'])) . '</span>';
                })
                ->addColumn('action', function ($row) use ($canSales, $canProduct) {
                    $id = (int) $row->entity_id;
                    if ($row->quote_kind === 'sales_quotation') {
                        $html = '<div class="d-flex flex-wrap gap-1 justify-content-end">';
                        $html .= '<a href="' . e(action([\App\Http\Controllers\SellController::class, 'show'], [$id])) . '" class="btn btn-sm btn-light-primary btn-modal" data-href="' . e(action([\App\Http\Controllers\SellController::class, 'show'], [$id])) . '" data-container=".view_modal">' . e(__('messages.view')) . '</a>';
                        if ($canSales && (auth()->user()->can('quotation.update') || auth()->user()->can('draft.update'))) {
                            if ((int) $row->is_direct_sale === 1) {
                                $html .= '<a href="' . e(action([\App\Http\Controllers\SellController::class, 'edit'], [$id])) . '" class="btn btn-sm btn-light-info" target="_blank" rel="noopener">' . e(__('messages.edit')) . '</a>';
                            } else {
                                $html .= '<a href="' . e(action([\App\Http\Controllers\SellPosController::class, 'edit'], [$id])) . '" class="btn btn-sm btn-light-info" target="_blank" rel="noopener">' . e(__('messages.edit')) . '</a>';
                            }
                        }
                        if (config('constants.enable_download_pdf')) {
                            $html .= '<a href="' . e(route('quotation.downloadPdf', ['id' => $id])) . '" class="btn btn-sm btn-light-success" target="_blank" rel="noopener">' . e(__('lang_v1.download_pdf')) . '</a>';
                        }
                        $html .= '</div>';

                        return $html;
                    }

                    $html = '<div class="d-flex flex-wrap gap-1 justify-content-end">';
                    $html .= '<a href="' . e(route('product.quotes.show', ['id' => $id])) . '" class="btn btn-sm btn-light-primary">' . e(__('product.view')) . '</a>';
                    if ($canProduct && auth()->user()->can('product_quote.edit')) {
                        $converted = $row->status_raw === 'converted';
                        $confirmed = $row->status_raw === 'confirmed';
                        if (! $converted && (! $confirmed || auth()->user()->can('product_quote.admin_override'))) {
                            $html .= '<a href="' . e(route('product.quotes.edit', ['id' => $id])) . '" class="btn btn-sm btn-light-info">' . e(__('product.edit')) . '</a>';
                        }
                    }
                    $html .= '</div>';

                    return $html;
                })
                ->rawColumns(['quote_type', 'status_label', 'action'])
                ->make(true);
    }
}
