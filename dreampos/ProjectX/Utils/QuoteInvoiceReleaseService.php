<?php

namespace Modules\ProjectX\Utils;

use App\Product;
use App\ProductVariation;
use App\Transaction;
use App\Unit;
use App\Utils\TransactionUtil;
use App\Variation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\ProjectX\Entities\Fabric;
use Modules\ProjectX\Entities\Quote;
use Modules\ProjectX\Entities\QuoteLine;
use Modules\ProjectX\Entities\Trim;

class QuoteInvoiceReleaseService
{
    protected TransactionUtil $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    public function releaseToDraftInvoice(int $business_id, Quote $quote, int $user_id): Transaction
    {
        if (! empty($quote->transaction_id)) {
            $existingTransaction = $this->findExistingTransaction($business_id, (int) $quote->transaction_id);
            if ($existingTransaction) {
                return $existingTransaction;
            }
        }

        if (empty($quote->contact_id)) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_release_contact_required'));
        }

        if (empty($quote->location_id)) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_release_location_required'));
        }

        $quote->loadMissing(['lines.fabric', 'lines.trim']);
        if ($quote->lines->isEmpty()) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_lines_required'));
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

    public function buildSellLinePayload(int $business_id, Quote $quote, QuoteLine $line, int $user_id): array
    {
        [$product, $variation, $sku] = $this->resolveProductVariationForQuoteLine($business_id, $quote, $line, $user_id);

        $qty = (float) ($line->costing_input['qty'] ?? 0);
        $unitCost = (float) ($line->costing_breakdown['unit_cost'] ?? 0);
        $lineTotal = (float) ($line->costing_breakdown['total_cost'] ?? 0);

        if ($qty <= 0 || $unitCost < 0) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_line_costing_invalid'));
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

    protected function resolveProductVariationForQuoteLine(int $business_id, Quote $quote, QuoteLine $line, int $user_id): array
    {
        $trim = null;
        if (! empty($line->trim_id)) {
            $trimSnapshot = is_array($line->trim_snapshot) ? $line->trim_snapshot : [];
            $trim = $line->trim;
            $sku = trim((string) ($trimSnapshot['part_number'] ?? optional($trim)->part_number ?? ''));

            if ($sku === '') {
                $sku = 'PXT-' . (int) $line->trim_id;
            }
        } else {
            $snapshot = is_array($line->fabric_snapshot) ? $line->fabric_snapshot : [];
            $sku = trim((string) ($snapshot['fabric_sku'] ?? ''));
            $fabric = $line->fabric;

            if ($sku === '') {
                $sku = 'PXF-' . (int) $line->fabric_id;
                if (! $fabric) {
                    $fabric = Fabric::forBusiness($business_id)->findOrFail((int) $line->fabric_id);
                }
                $fabric->fabric_sku = $sku;
                $fabric->save();

                $snapshot['fabric_sku'] = $sku;
                $line->fabric_snapshot = $snapshot;
                $line->save();
            }
        }

        $variation = Variation::where('sub_sku', $sku)
            ->whereHas('product', function ($query) use ($business_id) {
                $query->where('business_id', $business_id);
            })
            ->with('product')
            ->first();

        if ($variation && $variation->product) {
            return [$variation->product, $variation, $sku];
        }

        $product = Product::where('business_id', $business_id)
            ->where('sku', $sku)
            ->first();

        if ($product) {
            $existingVariation = Variation::where('product_id', $product->id)->orderBy('id')->first();
            if ($existingVariation) {
                return [$product, $existingVariation, $sku];
            }

            $newVariation = $this->createDummyVariationForProduct($product, $sku, (float) ($line->costing_breakdown['unit_cost'] ?? 0));

            return [$product, $newVariation, $sku];
        }

        $product = $this->createAutoMappedProduct($business_id, $quote, $line, $sku, $user_id, $trim);
        $variation = $this->createDummyVariationForProduct($product, $sku, (float) ($line->costing_breakdown['unit_cost'] ?? 0));

        return [$product, $variation, $sku];
    }

    protected function buildSellLineNote(QuoteLine $line, string $sku, float $lineTotal): string
    {
        if (! empty($line->trim_id)) {
            $trimName = $line->trim_snapshot['name'] ?? optional($line->trim)->name ?? ('Trim #' . $line->trim_id);
            $partNumber = trim((string) ($line->trim_snapshot['part_number'] ?? optional($line->trim)->part_number ?? ''));
            $displayCode = $partNumber !== '' ? $partNumber : $sku;

            $lineNote = $trimName . ' [' . $displayCode . ']';
            $lineNote .= ' | Total: ' . number_format($lineTotal, 4, '.', '');

            return $lineNote;
        }

        $fabricName = $line->fabric_snapshot['name'] ?? optional($line->fabric)->name ?? ('Fabric #' . $line->fabric_id);
        $millArticleNo = $line->fabric_snapshot['mill_article_no'] ?? optional($line->fabric)->mill_article_no ?? null;

        $lineNote = $fabricName . ' [' . $sku . ']';
        if (! empty($millArticleNo)) {
            $lineNote .= ' - ' . $millArticleNo;
        }

        $lineNote .= ' | Total: ' . number_format($lineTotal, 4, '.', '');

        return $lineNote;
    }

    protected function createAutoMappedProduct(int $business_id, Quote $quote, QuoteLine $line, string $sku, int $user_id, ?Trim $trim = null): Product
    {
        $unitId = Unit::where('business_id', $business_id)->value('id');
        if (empty($unitId)) {
            $unitId = Unit::query()->value('id');
        }
        if (empty($unitId)) {
            throw new \RuntimeException(__('projectx::lang.quote_auto_product_unit_missing'));
        }

        if (! empty($line->trim_id)) {
            if (! $trim) {
                $trim = $line->trim;
            }
            if (! $trim && ! empty($line->trim_id)) {
                $trim = Trim::forBusiness($business_id)->find((int) $line->trim_id);
            }

            $trimName = $line->trim_snapshot['name'] ?? optional($trim)->name ?? ('Trim #' . $line->trim_id);
            $productName = mb_substr($trimName . ' (' . $sku . ')', 0, 255);
        } else {
            $fabricName = $line->fabric_snapshot['name'] ?? optional($line->fabric)->name ?? ('Fabric #' . $line->fabric_id);
            $productName = mb_substr($fabricName . ' (' . $sku . ')', 0, 255);
        }

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

        $fabric = $line->fabric;
        if (! $fabric && ! empty($line->fabric_id)) {
            $fabric = Fabric::forBusiness($business_id)->find((int) $line->fabric_id);
        }

        if ($fabric && ! empty($fabric->image_path)) {
            $this->copyFabricImageToProduct($product, (string) $fabric->image_path);
        }

        if ($trim && ! empty($trim->image_path)) {
            $this->copyTrimImageToProduct($product, (string) $trim->image_path);
        }

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

    protected function copyFabricImageToProduct(Product $product, string $fabricImagePath): void
    {
        $this->copySourceImageToProduct($product, $fabricImagePath, 'fabric');
    }

    protected function copyTrimImageToProduct(Product $product, string $trimImagePath): void
    {
        $this->copySourceImageToProduct($product, $trimImagePath, 'trim');
    }

    protected function copySourceImageToProduct(Product $product, string $sourceImagePath, string $sourceLabel): void
    {
        try {
            $disk = Storage::disk('public');
            if (! $disk->exists($sourceImagePath)) {
                return;
            }

            $sourcePath = $disk->path($sourceImagePath);
            if (! is_file($sourcePath)) {
                return;
            }

            $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
            if (! in_array($extension, $allowedExtensions, true)) {
                $extension = 'jpg';
            }

            $filename = 'pxf_' . (int) $product->id . '_' . Str::random(6) . '.' . $extension;
            $imageDir = public_path('uploads/' . trim((string) config('constants.product_img_path', 'img'), '/'));

            if (! is_dir($imageDir) && ! mkdir($imageDir, 0755, true) && ! is_dir($imageDir)) {
                throw new \RuntimeException('Unable to create destination directory for product image.');
            }

            $destinationPath = rtrim($imageDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (! @copy($sourcePath, $destinationPath)) {
                throw new \RuntimeException('Unable to copy source image to product image path.');
            }

            $product->image = $filename;
            $product->save();
        } catch (\Exception $e) {
            \Log::warning('ProjectX source image copy failed for auto-created product', [
                'product_id' => $product->id,
                'source' => $sourceLabel,
                'source_image_path' => $sourceImagePath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
