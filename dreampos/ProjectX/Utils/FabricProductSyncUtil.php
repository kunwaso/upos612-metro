<?php

namespace Modules\ProjectX\Utils;

use App\Product;
use App\Variation;
use Modules\ProjectX\Entities\Fabric;

class FabricProductSyncUtil
{
    public function findLinkedFabric(int $business_id, int $product_id): ?Fabric
    {
        return Fabric::forBusiness($business_id)
            ->where('product_id', $product_id)
            ->with('pantoneItems:id,fabric_id,pantone_code,sort_order')
            ->first();
    }

    public function createLinkedFabricFromProduct(
        int $business_id,
        Product $product,
        ?int $variation_id = null,
        ?int $user_id = null
    ): Fabric {
        $existing = $this->findLinkedFabric($business_id, (int) $product->id);
        if ($existing) {
            $this->syncFabricFromProduct($business_id, $product, $variation_id);

            return $existing->fresh();
        }

        $variation = $this->resolveLinkedVariation($product, $variation_id);
        $fabric = Fabric::create([
            'business_id' => $business_id,
            'name' => (string) $product->name,
            'status' => Fabric::STATUS_DRAFT,
            'purchase_price' => (float) ($variation->default_purchase_price ?? 0),
            'sale_price' => (float) ($variation->default_sell_price ?? 0),
            'fabric_sku' => $this->resolveSku($product, $variation),
            'product_id' => (int) $product->id,
            'variation_id' => (int) $variation->id,
            'created_by' => $user_id,
        ]);

        return $fabric->fresh();
    }

    public function syncFabricFromProduct(int $business_id, Product $product, ?int $variation_id = null): ?Fabric
    {
        $fabric = $this->findLinkedFabric($business_id, (int) $product->id);
        if (! $fabric) {
            return null;
        }

        $variation = $this->resolveLinkedVariation($product, $variation_id ?: (int) $fabric->variation_id);

        $fabric->name = (string) $product->name;
        $fabric->fabric_sku = $this->resolveSku($product, $variation);
        $fabric->purchase_price = (float) ($variation->default_purchase_price ?? 0);
        $fabric->sale_price = (float) ($variation->default_sell_price ?? 0);
        $fabric->product_id = (int) $product->id;
        $fabric->variation_id = (int) $variation->id;
        $fabric->save();

        return $fabric->fresh();
    }

    public function syncProductFromFabric(int $business_id, Fabric $fabric): ?Product
    {
        $product_id = (int) ($fabric->product_id ?? 0);
        if ($product_id <= 0) {
            return null;
        }

        $product = Product::where('business_id', $business_id)
            ->with('product_tax:id,amount')
            ->find($product_id);
        if (! $product) {
            return null;
        }

        $variation_id = (int) ($fabric->variation_id ?? 0);
        if ($product->type === 'variable' && $variation_id <= 0) {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_required'));
        }

        $variation = $this->resolveLinkedVariation($product, $variation_id);

        $product->name = (string) ($fabric->name ?: $product->name);
        if (! empty($fabric->fabric_sku)) {
            $product->sku = (string) $fabric->fabric_sku;
        }
        $product->save();

        $variation->sub_sku = $this->resolveSku($product, $variation, (string) ($fabric->fabric_sku ?? ''));
        $variation->default_purchase_price = (float) ($fabric->purchase_price ?? 0);
        $variation->default_sell_price = (float) ($fabric->sale_price ?? 0);

        $taxRate = (float) ($product->product_tax->amount ?? 0);
        $variation->dpp_inc_tax = $this->addTax((float) $variation->default_purchase_price, $taxRate);
        $variation->sell_price_inc_tax = $this->addTax((float) $variation->default_sell_price, $taxRate);
        $variation->save();

        if ($product->type !== 'variable' && empty($fabric->variation_id)) {
            $fabric->variation_id = (int) $variation->id;
            $fabric->save();
        }

        return $product->fresh();
    }

    public function unlinkFabricForDeletedProduct(int $business_id, int $product_id): void
    {
        Fabric::forBusiness($business_id)
            ->where('product_id', $product_id)
            ->update([
                'product_id' => null,
                'variation_id' => null,
            ]);
    }

    public function resolveLinkedVariation(Product $product, ?int $variation_id = null): Variation
    {
        $variation_id = ! empty($variation_id) ? (int) $variation_id : 0;

        if ($product->type === 'variable') {
            if ($variation_id <= 0) {
                throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_required'));
            }

            $variation = Variation::where('product_id', $product->id)
                ->where('id', $variation_id)
                ->first();

            if (! $variation) {
                throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
            }

            return $variation;
        }

        $variationQuery = Variation::where('product_id', $product->id)->orderBy('id');
        if ($variation_id > 0) {
            $variationQuery->where('id', $variation_id);
        }

        $variation = $variationQuery->first();
        if (! $variation) {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        return $variation;
    }

    protected function resolveSku(Product $product, Variation $variation, string $preferredSku = ''): string
    {
        $preferredSku = trim($preferredSku);
        if ($preferredSku !== '') {
            return $preferredSku;
        }

        $variationSku = trim((string) ($variation->sub_sku ?? ''));
        if ($variationSku !== '') {
            return $variationSku;
        }

        return trim((string) ($product->sku ?? ''));
    }

    protected function addTax(float $amount, float $taxRatePercent): float
    {
        if ($taxRatePercent <= 0) {
            return round($amount, 4);
        }

        return round($amount + (($amount * $taxRatePercent) / 100), 4);
    }
}

