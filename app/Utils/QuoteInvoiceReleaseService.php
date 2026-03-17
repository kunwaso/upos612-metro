<?php

namespace App\Utils;

use App\Product;
use App\ProductQuote;
use App\ProductQuoteLine;
use App\ProductVariation;
use App\Transaction;
use App\Unit;
use App\Variation;
use Illuminate\Support\Facades\DB;

class QuoteInvoiceReleaseService
{
    protected TransactionUtil $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    public function releaseToDraftInvoice(int $business_id, ProductQuote $quote, int $user_id): Transaction
    {
        if (! empty($quote->transaction_id)) {
            $existingTransaction = $this->findExistingTransaction($business_id, (int) $quote->transaction_id);
            if ($existingTransaction) {
                return $existingTransaction;
            }
        }

        if (empty($quote->contact_id)) {
            throw new \InvalidArgumentException(__('product.quote_release_contact_required'));
        }

        if (empty($quote->location_id)) {
            throw new \InvalidArgumentException(__('product.quote_release_location_required'));
        }

        $quote->loadMissing('lines.product');
        if ($quote->lines->isEmpty()) {
            throw new \InvalidArgumentException(__('product.quote_lines_required'));
        }

        return DB::transaction(function () use ($business_id, $quote, $user_id) {
            $sellLines = [];

            foreach ($quote->lines as $line) {
                $sellLines[] = $this->buildSellLinePayload($business_id, $quote, $line, $user_id);
            }

            $finalTotal = (float) $quote->grand_total;
            $invoiceTotal = [
                'total_before_tax' => $finalTotal,
                'tax' => 0,
            ];

            $transactionInput = [
                'type' => 'sell',
                'status' => 'draft',
                'location_id' => (int) $quote->location_id,
                'contact_id' => (int) $quote->contact_id,
                'transaction_date' => now()->format('Y-m-d H:i:s'),
                'final_total' => $finalTotal,
                'discount_amount' => 0,
                'tax_rate_id' => null,
                'is_direct_sale' => 1,
            ];

            $transaction = $this->transactionUtil->createSellTransaction(
                $business_id,
                $transactionInput,
                $invoiceTotal,
                $user_id,
                false
            );

            $this->transactionUtil->createOrUpdateSellLines(
                $transaction,
                $sellLines,
                (int) $quote->location_id,
                false,
                null,
                [],
                false
            );

            $quote->transaction_id = (int) $transaction->id;
            $quote->save();

            return $transaction->fresh();
        });
    }

    public function buildSellLinePayload(int $business_id, ProductQuote $quote, ProductQuoteLine $line, int $user_id): array
    {
        [$product, $variation, $sku] = $this->resolveProductVariationForQuoteLine($business_id, $quote, $line, $user_id);

        $qty = (float) ($line->costing_input['qty'] ?? 0);
        $unitCost = (float) ($line->costing_breakdown['unit_cost'] ?? 0);
        $lineTotal = (float) ($line->costing_breakdown['total_cost'] ?? 0);

        if ($qty <= 0 || $unitCost < 0) {
            throw new \InvalidArgumentException(__('product.quote_line_costing_invalid'));
        }

        return [
            'product_id' => (int) $product->id,
            'variation_id' => (int) $variation->id,
            'quantity' => round($qty, 4),
            'unit_price' => round($unitCost, 4),
            'unit_price_inc_tax' => round($unitCost, 4),
            'item_tax' => 0,
            'tax_id' => null,
            'line_discount_type' => 'fixed',
            'line_discount_amount' => 0,
            'sell_line_note' => $this->buildSellLineNote($line, $sku, $lineTotal),
        ];
    }

    protected function findExistingTransaction(int $business_id, int $transaction_id): ?Transaction
    {
        return Transaction::where('business_id', $business_id)
            ->where('id', $transaction_id)
            ->first();
    }

    protected function resolveProductVariationForQuoteLine(int $business_id, ProductQuote $quote, ProductQuoteLine $line, int $user_id): array
    {
        $snapshot = is_array($line->product_snapshot) ? $line->product_snapshot : [];
        $sku = trim((string) ($snapshot['sku'] ?? optional($line->product)->sku ?? ''));

        $product = null;
        if (! empty($line->product_id)) {
            $product = Product::where('business_id', $business_id)
                ->where('id', (int) $line->product_id)
                ->first();
        }

        if (! $product && $sku !== '') {
            $variationBySku = Variation::where('sub_sku', $sku)
                ->whereHas('product', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                })
                ->with('product')
                ->first();

            if ($variationBySku && $variationBySku->product) {
                return [$variationBySku->product, $variationBySku, $sku];
            }

            $product = Product::where('business_id', $business_id)
                ->where('sku', $sku)
                ->first();
        }

        if (! $product) {
            if ($sku === '') {
                $sku = 'PQP-' . (int) ($line->id ?: now()->timestamp);
            }

            $product = $this->createAutoMappedProduct($business_id, $quote, $line, $sku, $user_id);
        }

        if ($sku === '') {
            $sku = trim((string) ($product->sku ?? ''));
        }
        if ($sku === '') {
            $sku = 'PQP-' . (int) $product->id;
            $product->sku = $sku;
            $product->save();
        }

        $variation = Variation::where('product_id', $product->id)
            ->where('sub_sku', $sku)
            ->orderBy('id')
            ->first();

        if (! $variation) {
            $variation = Variation::where('product_id', $product->id)
                ->orderBy('id')
                ->first();
        }

        if (! $variation) {
            $variation = $this->createDummyVariationForProduct(
                $product,
                $sku,
                (float) ($line->costing_breakdown['unit_cost'] ?? 0)
            );
        }

        return [$product, $variation, $sku];
    }

    protected function buildSellLineNote(ProductQuoteLine $line, string $sku, float $lineTotal): string
    {
        $productName = $line->product_snapshot['name'] ?? optional($line->product)->name ?? ('Product #' . $line->product_id);

        $lineNote = $productName . ' [' . $sku . ']';
        $lineNote .= ' | Total: ' . number_format($lineTotal, 4, '.', '');

        return $lineNote;
    }

    protected function createAutoMappedProduct(int $business_id, ProductQuote $quote, ProductQuoteLine $line, string $sku, int $user_id): Product
    {
        $unitId = Unit::where('business_id', $business_id)->value('id');
        if (empty($unitId)) {
            $unitId = Unit::query()->value('id');
        }
        if (empty($unitId)) {
            throw new \RuntimeException(__('product.quote_auto_product_unit_missing'));
        }

        $productName = (string) ($line->product_snapshot['name'] ?? ('Quote Product ' . $sku));
        $productName = mb_substr($productName . ' (' . $sku . ')', 0, 255);

        $product = Product::create([
            'name' => $productName,
            'business_id' => $business_id,
            'type' => 'single',
            'unit_id' => (int) $unitId,
            'tax_type' => 'exclusive',
            'enable_stock' => 0,
            'alert_quantity' => 0,
            'sku' => $sku,
            'barcode_type' => 'C128',
            'created_by' => $user_id,
            'not_for_selling' => 0,
        ]);

        $product->product_locations()->sync([(int) $quote->location_id]);

        return $product;
    }

    protected function createDummyVariationForProduct(Product $product, string $sku, float $unitCost): Variation
    {
        $productVariation = ProductVariation::where('product_id', $product->id)
            ->where('is_dummy', 1)
            ->first();

        if (! $productVariation) {
            $productVariation = ProductVariation::create([
                'name' => 'DUMMY',
                'product_id' => $product->id,
                'is_dummy' => 1,
            ]);
        }

        return Variation::create([
            'name' => 'DUMMY',
            'product_id' => $product->id,
            'sub_sku' => $sku,
            'product_variation_id' => $productVariation->id,
            'default_purchase_price' => round($unitCost, 4),
            'dpp_inc_tax' => round($unitCost, 4),
            'profit_percent' => 0,
            'default_sell_price' => round($unitCost, 4),
            'sell_price_inc_tax' => round($unitCost, 4),
        ]);
    }
}
