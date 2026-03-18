<?php

namespace App\Utils;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\User;

class UnifiedQuoteListUtil
{
    /**
     * Build a query over a subquery (single source or UNION ALL) for unified quotes listing.
     */
    public function buildWrappedQuery(
        int $businessId,
        bool $includeSalesQuotations,
        bool $includeProductQuotes,
        array $input,
        User $user
    ): Builder {
        $includeProductQuotes = $includeProductQuotes && Schema::hasTable('product_quotes');

        $kindFilter = $input['quote_kind_filter'] ?? 'all';
        if ($kindFilter === 'sales_quotation') {
            $includeProductQuotes = false;
        } elseif ($kindFilter === 'product_quote') {
            $includeSalesQuotations = false;
        }

        if ($kindFilter === 'sales_quotation' && ! $includeSalesQuotations) {
            $includeProductQuotes = false;
        }
        if ($kindFilter === 'product_quote' && ! $includeProductQuotes) {
            $includeSalesQuotations = false;
        }

        if (! $includeSalesQuotations && ! $includeProductQuotes) {
            return $this->emptyUnifiedTable();
        }

        if ($includeSalesQuotations && $includeProductQuotes) {
            $sales = $this->salesQuotationsBaseQuery($businessId, $input, $user);
            $product = $this->productQuotesBaseQuery($businessId, $input);
            $union = $sales->unionAll($product);

            return $this->wrapSubquery($union);
        }

        if ($includeSalesQuotations) {
            return $this->wrapSubquery($this->salesQuotationsBaseQuery($businessId, $input, $user));
        }

        return $this->wrapSubquery($this->productQuotesBaseQuery($businessId, $input));
    }

    protected function wrapSubquery(Builder $inner): Builder
    {
        return DB::query()->fromSub($inner, 'unified_quotes');
    }

    protected function emptyUnifiedTable(): Builder
    {
        return DB::query()->from(DB::raw('(
            SELECT \'sales_quotation\' as quote_kind, 0 as entity_id, NOW() as quote_sort_at,
            \'\' as ref_no, \'\' as customer_name, \'\' as location_name, 0 as amount, \'\' as status_raw, 0 as is_direct_sale
        ) as unified_quotes'))
            ->whereRaw('1 = 0');
    }

    protected function salesQuotationsBaseQuery(int $businessId, array $input, User $user): Builder
    {
        $q = DB::table('transactions')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->join('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
            ->where('transactions.business_id', $businessId)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'draft')
            ->where('transactions.sub_status', 'quotation')
            ->select([
                DB::raw("'sales_quotation' as quote_kind"),
                'transactions.id as entity_id',
                'transactions.transaction_date as quote_sort_at',
                DB::raw('COALESCE(NULLIF(transactions.invoice_no, \'\'), CONCAT(\'Q-\', transactions.id)) as ref_no'),
                DB::raw("COALESCE(NULLIF(contacts.supplier_business_name, ''), NULLIF(contacts.name, ''), '-') as customer_name"),
                'bl.name as location_name',
                'transactions.final_total as amount',
                DB::raw("'quotation' as status_raw"),
                'transactions.is_direct_sale as is_direct_sale',
            ]);

        if (! $user->can('quotation.view_all') && $user->can('quotation.view_own')) {
            $q->where('transactions.created_by', (int) $user->id);
        }

        $permitted = $user->permitted_locations();
        if ($permitted !== 'all' && is_array($permitted) && count($permitted) > 0) {
            $q->whereIn('transactions.location_id', $permitted);
        }

        $this->applyDateRange($q, 'transactions.transaction_date', $input);
        if (! empty($input['location_id'])) {
            $q->where('transactions.location_id', (int) $input['location_id']);
        }
        if (! empty($input['customer_id'])) {
            $q->where('contacts.id', (int) $input['customer_id']);
        }

        return $q;
    }

    protected function productQuotesBaseQuery(int $businessId, array $input): Builder
    {
        $q = DB::table('product_quotes')
            ->leftJoin('contacts as c', 'product_quotes.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 'product_quotes.location_id', '=', 'bl.id')
            ->where('product_quotes.business_id', $businessId)
            ->select([
                DB::raw("'product_quote' as quote_kind"),
                'product_quotes.id as entity_id',
                DB::raw('COALESCE(product_quotes.quote_date, product_quotes.created_at) as quote_sort_at'),
                DB::raw('COALESCE(NULLIF(product_quotes.quote_number, \'\'), product_quotes.uuid) as ref_no'),
                DB::raw("COALESCE(NULLIF(product_quotes.customer_name, ''), NULLIF(c.name, ''), NULLIF(c.supplier_business_name, ''), '-') as customer_name"),
                DB::raw('COALESCE(bl.name, \'-\') as location_name'),
                'product_quotes.grand_total as amount',
                DB::raw("CASE
                    WHEN product_quotes.transaction_id IS NOT NULL THEN 'converted'
                    WHEN product_quotes.confirmed_at IS NOT NULL THEN 'confirmed'
                    WHEN product_quotes.sent_at IS NOT NULL THEN 'sent'
                    ELSE 'draft'
                END as status_raw"),
                DB::raw('0 as is_direct_sale'),
            ]);

        if (! empty($input['start_date']) && ! empty($input['end_date'])) {
            $q->whereRaw('DATE(COALESCE(product_quotes.quote_date, product_quotes.created_at)) >= ?', [$input['start_date']])
                ->whereRaw('DATE(COALESCE(product_quotes.quote_date, product_quotes.created_at)) <= ?', [$input['end_date']]);
        }
        if (! empty($input['location_id'])) {
            $q->where('product_quotes.location_id', (int) $input['location_id']);
        }
        if (! empty($input['customer_id'])) {
            $q->where(function ($sub) use ($input) {
                $cid = (int) $input['customer_id'];
                $sub->where('product_quotes.contact_id', $cid)
                    ->orWhere('c.id', $cid);
            });
        }

        return $q;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $q
     * @param  string|\Illuminate\Database\Query\Expression  $dateColumn
     */
    protected function applyDateRange($q, $dateColumn, array $input): void
    {
        if (! empty($input['start_date']) && ! empty($input['end_date'])) {
            $q->whereDate($dateColumn, '>=', $input['start_date'])
                ->whereDate($dateColumn, '<=', $input['end_date']);
        }
    }
}
