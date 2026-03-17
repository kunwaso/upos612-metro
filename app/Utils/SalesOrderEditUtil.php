<?php

namespace App\Utils;

use App\BusinessLocation;
use App\Contact;
use App\TaxRate;
use App\Transaction;
use App\Utils\TransactionUtil;
use App\Variation;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\ProductQuote;
use App\Utils\NumberFormatUtil;

class SalesOrderEditUtil
{
    protected TransactionUtil $transactionUtil;
    protected ?NumberFormatUtil $numberFormatUtil;

    public function __construct(TransactionUtil $transactionUtil, ?NumberFormatUtil $numberFormatUtil = null)
    {
        $this->transactionUtil = $transactionUtil;
        $this->numberFormatUtil = $numberFormatUtil;
    }

    public function getProductQuoteSellTransactionForEdit(int $businessId, int $id): Transaction
    {
        return Transaction::where('transactions.business_id', $businessId)
            ->where('transactions.type', 'sell')
            ->whereExists(function ($query) use ($businessId) {
                $query->select(DB::raw(1))
                    ->from('product_quotes as pq')
                    ->whereColumn('pq.transaction_id', 'transactions.id')
                    ->where('pq.business_id', $businessId);
            })
            ->with([
                'contact',
                'location',
                'sell_lines.product',
                'sell_lines.variations',
                'payment_lines',
            ])
            ->findOrFail($id);
    }

    public function buildEditViewData(int $businessId, Transaction $transaction): array
    {
        $quote = $this->fetchQuoteForTransaction($businessId, (int) $transaction->id);
        $taxDropdown = $this->fetchTaxDropdown($businessId);
        $paymentTypes = $this->fetchPaymentTypes($transaction, $businessId);
        $numberFormatUtil = $this->getNumberFormatUtil();
        $business = $this->getBusinessObjectFromSession();
        $formatPayload = $numberFormatUtil->buildViewPayload($business);

        $currencyPrecision = (int) ($formatPayload['projectxCurrencyPrecision'] ?? 2);
        $quantityPrecision = (int) ($formatPayload['projectxQuantityPrecision'] ?? 2);

        $lineItems = $this->mapSellLinesForEdit($transaction->sell_lines, $currencyPrecision, $quantityPrecision);
        $paymentLines = $this->mapPaymentLinesForEdit($transaction->payment_lines, $currencyPrecision);
        $initialProducts = $this->resolveInitialProducts($lineItems, $currencyPrecision, $quantityPrecision);
        $initialPayments = $this->resolveInitialPayments($paymentLines, $paymentTypes, $currencyPrecision);

        $transactionDateFormatted = $this->transactionUtil->format_date($transaction->transaction_date, true);
        $deliveryDate = ! empty($transaction->delivery_date)
            ? Carbon::parse($transaction->delivery_date)->format('Y-m-d')
            : null;
        $taxRateOptions = is_array($taxDropdown['tax_rates'] ?? null) ? $taxDropdown['tax_rates'] : [];
        $taxRateAttributes = is_array($taxDropdown['attributes'] ?? null) ? $taxDropdown['attributes'] : [];
        $additionalExpenseInputValues = $this->buildAdditionalExpenseInputValues($transaction, $currencyPrecision);

        return array_merge($formatPayload, [
            'transaction' => $transaction,
            'quote' => $quote,
            'contactsDropdown' => $this->fetchContactsDropdown($businessId),
            'locationsDropdown' => $this->fetchLocationsDropdown($businessId),
            'statuses' => Transaction::sell_statuses(),
            'taxDropdown' => $taxDropdown,
            'paymentTypes' => $paymentTypes,
            'shippingStatuses' => $this->transactionUtil->shipping_statuses(),
            'currencySymbol' => (string) ($formatPayload['projectxCurrencySymbol'] ?? '$'),
            'statusValue' => $this->resolveStatusValue($transaction),
            'lineItems' => $lineItems,
            'paymentLines' => $paymentLines,
            'initialProducts' => $initialProducts,
            'initialPayments' => $initialPayments,
            'taxRateOptions' => $taxRateOptions,
            'taxRateAttributes' => $taxRateAttributes,
            'finalTotalInputValue' => $numberFormatUtil->formatInput((float) ($transaction->final_total ?? 0), $currencyPrecision),
            'discountAmountInputValue' => $numberFormatUtil->formatInput((float) ($transaction->discount_amount ?? 0), $currencyPrecision),
            'shippingChargesInputValue' => $numberFormatUtil->formatInput((float) ($transaction->shipping_charges ?? 0), $currencyPrecision),
            'additionalExpenseInputValues' => $additionalExpenseInputValues,
            'transactionDateFormatted' => $transactionDateFormatted,
            'deliveryDate' => $deliveryDate,
        ]);
    }

    public function searchSellableVariations(int $businessId, int $locationId, ?string $term, int $page): array
    {
        $search = trim((string) $term);

        $query = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
            ->join('product_locations as pl', function ($join) use ($locationId) {
                $join->on('pl.product_id', '=', 'p.id')
                    ->where('pl.location_id', '=', $locationId);
            })
            ->where('p.business_id', $businessId)
            ->where('p.is_inactive', 0)
            ->where('p.not_for_selling', 0)
            ->where('p.type', '!=', 'modifier');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'like', '%' . $search . '%')
                    ->orWhere('p.sku', 'like', '%' . $search . '%')
                    ->orWhere('variations.sub_sku', 'like', '%' . $search . '%');
            });
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query
            ->select([
                'p.id as product_id',
                'p.name as product_name',
                'p.type as product_type',
                'p.unit_id as product_unit_id',
                'variations.id as variation_id',
                'variations.name as variation_name',
                'variations.sub_sku',
                'variations.default_sell_price',
                'variations.sell_price_inc_tax',
            ])
            ->orderBy('p.name')
            ->orderBy('variations.name')
            ->paginate(20, ['*'], 'page', max(1, $page));

        $rows = collect($paginator->items())->map(function ($row) {
            $variationName = trim((string) ($row->variation_name ?? ''));
            $isDummy = strcasecmp($variationName, 'DUMMY') === 0;
            $label = (string) $row->product_name;
            if (! $isDummy && $variationName !== '') {
                $label .= ' (' . $variationName . ')';
            }
            if (! empty($row->sub_sku)) {
                $label .= ' [' . $row->sub_sku . ']';
            }

            return [
                'id' => (int) $row->variation_id,
                'text' => $label,
                'product_id' => (int) $row->product_id,
                'variation_id' => (int) $row->variation_id,
                'product_name' => (string) $row->product_name,
                'variation_name' => $variationName,
                'sub_sku' => (string) ($row->sub_sku ?? ''),
                'product_type' => (string) ($row->product_type ?? 'single'),
                'product_unit_id' => (int) ($row->product_unit_id ?? 0),
                'unit_price' => (float) ($row->default_sell_price ?? 0),
                'unit_price_inc_tax' => (float) ($row->sell_price_inc_tax ?? 0),
            ];
        })->values()->all();

        return [
            'results' => $rows,
            'has_more' => $paginator->hasMorePages(),
        ];
    }

    public function persistDeliveryDate(int $businessId, int $transactionId, ?string $deliveryDateInput): void
    {
        $deliveryDateValue = null;
        $rawValue = trim((string) $deliveryDateInput);
        if ($rawValue !== '') {
            $deliveryDateValue = Carbon::parse($rawValue)->format('Y-m-d 00:00:00');
        }

        Transaction::where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('id', $transactionId)
            ->update(['delivery_date' => $deliveryDateValue]);
    }

    protected function mapSellLinesForEdit(Collection $sellLines, int $currencyPrecision, int $quantityPrecision): array
    {
        return $sellLines->map(function ($line) use ($currencyPrecision, $quantityPrecision) {
            $variationName = trim((string) optional($line->variations)->name);
            $isDummyVariation = strcasecmp($variationName, 'DUMMY') === 0;
            $displayName = (string) optional($line->product)->name;
            if (! $isDummyVariation && $variationName !== '') {
                $displayName .= ' (' . $variationName . ')';
            }

            return $this->prepareLineForView([
                'transaction_sell_lines_id' => (int) $line->id,
                'product_id' => (int) $line->product_id,
                'variation_id' => (int) $line->variation_id,
                'product_name' => $displayName,
                'sub_sku' => (string) optional($line->variations)->sub_sku,
                'product_type' => (string) optional($line->product)->type,
                'product_unit_id' => (int) optional($line->product)->unit_id,
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) ($line->unit_price_before_discount ?? $line->unit_price ?? 0),
                'unit_price_inc_tax' => (float) ($line->unit_price_inc_tax ?? 0),
                'tax_id' => $line->tax_id,
                'item_tax' => (float) ($line->item_tax ?? 0),
                'line_discount_type' => ! empty($line->line_discount_type) ? (string) $line->line_discount_type : 'fixed',
                'line_discount_amount' => (float) ($line->line_discount_amount ?? 0),
                'base_unit_multiplier' => 1,
                'sell_line_note' => (string) ($line->sell_line_note ?? ''),
            ], $currencyPrecision, $quantityPrecision);
        })->values()->all();
    }

    protected function mapPaymentLinesForEdit(Collection $paymentLines, int $currencyPrecision): array
    {
        return $paymentLines->map(function ($paymentLine) use ($currencyPrecision) {
            return $this->preparePaymentForView([
                'payment_id' => (int) $paymentLine->id,
                'method' => (string) ($paymentLine->method ?? 'cash'),
                'amount' => (float) ($paymentLine->amount ?? 0),
                'paid_on' => ! empty($paymentLine->paid_on)
                    ? Carbon::parse($paymentLine->paid_on)->format('Y-m-d H:i:s')
                    : null,
                'note' => (string) ($paymentLine->note ?? ''),
                'account_id' => $paymentLine->account_id,
            ], $currencyPrecision);
        })->values()->all();
    }

    protected function resolveInitialProducts(array $lineItems, int $currencyPrecision, int $quantityPrecision): array
    {
        $oldProducts = old('products');
        $initialProducts = is_array($oldProducts) ? array_values($oldProducts) : $lineItems;

        if (empty($initialProducts)) {
            $initialProducts = [$this->buildDefaultProductLine()];
        }

        return array_values(array_map(function ($line) use ($currencyPrecision, $quantityPrecision) {
            return $this->prepareLineForView((array) $line, $currencyPrecision, $quantityPrecision);
        }, $initialProducts));
    }

    protected function resolveInitialPayments(array $paymentLines, array $paymentTypes, int $currencyPrecision): array
    {
        $oldPayments = old('payment');
        $initialPayments = is_array($oldPayments) ? array_values($oldPayments) : $paymentLines;

        if (empty($initialPayments)) {
            $initialPayments = [$this->buildDefaultPaymentLine($paymentTypes)];
        }

        return array_values(array_map(function ($payment) use ($currencyPrecision) {
            return $this->preparePaymentForView((array) $payment, $currencyPrecision);
        }, $initialPayments));
    }

    protected function prepareLineForView(array $line, int $currencyPrecision, int $quantityPrecision): array
    {
        $productName = (string) ($line['product_name'] ?? '');
        $subSku = (string) ($line['sub_sku'] ?? '');
        $optionLabel = trim($productName . ($subSku !== '' ? ' [' . $subSku . ']' : ''));

        $lineQuantity = (float) ($line['quantity'] ?? 1);
        $lineUnitPrice = (float) ($line['unit_price_inc_tax'] ?? ($line['unit_price'] ?? 0));
        $lineItemTax = (float) ($line['item_tax'] ?? 0);
        $lineDiscountAmount = (float) ($line['line_discount_amount'] ?? 0);
        $lineUnitPriceRaw = (float) ($line['unit_price'] ?? $lineUnitPrice);

        return [
            'transaction_sell_lines_id' => $line['transaction_sell_lines_id'] ?? null,
            'product_id' => $line['product_id'] ?? null,
            'variation_id' => $line['variation_id'] ?? null,
            'product_name' => $productName,
            'sub_sku' => $subSku,
            'product_type' => (string) ($line['product_type'] ?? 'single'),
            'product_unit_id' => $line['product_unit_id'] ?? null,
            'quantity' => $lineQuantity,
            'unit_price' => $lineUnitPriceRaw,
            'unit_price_inc_tax' => $lineUnitPrice,
            'tax_id' => $line['tax_id'] ?? null,
            'item_tax' => $lineItemTax,
            'line_discount_type' => (string) ($line['line_discount_type'] ?? 'fixed'),
            'line_discount_amount' => $lineDiscountAmount,
            'base_unit_multiplier' => $line['base_unit_multiplier'] ?? 1,
            'sell_line_note' => (string) ($line['sell_line_note'] ?? ''),
            'option_label' => $optionLabel,
            'quantity_input' => $this->getNumberFormatUtil()->formatInput($lineQuantity, $quantityPrecision),
            'unit_price_input' => $this->getNumberFormatUtil()->formatInput($lineUnitPrice, $currencyPrecision),
            'item_tax_input' => $this->getNumberFormatUtil()->formatInput($lineItemTax, $currencyPrecision),
            'line_discount_amount_input' => $this->getNumberFormatUtil()->formatInput($lineDiscountAmount, $currencyPrecision),
            'unit_price_hidden_input' => $this->getNumberFormatUtil()->formatInput($lineUnitPriceRaw, $currencyPrecision),
            'item_tax_display' => $this->getNumberFormatUtil()->formatInput($lineItemTax, $currencyPrecision),
        ];
    }

    protected function preparePaymentForView(array $payment, int $currencyPrecision): array
    {
        $amount = (float) ($payment['amount'] ?? 0);

        return [
            'payment_id' => $payment['payment_id'] ?? null,
            'method' => (string) ($payment['method'] ?? 'cash'),
            'amount' => $amount,
            'amount_input' => $this->getNumberFormatUtil()->formatInput($amount, $currencyPrecision),
            'paid_on' => $payment['paid_on'] ?? now()->format('Y-m-d H:i:s'),
            'note' => (string) ($payment['note'] ?? ''),
            'account_id' => $payment['account_id'] ?? null,
        ];
    }

    protected function buildDefaultProductLine(): array
    {
        return [
            'transaction_sell_lines_id' => null,
            'product_id' => null,
            'variation_id' => null,
            'product_name' => '',
            'sub_sku' => '',
            'product_type' => 'single',
            'product_unit_id' => null,
            'quantity' => 1,
            'unit_price' => 0,
            'unit_price_inc_tax' => 0,
            'tax_id' => null,
            'item_tax' => 0,
            'line_discount_type' => 'fixed',
            'line_discount_amount' => 0,
            'base_unit_multiplier' => 1,
            'sell_line_note' => '',
        ];
    }

    protected function buildDefaultPaymentLine(array $paymentTypes): array
    {
        $defaultMethod = array_key_exists('cash', $paymentTypes)
            ? 'cash'
            : (string) (array_key_first($paymentTypes) ?? 'cash');

        return [
            'payment_id' => null,
            'method' => $defaultMethod,
            'amount' => 0,
            'paid_on' => now()->format('Y-m-d H:i:s'),
            'note' => '',
            'account_id' => null,
        ];
    }

    protected function buildAdditionalExpenseInputValues(Transaction $transaction, int $currencyPrecision): array
    {
        $values = [];
        for ($index = 1; $index <= 4; $index++) {
            $rawValue = (float) data_get($transaction, 'additional_expense_value_' . $index, 0);
            $values[$index] = $this->getNumberFormatUtil()->formatInput($rawValue, $currencyPrecision);
        }

        return $values;
    }

    protected function resolveStatusValue(Transaction $transaction): string
    {
        $statusValue = old('status');
        if ($statusValue !== null) {
            return (string) $statusValue;
        }

        if (($transaction->status ?? '') === 'draft' && (int) ($transaction->is_quotation ?? 0) === 1) {
            return 'quotation';
        }

        if (($transaction->status ?? '') === 'draft' && ($transaction->sub_status ?? '') === 'proforma') {
            return 'proforma';
        }

        return (string) ($transaction->status ?? 'final');
    }

    protected function getNumberFormatUtil(): NumberFormatUtil
    {
        if ($this->numberFormatUtil) {
            return $this->numberFormatUtil;
        }

        $this->numberFormatUtil = app(NumberFormatUtil::class);

        return $this->numberFormatUtil;
    }

    protected function getBusinessObjectFromSession(): ?object
    {
        $business = session('business');
        if (is_object($business)) {
            return $business;
        }

        if (is_array($business)) {
            return (object) $business;
        }

        return null;
    }

    protected function fetchQuoteForTransaction(int $businessId, int $transactionId): ProductQuote
    {
        return ProductQuote::forBusiness($businessId)
            ->where('transaction_id', $transactionId)
            ->firstOrFail();
    }

    protected function fetchTaxDropdown(int $businessId): array
    {
        return TaxRate::forBusinessDropdown($businessId, true, true);
    }

    protected function fetchPaymentTypes(Transaction $transaction, int $businessId): array
    {
        return $this->transactionUtil->payment_types((int) $transaction->location_id, false, $businessId);
    }

    protected function fetchContactsDropdown(int $businessId): array
    {
        return Contact::customersDropdown($businessId, false, true);
    }

    protected function fetchLocationsDropdown(int $businessId): array
    {
        return BusinessLocation::forDropdown($businessId, false, false);
    }
}

