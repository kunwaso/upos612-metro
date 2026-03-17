<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Product;
use App\Transaction;
use App\TransactionPayment;
use App\VariationLocationDetails;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProjectX\Utils\ProjectXUtil;

class DashboardController extends Controller
{
    protected $projectXUtil;

    public function __construct(ProjectXUtil $projectXUtil)
    {
        $this->projectXUtil = $projectXUtil;
    }

    public function index()
    {
        if (! auth()->user()->can('product.view') && ! auth()->user()->can('sell.view')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = request()->session()->get('user.business_id');
        $data = $this->projectXUtil->getDashboardData($business_id);

        $currency = session('currency');

        return view('projectx::dashboard.index', compact('data', 'currency'));
    }

    public function getSidebarActivity(Request $request)
    {
        if (! $request->ajax()) {
            abort(404);
        }

        $business_id = $request->session()->get('user.business_id');
        $activities  = collect();

        $sells = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->with('contact:id,name,supplier_business_name')
            ->latest('transaction_date')
            ->limit(5)
            ->get(['id', 'invoice_no', 'final_total', 'contact_id', 'transaction_date', 'payment_status']);

        foreach ($sells as $sell) {
            $contact_name = ! empty($sell->contact->supplier_business_name)
                ? $sell->contact->supplier_business_name
                : ($sell->contact->name ?? 'Walk-in Customer');
            $activities->push([
                'type'       => 'sell',
                'icon'       => 'ki-sms',
                'icon_paths' => 2,
                'title'      => __('lang_v1.new_sale') . ': ' . $sell->invoice_no,
                'sub_label'  => $contact_name,
                'amount'     => $sell->final_total,
                'ref_no'     => $sell->invoice_no,
                'link'       => action([\App\Http\Controllers\SellController::class, 'show'], [$sell->id]),
                'occurred_at'=> $sell->transaction_date,
            ]);
        }

        $purchases = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->with('contact:id,name,supplier_business_name')
            ->latest('transaction_date')
            ->limit(4)
            ->get(['id', 'ref_no', 'final_total', 'contact_id', 'transaction_date']);

        foreach ($purchases as $purchase) {
            $supplier = ! empty($purchase->contact->supplier_business_name)
                ? $purchase->contact->supplier_business_name
                : ($purchase->contact->name ?? '');
            $activities->push([
                'type'       => 'purchase',
                'icon'       => 'ki-credit-cart',
                'icon_paths' => 2,
                'title'      => __('purchase.purchases') . ': ' . $purchase->ref_no,
                'sub_label'  => $supplier,
                'amount'     => $purchase->final_total,
                'ref_no'     => $purchase->ref_no,
                'link'       => action([\App\Http\Controllers\PurchaseController::class, 'show'], [$purchase->id]),
                'occurred_at'=> $purchase->transaction_date,
            ]);
        }

        $expenses = Transaction::where('business_id', $business_id)
            ->where('type', 'expense')
            ->latest('transaction_date')
            ->limit(4)
            ->get(['id', 'ref_no', 'final_total', 'transaction_date', 'additional_notes']);

        foreach ($expenses as $expense) {
            $activities->push([
                'type'       => 'expense',
                'icon'       => 'ki-briefcase',
                'icon_paths' => 2,
                'title'      => __('expense.expenses') . ': ' . ($expense->ref_no ?: '#' . $expense->id),
                'sub_label'  => $expense->additional_notes ? \Str::limit($expense->additional_notes, 40) : '',
                'amount'     => $expense->final_total,
                'ref_no'     => $expense->ref_no ?: '#' . $expense->id,
                'link'       => '#',
                'occurred_at'=> $expense->transaction_date,
            ]);
        }

        $payments = TransactionPayment::join('transactions as t', 't.id', '=', 'transaction_payments.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereNull('transaction_payments.parent_id')
            ->latest('transaction_payments.created_at')
            ->limit(4)
            ->get([
                'transaction_payments.id',
                'transaction_payments.amount',
                'transaction_payments.method',
                'transaction_payments.transaction_id',
                'transaction_payments.created_at',
                't.invoice_no',
                't.id as txn_id',
            ]);

        foreach ($payments as $payment) {
            $activities->push([
                'type'       => 'payment',
                'icon'       => 'ki-bank',
                'icon_paths' => 2,
                'title'      => __('lang_v1.payment') . ': ' . $payment->invoice_no,
                'sub_label'  => ucfirst($payment->method ?? ''),
                'amount'     => $payment->amount,
                'ref_no'     => $payment->invoice_no,
                'link'       => action([\App\Http\Controllers\SellController::class, 'show'], [$payment->txn_id]),
                'occurred_at'=> $payment->created_at,
            ]);
        }

        $products = Product::where('business_id', $business_id)
            ->latest()
            ->limit(4)
            ->get(['id', 'name', 'sku', 'created_at']);

        foreach ($products as $product) {
            $activities->push([
                'type'       => 'product',
                'icon'       => 'ki-basket',
                'icon_paths' => 4,
                'title'      => __('product.add_new_product') . ': ' . $product->name,
                'sub_label'  => $product->sku,
                'amount'     => null,
                'ref_no'     => $product->sku,
                'link'       => action([\App\Http\Controllers\ProductController::class, 'edit'], [$product->id]),
                'occurred_at'=> $product->created_at,
            ]);
        }

        $adjustments = Transaction::where('business_id', $business_id)
            ->where('type', 'stock_adjustment')
            ->latest('transaction_date')
            ->limit(3)
            ->get(['id', 'ref_no', 'final_total', 'transaction_date', 'additional_notes']);

        foreach ($adjustments as $adj) {
            $activities->push([
                'type'       => 'stock_adjustment',
                'icon'       => 'ki-abstract-26',
                'icon_paths' => 2,
                'title'      => __('stock_adjustment.stock_adjustment') . ': ' . ($adj->ref_no ?: '#' . $adj->id),
                'sub_label'  => $adj->additional_notes ? \Str::limit($adj->additional_notes, 40) : '',
                'amount'     => null,
                'ref_no'     => $adj->ref_no ?: '#' . $adj->id,
                'link'       => '#',
                'occurred_at'=> $adj->transaction_date,
            ]);
        }

        $out_of_stock_products = VariationLocationDetails::query()
            ->join('products as p', 'p.id', '=', 'variation_location_details.product_id')
            ->where('p.business_id', $business_id)
            ->where('p.enable_stock', 1)
            ->groupBy('variation_location_details.product_id', 'p.name')
            ->havingRaw('SUM(variation_location_details.qty_available) <= 0')
            ->limit(5)
            ->pluck('p.name', 'variation_location_details.product_id');

        if ($out_of_stock_products->isNotEmpty()) {
            $names = $out_of_stock_products->values()->implode(', ');
            $activities->push([
                'type'       => 'out_of_stock',
                'icon'       => 'ki-information-5',
                'icon_paths' => 2,
                'title'      => __('lang_v1.item_out_of_stock') . ' (' . $out_of_stock_products->count() . ')',
                'sub_label'  => \Str::limit($names, 60),
                'amount'     => null,
                'ref_no'     => __('report.stock_report'),
                'link'       => url('/reports/stock-report'),
                'occurred_at'=> \Carbon::now(),
                'is_alert'   => true,
            ]);
        }

        $feed = $activities
            ->sortByDesc(fn ($item) => $item['occurred_at'])
            ->take(20)
            ->map(function ($item) {
                $item['time_ago'] = \Carbon::parse($item['occurred_at'])->diffForHumans();
                $item['amount_formatted'] = $item['amount'] !== null
                    ? number_format((float) $item['amount'], 2)
                    : null;
                unset($item['occurred_at']);

                return $item;
            })
            ->values();

        return response()->json($feed);
    }
}
