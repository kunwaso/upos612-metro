<?php

namespace Modules\ProjectX\Utils;

use App\Product;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectXUtil
{
    public function getDashboardData($business_id)
    {
        $total_products = Product::where('business_id', $business_id)
            ->where('is_inactive', 0)
            ->count();

        $today = Carbon::today()->toDateString();
        $month_start = Carbon::now()->startOfMonth()->toDateString();
        $month_end = Carbon::now()->endOfMonth()->toDateString();

        $sales_today = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', $today)
            ->sum('final_total');

        $sales_this_month = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereBetween(DB::raw('DATE(transaction_date)'), [$month_start, $month_end])
            ->sum('final_total');

        $total_sales_count = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->count();

        $recent_sales = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->select([
                'transactions.id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.final_total',
                'transactions.payment_status',
                'contacts.name as customer_name',
            ])
            ->orderBy('transactions.transaction_date', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_products' => $total_products,
            'sales_today' => $sales_today,
            'sales_this_month' => $sales_this_month,
            'total_sales_count' => $total_sales_count,
            'recent_sales' => $recent_sales,
        ];
    }
}
