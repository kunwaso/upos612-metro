<?php

namespace App\Utils;

use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\Transaction;
use App\TransactionSellLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeMetronicDashboardUtil extends Util
{
    /**
     * @var \App\Utils\TransactionUtil
     */
    protected $transactionUtil;

    /**
     * @var \App\Utils\ProductUtil
     */
    protected $productUtil;

    /**
     * @param  \App\Utils\TransactionUtil  $transactionUtil
     * @param  \App\Utils\ProductUtil  $productUtil
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Default safe payload for unauthorized or failed responses.
     *
     * @return array<string, mixed>
     */
    public function emptyPayload()
    {
        return [
            'meta' => $this->buildMeta(null, [], [
                'range' => 'month',
                'label' => 'This month',
                'current_start' => null,
                'current_end' => null,
                'previous_start' => null,
                'previous_end' => null,
            ]),
            'kpis' => [
                'expected_earnings' => [
                    'value' => 0.0,
                    'total_sell' => 0.0,
                    'invoice_due' => 0.0,
                    'total_expense' => 0.0,
                    'delta_percent' => 0.0,
                    'is_positive_delta' => true,
                    'range_label' => 'This month',
                    'breakdown' => [
                        ['label' => __('sale.total_sell'), 'value' => 0.0],
                        ['label' => __('home.invoice_due'), 'value' => 0.0],
                        ['label' => __('home.expense'), 'value' => 0.0],
                    ],
                ],
                'sales_summary' => [
                    'value' => 0.0,
                    'delta_percent' => 0.0,
                    'is_positive_delta' => true,
                    'range_label' => 'This month',
                    'breakdown' => [
                        ['label' => __('home.total_purchase'), 'value' => 0.0],
                        ['label' => __('home.invoice_due'), 'value' => 0.0],
                        ['label' => __('lang_v1.total_sell_return'), 'value' => 0.0],
                    ],
                ],
                'orders_this_month' => [
                    'count' => 0,
                    'goal' => 0,
                    'remaining' => 0,
                    'progress_percent' => 0.0,
                    'delta_percent' => 0.0,
                    'is_positive_delta' => true,
                    'range_label' => 'This month',
                ],
                'average_daily_sales' => [
                    'value' => 0.0,
                    'delta_percent' => 0.0,
                    'is_positive_delta' => true,
                    'range_label' => 'This month',
                ],
                'new_customers_this_month' => [
                    'count' => 0,
                    'heroes' => [],
                    'range_label' => 'This month',
                ],
                'sales_this_month' => [
                    'value' => 0.0,
                    'previous_month_goal' => 0.0,
                    'goal_gap' => 0.0,
                    'range' => 'month',
                    'range_label' => 'This month',
                ],
                'discounted_product_sales' => [
                    'value' => 0.0,
                    'delta_percent' => 0.0,
                    'is_positive_delta' => true,
                    'range_label' => 'This month',
                ],
            ],
            'charts' => [
                'expected_earnings_breakdown' => [
                    'labels' => [__('sale.total_sell'), __('home.invoice_due'), __('home.expense')],
                    'series' => [0, 0, 0],
                ],
                'average_daily_sales' => [
                    'labels' => [],
                    'series' => [],
                ],
                'sales_this_month' => [
                    'labels' => [],
                    'series' => [],
                ],
                'discounted_product_sales' => [
                    'labels' => [],
                    'series' => [],
                ],
            ],
            'recent_orders_tabs' => [],
            'product_orders' => [],
            'delivery_feed' => [],
            'stock_rows' => [],
        ];
    }

    /**
     * Build the full payload for Metronic dashboard hydration.
     *
     * @param  int  $business_id
     * @param  int|null  $location_id
     * @param  array<string, mixed>  $sales_chart_filter
     * @return array<string, mixed>
     */
    public function getDashboardData($business_id, $location_id = null, array $sales_chart_filter = [])
    {
        $location_scope = $this->resolveLocationScope((int) $business_id, $location_id);
        $selected_location_id = $location_scope['selected_location_id'];
        $permitted_locations = $location_scope['permitted_locations'];

        $today = Carbon::now();
        $sales_chart_range = $this->resolveSalesChartRange($today, $sales_chart_filter);

        $expected_earnings = $this->buildExpectedEarnings(
            (int) $business_id,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end'],
            $sales_chart_range['previous_start'],
            $sales_chart_range['previous_end'],
            $selected_location_id,
            $permitted_locations,
            $sales_chart_range['label']
        );

        $sales_summary = $this->buildSalesSummary(
            (int) $business_id,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end'],
            $sales_chart_range['previous_start'],
            $sales_chart_range['previous_end'],
            $selected_location_id,
            $permitted_locations,
            $sales_chart_range['label']
        );

        $orders_this_month = $this->buildOrdersThisMonth(
            (int) $business_id,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end'],
            $sales_chart_range['previous_start'],
            $sales_chart_range['previous_end'],
            $selected_location_id,
            $permitted_locations,
            $sales_chart_range['label']
        );

        $average_daily_sales = $this->buildAverageDailySales(
            (int) $business_id,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end'],
            $selected_location_id,
            $permitted_locations,
            $sales_chart_range['range'],
            $sales_chart_range['label']
        );

        $new_customers_this_month = $this->buildNewCustomersThisMonth(
            (int) $business_id,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end'],
            $selected_location_id,
            $permitted_locations,
            $sales_chart_range['label']
        );

        $sales_this_month = $this->buildSalesThisMonth(
            (int) $business_id,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end'],
            $sales_chart_range['previous_start'],
            $sales_chart_range['previous_end'],
            $selected_location_id,
            $permitted_locations,
            $sales_chart_range['range'],
            $sales_chart_range['label']
        );

        $discounted_product_sales = $this->buildDiscountedProductSales(
            (int) $business_id,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end'],
            $sales_chart_range['previous_start'],
            $sales_chart_range['previous_end'],
            $selected_location_id,
            $permitted_locations,
            $sales_chart_range['range'],
            $sales_chart_range['label']
        );

        $recent_order_tabs = $this->buildRecentOrderTabs(
            (int) $business_id,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end'],
            $selected_location_id,
            $permitted_locations
        );

        $product_orders = $this->buildProductOrders(
            (int) $business_id,
            $selected_location_id,
            $permitted_locations,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end']
        );

        $delivery_feed = $this->buildDeliveryFeed(
            (int) $business_id,
            $selected_location_id,
            $permitted_locations,
            $sales_chart_range['current_start'],
            $sales_chart_range['current_end']
        );

        $stock_rows = $this->buildStockRows(
            (int) $business_id,
            $selected_location_id
        );

        return [
            'meta' => $this->buildMeta($selected_location_id, $location_scope['permitted_location_ids'], [
                'range' => $sales_chart_range['range'],
                'label' => $sales_chart_range['label'],
                'current_start' => $sales_chart_range['current_start']->toDateString(),
                'current_end' => $sales_chart_range['current_end']->toDateString(),
                'previous_start' => $sales_chart_range['previous_start']->toDateString(),
                'previous_end' => $sales_chart_range['previous_end']->toDateString(),
            ]),
            'kpis' => [
                'expected_earnings' => $expected_earnings,
                'sales_summary' => $sales_summary,
                'orders_this_month' => $orders_this_month,
                'average_daily_sales' => [
                    'value' => $average_daily_sales['value'],
                    'delta_percent' => $average_daily_sales['delta_percent'],
                    'is_positive_delta' => $average_daily_sales['is_positive_delta'],
                    'range_label' => $average_daily_sales['range_label'],
                ],
                'new_customers_this_month' => $new_customers_this_month,
                'sales_this_month' => [
                    'value' => $sales_this_month['value'],
                    'previous_month_goal' => $sales_this_month['previous_month_goal'],
                    'goal_gap' => $sales_this_month['goal_gap'],
                    'range' => $sales_this_month['range'],
                    'range_label' => $sales_this_month['range_label'],
                ],
                'discounted_product_sales' => [
                    'value' => $discounted_product_sales['value'],
                    'delta_percent' => $discounted_product_sales['delta_percent'],
                    'is_positive_delta' => $discounted_product_sales['is_positive_delta'],
                    'range_label' => $discounted_product_sales['range_label'],
                ],
            ],
            'charts' => [
                'expected_earnings_breakdown' => [
                    'labels' => array_column($expected_earnings['breakdown'], 'label'),
                    'series' => array_map(function ($row) {
                        return $this->toFloat($row['value'] ?? 0);
                    }, $expected_earnings['breakdown']),
                ],
                'average_daily_sales' => $average_daily_sales['chart'],
                'sales_this_month' => $sales_this_month['chart'],
                'discounted_product_sales' => $discounted_product_sales['chart'],
            ],
            'recent_orders_tabs' => $recent_order_tabs,
            'product_orders' => $product_orders,
            'delivery_feed' => $delivery_feed,
            'stock_rows' => $stock_rows,
        ];
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $current_start
     * @param  \Carbon\Carbon  $current_end
     * @param  \Carbon\Carbon  $previous_start
     * @param  \Carbon\Carbon  $previous_end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $range_label
     * @return array<string, mixed>
     */
    protected function buildExpectedEarnings(
        $business_id,
        Carbon $current_start,
        Carbon $current_end,
        Carbon $previous_start,
        Carbon $previous_end,
        $location_id,
        $permitted_locations,
        $range_label = 'This month'
    ) {
        $current_data = $this->getNetTotalsForDateRange(
            $business_id,
            $current_start->toDateString(),
            $current_end->toDateString(),
            $location_id,
            $permitted_locations
        );
        $previous_data = $this->getNetTotalsForDateRange(
            $business_id,
            $previous_start->toDateString(),
            $previous_end->toDateString(),
            $location_id,
            $permitted_locations
        );

        $delta_percent = $this->calculateDeltaPercent($current_data['net'], $previous_data['net']);

        return [
            'value' => $current_data['net'],
            'total_sell' => $current_data['total_sell'],
            'invoice_due' => $current_data['invoice_due'],
            'total_expense' => $current_data['total_expense'],
            'delta_percent' => abs($delta_percent),
            'is_positive_delta' => $delta_percent >= 0,
            'range_label' => $range_label,
            'breakdown' => [
                ['label' => __('sale.total_sell'), 'value' => $current_data['total_sell']],
                ['label' => __('home.invoice_due'), 'value' => $current_data['invoice_due']],
                ['label' => __('home.expense'), 'value' => $current_data['total_expense']],
            ],
        ];
    }

    /**
     * @param  int  $business_id
     * @param  string  $start_date
     * @param  string  $end_date
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @return array<string, float>
     */
    protected function getNetTotalsForDateRange($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        $sell_details = $this->transactionUtil->getSellTotals(
            $business_id,
            $start_date,
            $end_date,
            $location_id,
            null,
            $permitted_locations
        );

        $total_ledger_discount = $this->transactionUtil->getTotalLedgerDiscount($business_id, $start_date, $end_date);

        $transaction_totals = $this->transactionUtil->getTransactionTotals(
            $business_id,
            ['expense'],
            $start_date,
            $end_date,
            $location_id,
            null,
            $permitted_locations
        );

        $total_sell = $this->toFloat($sell_details['total_sell_inc_tax'] ?? 0);
        $invoice_due = $this->toFloat($sell_details['invoice_due'] ?? 0) - $this->toFloat($total_ledger_discount['total_sell_discount'] ?? 0);
        $total_expense = $this->toFloat($transaction_totals['total_expense'] ?? 0);

        return [
            'total_sell' => $total_sell,
            'invoice_due' => $invoice_due,
            'total_expense' => $total_expense,
            'net' => $total_sell - $invoice_due - $total_expense,
        ];
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $current_start
     * @param  \Carbon\Carbon  $current_end
     * @param  \Carbon\Carbon  $previous_start
     * @param  \Carbon\Carbon  $previous_end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $range_label
     * @return array<string, mixed>
     */
    protected function buildSalesSummary(
        $business_id,
        Carbon $current_start,
        Carbon $current_end,
        Carbon $previous_start,
        Carbon $previous_end,
        $location_id,
        $permitted_locations,
        $range_label = 'This month'
    ) {
        $current_data = $this->getSalesSummaryForDateRange(
            $business_id,
            $current_start->toDateString(),
            $current_end->toDateString(),
            $location_id,
            $permitted_locations
        );
        $previous_data = $this->getSalesSummaryForDateRange(
            $business_id,
            $previous_start->toDateString(),
            $previous_end->toDateString(),
            $location_id,
            $permitted_locations
        );

        $delta_percent = $this->calculateDeltaPercent($current_data['total_sell'], $previous_data['total_sell']);

        return [
            'value' => $current_data['total_sell'],
            'delta_percent' => abs($delta_percent),
            'is_positive_delta' => $delta_percent >= 0,
            'range_label' => $range_label,
            'breakdown' => [
                ['label' => __('home.total_purchase'), 'value' => $current_data['total_purchase']],
                ['label' => __('home.invoice_due'), 'value' => $current_data['invoice_due']],
                ['label' => __('lang_v1.total_sell_return'), 'value' => $current_data['total_sell_return']],
            ],
        ];
    }

    /**
     * @param  int  $business_id
     * @param  string  $start_date
     * @param  string  $end_date
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @return array<string, float>
     */
    protected function getSalesSummaryForDateRange($business_id, $start_date, $end_date, $location_id, $permitted_locations)
    {
        $sell_details = $this->transactionUtil->getSellTotals(
            $business_id,
            $start_date,
            $end_date,
            $location_id,
            null,
            $permitted_locations
        );

        $purchase_details = $this->transactionUtil->getPurchaseTotals(
            $business_id,
            $start_date,
            $end_date,
            $location_id,
            null,
            $permitted_locations
        );

        $total_ledger_discount = $this->transactionUtil->getTotalLedgerDiscount($business_id, $start_date, $end_date);

        $transaction_totals = $this->transactionUtil->getTransactionTotals(
            $business_id,
            ['sell_return'],
            $start_date,
            $end_date,
            $location_id,
            null,
            $permitted_locations
        );

        return [
            'total_sell' => $this->toFloat($sell_details['total_sell_inc_tax'] ?? 0),
            'total_purchase' => $this->toFloat($purchase_details['total_purchase_inc_tax'] ?? 0),
            'invoice_due' => $this->toFloat($sell_details['invoice_due'] ?? 0) - $this->toFloat($total_ledger_discount['total_sell_discount'] ?? 0),
            'total_sell_return' => $this->toFloat($transaction_totals['total_sell_return_inc_tax'] ?? 0),
        ];
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $current_start
     * @param  \Carbon\Carbon  $current_end
     * @param  \Carbon\Carbon  $previous_start
     * @param  \Carbon\Carbon  $previous_end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $range_label
     * @return array<string, mixed>
     */
    protected function buildOrdersThisMonth(
        $business_id,
        Carbon $current_start,
        Carbon $current_end,
        Carbon $previous_start,
        Carbon $previous_end,
        $location_id,
        $permitted_locations,
        $range_label = 'This month'
    ) {
        $current_orders = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereDate('transactions.transaction_date', '>=', $current_start->toDateString())
            ->whereDate('transactions.transaction_date', '<=', $current_end->toDateString());
        $this->applyLocationScope($current_orders, $location_id, $permitted_locations, 'transactions.location_id');
        $current_count = (int) $current_orders->count();

        $previous_orders = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereDate('transactions.transaction_date', '>=', $previous_start->toDateString())
            ->whereDate('transactions.transaction_date', '<=', $previous_end->toDateString());
        $this->applyLocationScope($previous_orders, $location_id, $permitted_locations, 'transactions.location_id');
        $goal_count = (int) $previous_orders->count();

        $remaining = max($goal_count - $current_count, 0);
        $progress_percent = $goal_count > 0 ? min(($current_count / $goal_count) * 100, 100) : ($current_count > 0 ? 100 : 0);
        $delta_percent = $this->calculateDeltaPercent($current_count, $goal_count);

        return [
            'count' => $current_count,
            'goal' => $goal_count,
            'remaining' => $remaining,
            'progress_percent' => $this->toFloat($progress_percent),
            'delta_percent' => abs($delta_percent),
            'is_positive_delta' => $delta_percent >= 0,
            'range_label' => $range_label,
        ];
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $current_start
     * @param  \Carbon\Carbon  $current_end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $range
     * @param  string  $range_label
     * @return array<string, mixed>
     */
    protected function buildAverageDailySales(
        $business_id,
        Carbon $current_start,
        Carbon $current_end,
        $location_id,
        $permitted_locations,
        $range = 'month',
        $range_label = 'This month'
    ) {
        $current_sell_details = $this->transactionUtil->getSellTotals(
            $business_id,
            $current_start->toDateString(),
            $current_end->toDateString(),
            $location_id,
            null,
            $permitted_locations
        );
        $current_total = $this->toFloat($current_sell_details['total_sell_inc_tax'] ?? 0);
        $current_days = max((int) $current_start->copy()->startOfDay()->diffInDays($current_end->copy()->startOfDay()) + 1, 1);
        $average_daily = $current_total / $current_days;

        $previous_range = $this->getPreviousComparableRange($current_start, $current_end);
        $previous_sell_details = $this->transactionUtil->getSellTotals(
            $business_id,
            $previous_range['start']->toDateString(),
            $previous_range['end']->toDateString(),
            $location_id,
            null,
            $permitted_locations
        );
        $previous_total = $this->toFloat($previous_sell_details['total_sell_inc_tax'] ?? 0);
        $previous_days = max((int) $previous_range['start']->copy()->startOfDay()->diffInDays($previous_range['end']->copy()->startOfDay()) + 1, 1);
        $previous_average_daily = $previous_total / $previous_days;

        $label_format = $this->resolveSalesChartLabelFormat($range, $current_start, $current_end);
        $current_chart = $this->getDailySellSeries(
            $business_id,
            $current_start,
            $current_end,
            $location_id,
            $permitted_locations,
            $label_format
        );

        $delta_percent = $this->calculateDeltaPercent($average_daily, $previous_average_daily);

        return [
            'value' => $average_daily,
            'delta_percent' => abs($delta_percent),
            'is_positive_delta' => $delta_percent >= 0,
            'range_label' => $range_label,
            'chart' => [
                'labels' => $current_chart['labels'],
                'series' => $current_chart['series'],
            ],
        ];
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $current_start
     * @param  \Carbon\Carbon  $current_end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $range_label
     * @return array<string, mixed>
     */
    protected function buildNewCustomersThisMonth(
        $business_id,
        Carbon $current_start,
        Carbon $current_end,
        $location_id,
        $permitted_locations,
        $range_label = 'This month'
    )
    {
        $new_customer_count = Contact::where('contacts.business_id', $business_id)
            ->whereIn('contacts.type', ['customer', 'both'])
            ->whereDate('contacts.created_at', '>=', $current_start->toDateString())
            ->whereDate('contacts.created_at', '<=', $current_end->toDateString())
            ->count();

        $heroes_query = Contact::join('transactions as t', 'contacts.id', '=', 't.contact_id')
            ->where('contacts.business_id', $business_id)
            ->whereIn('contacts.type', ['customer', 'both'])
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $current_start->toDateString())
            ->whereDate('t.transaction_date', '<=', $current_end->toDateString())
            ->select(
                'contacts.id',
                'contacts.name',
                'contacts.supplier_business_name',
                DB::raw('SUM(t.final_total) as total_sales')
            )
            ->groupBy('contacts.id', 'contacts.name', 'contacts.supplier_business_name')
            ->orderBy('total_sales', 'desc')
            ->limit(6);
        $this->applyLocationScope($heroes_query, $location_id, $permitted_locations, 't.location_id');

        $heroes = [];
        foreach ($heroes_query->get() as $hero) {
            $name = ! empty($hero->name) ? $hero->name : (! empty($hero->supplier_business_name) ? $hero->supplier_business_name : __('lang_v1.unknown'));
            $heroes[] = [
                'id' => (int) $hero->id,
                'name' => $name,
                'initials' => $this->makeInitials($name),
                'total_sales' => $this->toFloat($hero->total_sales ?? 0),
            ];
        }

        return [
            'count' => (int) $new_customer_count,
            'heroes' => $heroes,
            'range_label' => $range_label,
        ];
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $current_period_start
     * @param  \Carbon\Carbon  $current_period_end
     * @param  \Carbon\Carbon  $previous_period_start
     * @param  \Carbon\Carbon  $previous_period_end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $range
     * @param  string  $range_label
     * @return array<string, mixed>
     */
    protected function buildSalesThisMonth(
        $business_id,
        Carbon $current_period_start,
        Carbon $current_period_end,
        Carbon $previous_period_start,
        Carbon $previous_period_end,
        $location_id,
        $permitted_locations,
        $range = 'month',
        $range_label = 'This month'
    ) {
        $label_format = $this->resolveSalesChartLabelFormat($range, $current_period_start, $current_period_end);
        $mtd_chart = $this->getDailySellSeries(
            $business_id,
            $current_period_start,
            $current_period_end,
            $location_id,
            $permitted_locations,
            $label_format
        );

        $previous_month_query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereDate('transactions.transaction_date', '>=', $previous_period_start->toDateString())
            ->whereDate('transactions.transaction_date', '<=', $previous_period_end->toDateString());
        $this->applyLocationScope($previous_month_query, $location_id, $permitted_locations, 'transactions.location_id');
        $previous_month_total = $this->toFloat($previous_month_query->sum('transactions.final_total'));

        return [
            'value' => $mtd_chart['total'],
            'previous_month_goal' => $previous_month_total,
            'goal_gap' => max($previous_month_total - $mtd_chart['total'], 0),
            'range' => $range,
            'range_label' => $range_label,
            'chart' => [
                'labels' => $mtd_chart['labels'],
                'series' => $mtd_chart['series'],
            ],
        ];
    }

    /**
     * @param  \Carbon\Carbon  $today
     * @param  array<string, mixed>  $sales_chart_filter
     * @return array<string, mixed>
     */
    protected function resolveSalesChartRange(Carbon $today, array $sales_chart_filter = [])
    {
        $allowed_ranges = ['week', 'month', 'quarter', 'year', 'custom'];
        $range = (string) ($sales_chart_filter['range'] ?? 'month');
        if (! in_array($range, $allowed_ranges, true)) {
            $range = 'month';
        }

        $current_start = $today->copy()->startOfMonth()->startOfDay();
        $current_end = $today->copy()->endOfDay();

        if ($range === 'week') {
            $current_start = $today->copy()->startOfWeek()->startOfDay();
        } elseif ($range === 'quarter') {
            $current_start = $today->copy()->startOfQuarter()->startOfDay();
        } elseif ($range === 'year') {
            $current_start = $today->copy()->startOfYear()->startOfDay();
        } elseif ($range === 'custom') {
            $start_date = (string) ($sales_chart_filter['start_date'] ?? '');
            $end_date = (string) ($sales_chart_filter['end_date'] ?? '');
            if ($this->isValidDateString($start_date) && $this->isValidDateString($end_date) && $start_date <= $end_date) {
                $current_start = Carbon::createFromFormat('Y-m-d', $start_date)->startOfDay();
                $current_end = Carbon::createFromFormat('Y-m-d', $end_date)->endOfDay();
            } else {
                $range = 'month';
            }
        }

        $previous = $this->getPreviousComparableRange($current_start, $current_end);

        return [
            'range' => $range,
            'label' => $this->buildSalesChartRangeLabel($range, $current_start, $current_end),
            'current_start' => $current_start,
            'current_end' => $current_end,
            'previous_start' => $previous['start'],
            'previous_end' => $previous['end'],
        ];
    }

    /**
     * @param  \Carbon\Carbon  $start
     * @param  \Carbon\Carbon  $end
     * @return array<string, \Carbon\Carbon>
     */
    protected function getPreviousComparableRange(Carbon $start, Carbon $end)
    {
        $days = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $days = max($days, 1);

        $previous_end = $start->copy()->subDay()->endOfDay();
        $previous_start = $previous_end->copy()->subDays($days - 1)->startOfDay();

        return [
            'start' => $previous_start,
            'end' => $previous_end,
        ];
    }

    /**
     * @param  string  $range
     * @param  \Carbon\Carbon  $start
     * @param  \Carbon\Carbon  $end
     * @return string
     */
    protected function buildSalesChartRangeLabel($range, Carbon $start, Carbon $end)
    {
        if ($range === 'week') {
            return 'This week';
        }
        if ($range === 'month') {
            return 'This month';
        }
        if ($range === 'quarter') {
            return 'This quarter';
        }
        if ($range === 'year') {
            return 'This year';
        }
        if ($range === 'custom') {
            return 'Custom: '.$start->toDateString().' - '.$end->toDateString();
        }

        return 'Current range';
    }

    /**
     * @param  string  $range
     * @param  \Carbon\Carbon  $start
     * @param  \Carbon\Carbon  $end
     * @return string
     */
    protected function resolveSalesChartLabelFormat($range, Carbon $start, Carbon $end)
    {
        if ($range === 'week') {
            return 'D';
        }
        if ($range === 'month') {
            return 'j';
        }
        if ($range === 'quarter' || $range === 'year') {
            return 'j M';
        }

        $days = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        return $days <= 14 ? 'D' : 'j M';
    }

    /**
     * @param  string  $date
     * @return bool
     */
    protected function isValidDateString($date)
    {
        if (empty($date)) {
            return false;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $current_start
     * @param  \Carbon\Carbon  $current_end
     * @param  \Carbon\Carbon  $previous_start
     * @param  \Carbon\Carbon  $previous_end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $range
     * @param  string  $range_label
     * @return array<string, mixed>
     */
    protected function buildDiscountedProductSales(
        $business_id,
        Carbon $current_start,
        Carbon $current_end,
        Carbon $previous_start,
        Carbon $previous_end,
        $location_id,
        $permitted_locations,
        $range = 'month',
        $range_label = 'This month'
    ) {
        $label_format = $this->resolveSalesChartLabelFormat($range, $current_start, $current_end);
        $current_discount_chart = $this->getDailyDiscountSeries(
            $business_id,
            $current_start,
            $current_end,
            $location_id,
            $permitted_locations,
            $label_format
        );

        $previous_discount_query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereDate('transactions.transaction_date', '>=', $previous_start->toDateString())
            ->whereDate('transactions.transaction_date', '<=', $previous_end->toDateString());
        $this->applyLocationScope($previous_discount_query, $location_id, $permitted_locations, 'transactions.location_id');
        $previous_discount_total = $this->toFloat($previous_discount_query->sum('transactions.discount_amount'));

        $delta_percent = $this->calculateDeltaPercent($current_discount_chart['total'], $previous_discount_total);

        return [
            'value' => $current_discount_chart['total'],
            'delta_percent' => abs($delta_percent),
            'is_positive_delta' => $delta_percent >= 0,
            'range_label' => $range_label,
            'chart' => [
                'labels' => $current_discount_chart['labels'],
                'series' => $current_discount_chart['series'],
            ],
        ];
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $current_start
     * @param  \Carbon\Carbon  $current_end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @return array<int, array<string, mixed>>
     */
    protected function buildRecentOrderTabs($business_id, Carbon $current_start, Carbon $current_end, $location_id, $permitted_locations)
    {
        $qty_expression = 'transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned';

        $category_query = TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('sale.business_id', $business_id)
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->whereDate('sale.transaction_date', '>=', $current_start->toDateString())
            ->whereDate('sale.transaction_date', '<=', $current_end->toDateString())
            ->where(function ($query) {
                $query->whereNull('transaction_sell_lines.children_type')
                    ->orWhere('transaction_sell_lines.children_type', '!=', 'modifier');
            })
            ->select(
                'c.id as category_id',
                'c.name as category_name',
                DB::raw('SUM('.$qty_expression.') as sold_qty')
            )
            ->groupBy('c.id', 'c.name')
            ->orderBy('sold_qty', 'desc')
            ->limit(5);
        $this->applyLocationScope($category_query, $location_id, $permitted_locations, 'sale.location_id');

        $tabs = [];
        $categories = $category_query->get();

        foreach ($categories as $category) {
            $products_query = TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
                ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
                ->leftJoin('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
                ->where('sale.business_id', $business_id)
                ->where('sale.type', 'sell')
                ->where('sale.status', 'final')
                ->whereDate('sale.transaction_date', '>=', $current_start->toDateString())
                ->whereDate('sale.transaction_date', '<=', $current_end->toDateString())
                ->where(function ($query) {
                    $query->whereNull('transaction_sell_lines.children_type')
                        ->orWhere('transaction_sell_lines.children_type', '!=', 'modifier');
                })
                ->select(
                    'p.id',
                    'p.name',
                    'p.image',
                    DB::raw('MAX(v.sub_sku) as item_code'),
                    DB::raw('SUM('.$qty_expression.') as qty'),
                    DB::raw('AVG(transaction_sell_lines.unit_price_inc_tax) as avg_price'),
                    DB::raw('SUM(('.$qty_expression.') * transaction_sell_lines.unit_price_inc_tax) as total_price')
                )
                ->groupBy('p.id', 'p.name', 'p.image')
                ->orderBy('qty', 'desc')
                ->limit(3);

            if (! empty($category->category_id)) {
                $products_query->where('p.category_id', $category->category_id);
            } else {
                $products_query->whereNull('p.category_id');
            }
            $this->applyLocationScope($products_query, $location_id, $permitted_locations, 'sale.location_id');

            $items = [];
            foreach ($products_query->get() as $item) {
                $items[] = [
                    'product_name' => $item->name,
                    'item_code' => ! empty($item->item_code) ? $item->item_code : ('#'.$item->id),
                    'qty' => $this->toFloat($item->qty),
                    'price' => $this->toFloat($item->avg_price),
                    'total_price' => $this->toFloat($item->total_price),
                    'image_url' => $this->buildProductImageUrl($item->image),
                ];
            }

            $tabs[] = [
                'label' => ! empty($category->category_name) ? $category->category_name : __('lang_v1.others'),
                'items' => $items,
            ];
        }

        return array_slice($tabs, 0, 5);
    }

    /**
     * @param  int  $business_id
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  \Carbon\Carbon|null  $start_date
     * @param  \Carbon\Carbon|null  $end_date
     * @return array<int, array<string, mixed>>
     */
    protected function buildProductOrders($business_id, $location_id, $permitted_locations, ?Carbon $start_date = null, ?Carbon $end_date = null)
    {
        $query = Transaction::leftJoin('contacts as c', 'transactions.contact_id', '=', 'c.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->select(
                'transactions.id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.final_total',
                'transactions.shipping_status',
                'transactions.payment_status',
                'transactions.delivered_to',
                'c.name as customer_name',
                'c.supplier_business_name'
            )
            ->orderBy('transactions.transaction_date', 'desc')
            ->limit(7);
        $this->applyLocationScope($query, $location_id, $permitted_locations, 'transactions.location_id');
        if (! empty($start_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date->toDateString());
        }
        if (! empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '<=', $end_date->toDateString());
        }

        $orders = $query->get();
        if ($orders->isEmpty()) {
            return [];
        }

        $order_ids = [];
        foreach ($orders as $order) {
            $order_ids[] = (int) $order->id;
        }

        $gross_profit_map = $this->getOrderGrossProfitsMap($business_id, $order_ids);
        $order_lines_map = $this->getOrderLinesMap($business_id, $order_ids, $location_id, $permitted_locations);

        $rows = [];
        foreach ($orders as $order) {
            $status = $this->mapShippingStatusToBadge($order->shipping_status, $order->payment_status);
            $order_id = (int) $order->id;
            $rows[] = [
                'id' => $order_id,
                'order_id' => ! empty($order->invoice_no) ? $order->invoice_no : ('#'.$order_id),
                'created_at' => ! empty($order->transaction_date) ? Carbon::parse($order->transaction_date)->diffForHumans() : '',
                'customer_name' => ! empty($order->customer_name) ? $order->customer_name : (! empty($order->supplier_business_name) ? $order->supplier_business_name : __('lang_v1.unknown')),
                'total' => $this->toFloat($order->final_total),
                'profit' => $this->toFloat($gross_profit_map[$order_id] ?? 0),
                'status' => $status['label'],
                'status_variant' => $status['variant'],
                'lines' => $order_lines_map[$order_id] ?? [],
            ];
        }

        return $rows;
    }

    /**
     * @param  int  $business_id
     * @param  array<int>  $order_ids
     * @return array<int, float>
     */
    protected function getOrderGrossProfitsMap($business_id, array $order_ids)
    {
        if (empty($order_ids)) {
            return [];
        }

        $gross_profit_rows = TransactionSellLine::leftJoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
            ->leftJoin('purchase_lines as PL', 'TSPL.purchase_line_id', '=', 'PL.id')
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->whereIn('transaction_sell_lines.transaction_id', $order_ids)
            ->where('sale.business_id', $business_id)
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->where(function ($query) {
                $query->whereNull('transaction_sell_lines.children_type')
                    ->orWhere('transaction_sell_lines.children_type', '!=', 'combo');
            })
            ->select(
                'transaction_sell_lines.transaction_id',
                DB::raw('SUM(IF (TSPL.id IS NULL AND P.type="combo", (
                    SELECT SUM((tspl2.quantity - tspl2.qty_returned) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax))
                    FROM transaction_sell_lines AS tsl
                    JOIN transaction_sell_lines_purchase_lines AS tspl2 ON tsl.id=tspl2.sell_line_id
                    JOIN purchase_lines AS pl2 ON tspl2.purchase_line_id = pl2.id
                    WHERE tsl.parent_sell_line_id = transaction_sell_lines.id
                ), IF(P.enable_stock=0,
                    (transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax,
                    (TSPL.quantity - TSPL.qty_returned) * (transaction_sell_lines.unit_price_inc_tax - PL.purchase_price_inc_tax)
                ))) AS gross_profit')
            )
            ->groupBy('transaction_sell_lines.transaction_id')
            ->pluck('gross_profit', 'transaction_sell_lines.transaction_id')
            ->toArray();

        $map = [];
        foreach ($gross_profit_rows as $order_id => $gross_profit) {
            $map[(int) $order_id] = $this->toFloat($gross_profit);
        }

        return $map;
    }

    /**
     * @param  int  $business_id
     * @param  array<int>  $order_ids
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @return array<int, array<int, array<string, mixed>>>
     */
    protected function getOrderLinesMap($business_id, array $order_ids, $location_id, $permitted_locations)
    {
        if (empty($order_ids)) {
            return [];
        }

        $qty_expression = 'transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned';

        $line_rows = TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->whereIn('transaction_sell_lines.transaction_id', $order_ids)
            ->where('sale.business_id', $business_id)
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->where(function ($query) {
                $query->whereNull('transaction_sell_lines.children_type')
                    ->orWhere('transaction_sell_lines.children_type', '!=', 'modifier');
            })
            ->select(
                'transaction_sell_lines.transaction_id',
                'transaction_sell_lines.id',
                'transaction_sell_lines.variation_id',
                'p.name as product_name',
                'p.image',
                'v.sub_sku',
                DB::raw($qty_expression.' as qty'),
                'transaction_sell_lines.unit_price_inc_tax as unit_price',
                DB::raw('COALESCE(
                    (
                        SELECT AVG(pl.purchase_price_inc_tax)
                        FROM transaction_sell_lines_purchase_lines tspl
                        JOIN purchase_lines pl ON tspl.purchase_line_id = pl.id
                        WHERE tspl.sell_line_id = transaction_sell_lines.id
                    ),
                    0
                ) as unit_cost')
            )
            ->orderBy('transaction_sell_lines.transaction_id', 'asc')
            ->orderBy('transaction_sell_lines.id', 'asc');
        $this->applyLocationScope($line_rows, $location_id, $permitted_locations, 'sale.location_id');
        $line_rows = $line_rows->get();

        $variation_ids = [];
        foreach ($line_rows as $line) {
            if (! empty($line->variation_id)) {
                $variation_ids[] = (int) $line->variation_id;
            }
        }
        $variation_ids = array_values(array_unique($variation_ids));
        $variation_stock_map = $this->getVariationAvailableQtyMap($variation_ids, $location_id, $permitted_locations);

        $lines_map = [];
        $line_counts = [];
        foreach ($line_rows as $line) {
            $order_id = (int) $line->transaction_id;
            $line_counts[$order_id] = $line_counts[$order_id] ?? 0;
            if ($line_counts[$order_id] >= 10) {
                continue;
            }

            $qty = max($this->toFloat($line->qty), 0);
            $variation_id = ! empty($line->variation_id) ? (int) $line->variation_id : 0;

            $lines_map[$order_id] = $lines_map[$order_id] ?? [];
            $lines_map[$order_id][] = [
                'id' => (int) $line->id,
                'name' => $line->product_name,
                'description' => ! empty($line->sub_sku) ? 'Item: #'.$line->sub_sku : __('lang_v1.not_applicable'),
                'image_url' => $this->buildProductImageUrl($line->image),
                'cost' => $this->toFloat($line->unit_cost),
                'qty' => $qty,
                'total' => $this->toFloat($line->unit_price) * $qty,
                'stock' => $this->toFloat($variation_stock_map[$variation_id] ?? 0),
            ];
            $line_counts[$order_id]++;
        }

        return $lines_map;
    }

    /**
     * @param  array<int>  $variation_ids
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @return array<int, float>
     */
    protected function getVariationAvailableQtyMap(array $variation_ids, $location_id, $permitted_locations)
    {
        if (empty($variation_ids)) {
            return [];
        }

        $query = DB::table('variation_location_details')
            ->whereIn('variation_id', $variation_ids)
            ->select('variation_id', DB::raw('SUM(qty_available) as qty_available'))
            ->groupBy('variation_id');

        if (! empty($location_id)) {
            $query->where('location_id', $location_id);
        } elseif ($permitted_locations !== 'all') {
            $ids = $this->normalizePermittedLocations($permitted_locations);
            if (empty($ids)) {
                return [];
            }
            $query->whereIn('location_id', $ids);
        }

        $rows = $query->pluck('qty_available', 'variation_id')->toArray();
        $map = [];
        foreach ($rows as $variation_id => $qty) {
            $map[(int) $variation_id] = $this->toFloat($qty);
        }

        return $map;
    }

    /**
     * @param  int  $business_id
     * @param  array<int>  $order_ids
     * @return array<int, array<string, string|null>>
     */
    protected function getFirstOrderProductMap($business_id, array $order_ids)
    {
        if (empty($order_ids)) {
            return [];
        }

        $first_line_subquery = TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->whereIn('transaction_sell_lines.transaction_id', $order_ids)
            ->where('sale.business_id', $business_id)
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->where(function ($query) {
                $query->whereNull('transaction_sell_lines.children_type')
                    ->orWhere('transaction_sell_lines.children_type', '!=', 'modifier');
            })
            ->select(
                DB::raw('MIN(transaction_sell_lines.id) as first_line_id'),
                'transaction_sell_lines.transaction_id'
            )
            ->groupBy('transaction_sell_lines.transaction_id');

        $rows = TransactionSellLine::joinSub($first_line_subquery, 'first_lines', function ($join) {
            $join->on('transaction_sell_lines.id', '=', 'first_lines.first_line_id');
        })
            ->join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->select(
                'first_lines.transaction_id',
                'p.name',
                'p.image'
            )
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->transaction_id] = [
                'name' => $row->name,
                'image' => $row->image,
            ];
        }

        return $map;
    }

    /**
     * @param  int  $order_id
     * @return float
     */
    protected function getOrderGrossProfit($order_id)
    {
        $gross_profit_obj = TransactionSellLine::leftJoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
            ->leftJoin('purchase_lines as PL', 'TSPL.purchase_line_id', '=', 'PL.id')
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->where('transaction_sell_lines.transaction_id', $order_id)
            ->where(function ($query) {
                $query->whereNull('transaction_sell_lines.children_type')
                    ->orWhere('transaction_sell_lines.children_type', '!=', 'combo');
            })
            ->select(
                DB::raw('SUM(IF (TSPL.id IS NULL AND P.type="combo", (
                    SELECT SUM((tspl2.quantity - tspl2.qty_returned) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax))
                    FROM transaction_sell_lines AS tsl
                    JOIN transaction_sell_lines_purchase_lines AS tspl2 ON tsl.id=tspl2.sell_line_id
                    JOIN purchase_lines AS pl2 ON tspl2.purchase_line_id = pl2.id
                    WHERE tsl.parent_sell_line_id = transaction_sell_lines.id
                ), IF(P.enable_stock=0,
                    (transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax,
                    (TSPL.quantity - TSPL.qty_returned) * (transaction_sell_lines.unit_price_inc_tax - PL.purchase_price_inc_tax)
                ))) AS gross_profit')
            )
            ->first();

        return $this->toFloat($gross_profit_obj->gross_profit ?? 0);
    }

    /**
     * @param  int  $order_id
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @return array<int, array<string, mixed>>
     */
    protected function getOrderLines($order_id, $location_id, $permitted_locations)
    {
        $qty_expression = 'transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned';

        $lines_query = TransactionSellLine::join('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->where('transaction_sell_lines.transaction_id', $order_id)
            ->where(function ($query) {
                $query->whereNull('transaction_sell_lines.children_type')
                    ->orWhere('transaction_sell_lines.children_type', '!=', 'modifier');
            })
            ->select(
                'transaction_sell_lines.id',
                'transaction_sell_lines.variation_id',
                'p.name as product_name',
                'p.image',
                'v.sub_sku',
                DB::raw($qty_expression.' as qty'),
                'transaction_sell_lines.unit_price_inc_tax as unit_price',
                DB::raw('COALESCE(
                    (
                        SELECT AVG(pl.purchase_price_inc_tax)
                        FROM transaction_sell_lines_purchase_lines tspl
                        JOIN purchase_lines pl ON tspl.purchase_line_id = pl.id
                        WHERE tspl.sell_line_id = transaction_sell_lines.id
                    ),
                    0
                ) as unit_cost')
            )
            ->limit(10)
            ->get();

        $lines = [];
        foreach ($lines_query as $line) {
            $qty = max($this->toFloat($line->qty), 0);
            $stock_qty = $this->getVariationAvailableQty((int) $line->variation_id, $location_id, $permitted_locations);

            $lines[] = [
                'id' => (int) $line->id,
                'name' => $line->product_name,
                'description' => ! empty($line->sub_sku) ? 'Item: #'.$line->sub_sku : __('lang_v1.not_applicable'),
                'image_url' => $this->buildProductImageUrl($line->image),
                'cost' => $this->toFloat($line->unit_cost),
                'qty' => $qty,
                'total' => $this->toFloat($line->unit_price) * $qty,
                'stock' => $stock_qty,
            ];
        }

        return $lines;
    }

    /**
     * @param  int  $variation_id
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @return float
     */
    protected function getVariationAvailableQty($variation_id, $location_id, $permitted_locations)
    {
        if (empty($variation_id)) {
            return 0.0;
        }

        $query = DB::table('variation_location_details')
            ->where('variation_id', $variation_id);

        if (! empty($location_id)) {
            $query->where('location_id', $location_id);
        } elseif ($permitted_locations !== 'all') {
            $ids = $this->normalizePermittedLocations($permitted_locations);
            if (empty($ids)) {
                return 0.0;
            }
            $query->whereIn('location_id', $ids);
        }

        return $this->toFloat($query->sum('qty_available'));
    }

    /**
     * @param  int  $business_id
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  \Carbon\Carbon|null  $start_date
     * @param  \Carbon\Carbon|null  $end_date
     * @return array<int, array<string, mixed>>
     */
    protected function buildDeliveryFeed($business_id, $location_id, $permitted_locations, ?Carbon $start_date = null, ?Carbon $end_date = null)
    {
        $query = Transaction::leftJoin('contacts as c', 'transactions.contact_id', '=', 'c.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->select(
                'transactions.id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.shipping_status',
                'transactions.delivered_to',
                'c.name as customer_name',
                'c.supplier_business_name'
            )
            ->orderBy('transactions.transaction_date', 'desc')
            ->limit(7);
        $this->applyLocationScope($query, $location_id, $permitted_locations, 'transactions.location_id');
        if (! empty($start_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date->toDateString());
        }
        if (! empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '<=', $end_date->toDateString());
        }

        $orders = $query->get();
        if ($orders->isEmpty()) {
            return [];
        }

        $order_ids = [];
        foreach ($orders as $order) {
            $order_ids[] = (int) $order->id;
        }
        $first_products = $this->getFirstOrderProductMap($business_id, $order_ids);

        $rows = [];
        foreach ($orders as $order) {
            $order_id = (int) $order->id;
            $product = $first_products[$order_id] ?? null;
            $status = $this->mapShippingStatusToBadge($order->shipping_status, null);
            $recipient = ! empty($order->delivered_to) ? $order->delivered_to : (! empty($order->customer_name) ? $order->customer_name : $order->supplier_business_name);

            $rows[] = [
                'id' => $order_id,
                'invoice_no' => ! empty($order->invoice_no) ? $order->invoice_no : ('#'.$order_id),
                'product_name' => ! empty($product['name']) ? $product['name'] : __('lang_v1.product'),
                'image_url' => $this->buildProductImageUrl($product['image'] ?? null),
                'recipient_name' => ! empty($recipient) ? $recipient : __('lang_v1.unknown'),
                'status' => $status['label'],
                'status_variant' => $status['variant'],
            ];
        }

        return $rows;
    }

    /**
     * @param  int  $business_id
     * @param  int|null  $location_id
     * @return array<int, array<string, mixed>>
     */
    protected function buildStockRows($business_id, $location_id)
    {
        $filters = [];
        if (! empty($location_id)) {
            $filters['location_id'] = $location_id;
        }

        $stock_query = $this->productUtil->getProductStockDetails($business_id, $filters, 'datatables');
        $stock_rows = $stock_query
            ->orderBy('stock', 'desc')
            ->limit(8)
            ->get();

        $product_ids = [];
        foreach ($stock_rows as $row) {
            if (! empty($row->product_id)) {
                $product_ids[] = (int) $row->product_id;
            }
        }
        $product_ids = array_values(array_unique($product_ids));

        $product_created_dates = [];
        if (! empty($product_ids)) {
            $product_created_dates = Product::whereIn('id', $product_ids)
                ->pluck('created_at', 'id')
                ->toArray();
        }

        $rows = [];
        foreach ($stock_rows as $row) {
            $qty = $this->toFloat($row->stock);
            $status = $this->mapStockStatus($qty, $this->toFloat($row->alert_quantity));
            $date_added = ! empty($product_created_dates[$row->product_id]) ? Carbon::parse($product_created_dates[$row->product_id]) : null;

            $item_name = $row->product;
            if (($row->type ?? '') === 'variable') {
                $item_name = trim($row->product.' '.$row->product_variation.' '.$row->variation_name);
            }

            $rows[] = [
                'item_name' => $item_name,
                'product_code' => ! empty($row->sku) ? $row->sku : ('#'.$row->product_id),
                'date_added' => ! empty($date_added) ? $date_added->toDateString() : null,
                'price' => $this->toFloat($row->unit_price),
                'status' => $status['enum'],
                'status_label' => $status['label'],
                'status_variant' => $status['variant'],
                'qty' => $qty,
            ];
        }

        return $rows;
    }

    /**
     * @param  float  $qty
     * @param  float  $alert_qty
     * @return array<string, string>
     */
    protected function mapStockStatus($qty, $alert_qty = 0.0)
    {
        if ($qty <= 0) {
            return [
                'enum' => 'out_of_stock',
                'label' => __('lang_v1.out_of_stock'),
                'variant' => 'danger',
            ];
        }

        if ($alert_qty > 0 && $qty <= $alert_qty) {
            return [
                'enum' => 'low_stock',
                'label' => __('report.low_stock'),
                'variant' => 'warning',
            ];
        }

        return [
            'enum' => 'in_stock',
            'label' => __('lang_v1.in_stock'),
            'variant' => 'primary',
        ];
    }

    /**
     * @param  string|null  $shipping_status
     * @param  string|null  $payment_status
     * @return array<string, string>
     */
    protected function mapShippingStatusToBadge($shipping_status = null, $payment_status = null)
    {
        $statuses = $this->shipping_statuses();
        $status = ! empty($shipping_status) ? $shipping_status : null;

        if ($status === 'delivered') {
            return ['label' => $statuses[$status] ?? __('lang_v1.delivered'), 'variant' => 'success'];
        }
        if ($status === 'shipped' || $status === 'packed') {
            return ['label' => $statuses[$status] ?? __('lang_v1.shipped'), 'variant' => 'primary'];
        }
        if ($status === 'cancelled') {
            return ['label' => $statuses[$status] ?? __('restaurant.cancelled'), 'variant' => 'danger'];
        }
        if ($status === 'ordered') {
            return ['label' => $statuses[$status] ?? __('lang_v1.ordered'), 'variant' => 'warning'];
        }

        if (! empty($payment_status) && in_array($payment_status, ['paid', 'partial'], true)) {
            return ['label' => __('lang_v1.confirmed'), 'variant' => 'primary'];
        }

        return ['label' => __('lang_v1.pending'), 'variant' => 'warning'];
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $start
     * @param  \Carbon\Carbon  $end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $label_format
     * @return array<string, mixed>
     */
    protected function getDailySellSeries($business_id, Carbon $start, Carbon $end, $location_id, $permitted_locations, $label_format = 'j M')
    {
        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereDate('transactions.transaction_date', '>=', $start->toDateString())
            ->whereDate('transactions.transaction_date', '<=', $end->toDateString())
            ->select(
                DB::raw('DATE(transactions.transaction_date) as bucket_date'),
                DB::raw('SUM(transactions.final_total) as day_total')
            )
            ->groupBy('bucket_date');
        $this->applyLocationScope($query, $location_id, $permitted_locations, 'transactions.location_id');

        $totals = $query->pluck('day_total', 'bucket_date')->toArray();

        $labels = [];
        $series = [];
        $total = 0.0;
        $cursor = $start->copy()->startOfDay();
        $end_cursor = $end->copy()->startOfDay();

        while ($cursor->lte($end_cursor)) {
            $bucket = $cursor->toDateString();
            $value = $this->toFloat($totals[$bucket] ?? 0);
            $labels[] = $cursor->format($label_format);
            $series[] = $value;
            $total += $value;
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'total' => $total,
        ];
    }

    /**
     * @param  int  $business_id
     * @param  \Carbon\Carbon  $start
     * @param  \Carbon\Carbon  $end
     * @param  int|null  $location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $label_format
     * @return array<string, mixed>
     */
    protected function getDailyDiscountSeries($business_id, Carbon $start, Carbon $end, $location_id, $permitted_locations, $label_format = 'j M')
    {
        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereDate('transactions.transaction_date', '>=', $start->toDateString())
            ->whereDate('transactions.transaction_date', '<=', $end->toDateString())
            ->select(
                DB::raw('DATE(transactions.transaction_date) as bucket_date'),
                DB::raw('SUM(COALESCE(transactions.discount_amount, 0)) as day_total')
            )
            ->groupBy('bucket_date');
        $this->applyLocationScope($query, $location_id, $permitted_locations, 'transactions.location_id');

        $totals = $query->pluck('day_total', 'bucket_date')->toArray();

        $labels = [];
        $series = [];
        $total = 0.0;
        $cursor = $start->copy()->startOfDay();
        $end_cursor = $end->copy()->startOfDay();

        while ($cursor->lte($end_cursor)) {
            $bucket = $cursor->toDateString();
            $value = $this->toFloat($totals[$bucket] ?? 0);
            $labels[] = $cursor->format($label_format);
            $series[] = $value;
            $total += $value;
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'total' => $total,
        ];
    }

    /**
     * @param  int|null  $selected_location_id
     * @param  array<int>  $permitted_location_ids
     * @param  array<string, mixed>  $range_meta
     * @return array<string, mixed>
     */
    protected function buildMeta($selected_location_id, array $permitted_location_ids, array $range_meta = [])
    {
        $business = session('business');
        $currency = session('currency');

        $currency_symbol = $this->readSessionField($currency, 'symbol', '$');
        $currency_code = $this->readSessionField($currency, 'code', null);
        $currency_precision = (int) $this->readSessionField($business, 'currency_precision', 2);
        $date_format = (string) $this->readSessionField($business, 'date_format', 'Y-m-d');
        $time_zone = (string) $this->readSessionField($business, 'time_zone', config('app.timezone'));

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'currency' => [
                'symbol' => $currency_symbol,
                'code' => $currency_code,
                'precision' => $currency_precision,
            ],
            'date' => [
                'format' => $date_format,
                'timezone' => $time_zone,
            ],
            'scope' => [
                'location_id' => $selected_location_id,
                'permitted_locations' => $permitted_location_ids,
            ],
            'range' => [
                'range' => $range_meta['range'] ?? 'month',
                'label' => $range_meta['label'] ?? 'This month',
                'current_start' => $range_meta['current_start'] ?? null,
                'current_end' => $range_meta['current_end'] ?? null,
                'previous_start' => $range_meta['previous_start'] ?? null,
                'previous_end' => $range_meta['previous_end'] ?? null,
            ],
        ];
    }

    /**
     * @param  array|object|null  $target
     * @param  string  $field
     * @param  mixed  $default
     * @return mixed
     */
    protected function readSessionField($target, $field, $default = null)
    {
        if (is_array($target)) {
            return array_key_exists($field, $target) ? $target[$field] : $default;
        }

        if (is_object($target) && isset($target->{$field})) {
            return $target->{$field};
        }

        return $default;
    }

    /**
     * @param  int  $business_id
     * @param  int|string|null  $location_id
     * @return array<string, mixed>
     */
    protected function resolveLocationScope($business_id, $location_id = null)
    {
        $permitted_locations = auth()->user()->permitted_locations();
        $permitted_location_ids = $this->normalizePermittedLocations($permitted_locations);
        $selected_location_id = null;

        if (! empty($location_id)) {
            $location_id = (int) $location_id;
            $exists_in_business = BusinessLocation::where('business_id', $business_id)
                ->where('id', $location_id)
                ->exists();

            if ($exists_in_business) {
                if ($permitted_locations === 'all' || in_array($location_id, $permitted_location_ids, true)) {
                    $selected_location_id = $location_id;
                }
            }
        }

        return [
            'selected_location_id' => $selected_location_id,
            'permitted_locations' => $permitted_locations,
            'permitted_location_ids' => $permitted_locations === 'all' ? [] : $permitted_location_ids,
        ];
    }

    /**
     * @param  array<int>|string|null  $permitted_locations
     * @return array<int>
     */
    protected function normalizePermittedLocations($permitted_locations)
    {
        if ($permitted_locations === 'all' || empty($permitted_locations)) {
            return [];
        }

        $normalized = [];
        foreach ((array) $permitted_locations as $location_id) {
            if (! empty($location_id)) {
                $normalized[] = (int) $location_id;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Applies location scope to tenant query with selected location precedence.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $selected_location_id
     * @param  array<int>|string  $permitted_locations
     * @param  string  $column
     * @return void
     */
    protected function applyLocationScope($query, $selected_location_id, $permitted_locations, $column)
    {
        if (! empty($selected_location_id)) {
            $query->where($column, $selected_location_id);

            return;
        }

        if ($permitted_locations === 'all') {
            return;
        }

        $location_ids = $this->normalizePermittedLocations($permitted_locations);
        if (empty($location_ids)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($column, $location_ids);
    }

    /**
     * @param  string|null  $filename
     * @return string
     */
    protected function buildProductImageUrl($filename = null)
    {
        if (! empty($filename)) {
            return asset('/uploads/img/'.rawurlencode($filename));
        }

        return asset('/img/default.png');
    }

    /**
     * @param  string  $name
     * @return string
     */
    protected function makeInitials($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'NA';
        }

        $parts = preg_split('/\s+/', $name);
        $first = strtoupper(substr((string) ($parts[0] ?? ''), 0, 1));
        $second = strtoupper(substr((string) ($parts[1] ?? ''), 0, 1));

        return trim($first.$second) !== '' ? trim($first.$second) : 'NA';
    }

    /**
     * @param  float|int  $current
     * @param  float|int  $previous
     * @return float
     */
    protected function calculateDeltaPercent($current, $previous)
    {
        $current = $this->toFloat($current);
        $previous = $this->toFloat($previous);

        if ($previous == 0.0) {
            return $current == 0.0 ? 0.0 : 100.0;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    /**
     * @param  mixed  $value
     * @return float
     */
    protected function toFloat($value)
    {
        return (float) $value;
    }

}
