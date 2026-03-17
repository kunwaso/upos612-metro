<?php

namespace Modules\ProjectX\Utils;

use App\Product;
use App\Variation;

class FabricLinkSelectorResolver
{
    public function parseFabricLinkSelector(string $selector): array
    {
        $selector = trim($selector);
        if ($selector === '') {
            return [
                'mode' => 'none',
                'variation_id' => null,
                'group_key' => null,
                'value_key' => null,
            ];
        }

        if (is_numeric($selector)) {
            return [
                'mode' => 'existing',
                'variation_id' => (int) $selector,
                'group_key' => null,
                'value_key' => null,
            ];
        }

        if (preg_match('/^existing:(\d+)$/', $selector, $matches) === 1) {
            return [
                'mode' => 'existing',
                'variation_id' => (int) $matches[1],
                'group_key' => null,
                'value_key' => null,
            ];
        }

        if (preg_match('/^new:([^:]+):([^:]+)$/', $selector, $matches) === 1) {
            return [
                'mode' => 'new',
                'variation_id' => null,
                'group_key' => (string) $matches[1],
                'value_key' => (string) $matches[2],
            ];
        }

        return [
            'mode' => 'invalid',
            'variation_id' => null,
            'group_key' => null,
            'value_key' => null,
        ];
    }

    public function resolveCreateVariationIdFromSelector(Product $product, array $inputVariations, string $selector): int
    {
        $parsed = $this->parseFabricLinkSelector($selector);
        if ($parsed['mode'] === 'none') {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_required'));
        }

        if ($parsed['mode'] === 'existing') {
            $variationId = (int) $parsed['variation_id'];
            $existing = Variation::where('product_id', $product->id)
                ->where('id', $variationId)
                ->first();

            if (! empty($existing)) {
                return (int) $existing->id;
            }

            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        if ($parsed['mode'] !== 'new') {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        $groupKey = (string) ($parsed['group_key'] ?? '');
        $valueKey = (string) ($parsed['value_key'] ?? '');
        $groupData = isset($inputVariations[$groupKey]) && is_array($inputVariations[$groupKey])
            ? $inputVariations[$groupKey]
            : null;
        $variationInput = isset($groupData['variations'][$valueKey]) && is_array($groupData['variations'][$valueKey])
            ? $groupData['variations'][$valueKey]
            : null;

        if (empty($groupData) || empty($variationInput)) {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        if ((string) ($variationInput['is_hidden'] ?? '0') === '1') {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        $variationName = trim((string) ($variationInput['value'] ?? ''));
        $variationSku = trim((string) ($variationInput['sub_sku'] ?? ''));
        $variationTemplateId = (int) ($groupData['variation_template_id'] ?? 0);
        $variationGroupName = trim((string) ($groupData['name'] ?? ''));

        if ($variationName === '') {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        $variationQuery = Variation::where('product_id', $product->id)
            ->where('name', $variationName);

        if ($variationSku !== '') {
            $variationQuery->where('sub_sku', $variationSku);
        }

        if ($variationTemplateId > 0) {
            $variationQuery->whereHas('product_variation', function ($query) use ($variationTemplateId) {
                $query->where('variation_template_id', $variationTemplateId);
            });
        } elseif ($variationGroupName !== '') {
            $variationQuery->whereHas('product_variation', function ($query) use ($variationGroupName) {
                $query->where('name', $variationGroupName);
            });
        }

        $variation = $variationQuery->orderBy('id')->first();
        if (empty($variation)) {
            $fallbackVariationQuery = Variation::where('product_id', $product->id)
                ->where('name', $variationName);
            if ($variationSku !== '') {
                $fallbackVariationQuery->where('sub_sku', $variationSku);
            }

            $variation = $fallbackVariationQuery->orderBy('id')->first();
        }

        if (empty($variation)) {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        return (int) $variation->id;
    }

    public function resolveExistingVariationIdFromSelector(Product $product, string $selector): int
    {
        $parsed = $this->parseFabricLinkSelector($selector);
        if ($parsed['mode'] === 'none') {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_required'));
        }

        if ($parsed['mode'] !== 'existing') {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        $variationId = (int) ($parsed['variation_id'] ?? 0);
        if ($variationId <= 0) {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        $exists = Variation::where('product_id', $product->id)
            ->where('id', $variationId)
            ->exists();

        if (! $exists) {
            throw new \RuntimeException(__('projectx::lang.fabric_linked_variation_invalid'));
        }

        return $variationId;
    }
}
