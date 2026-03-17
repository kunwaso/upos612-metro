<?php

namespace App\Utils;

use App\Product;
use App\ProductActivityLog;
use Illuminate\Database\Eloquent\Collection;

class ProductActivityLogUtil
{
    public function log(
        int $business_id,
        int $product_id,
        string $action_type,
        ?string $description = null,
        ?int $user_id = null,
        array $metadata = []
    ): ProductActivityLog {
        Product::where('business_id', $business_id)->findOrFail($product_id);

        return ProductActivityLog::create([
            'business_id' => $business_id,
            'product_id' => $product_id,
            'action_type' => $action_type,
            'description' => $description ?: $this->buildDescription($action_type, $metadata),
            'user_id' => $user_id,
            'metadata' => empty($metadata) ? null : $metadata,
        ]);
    }

    public function getForProduct(int $business_id, int $product_id, string $period): Collection
    {
        Product::where('business_id', $business_id)->findOrFail($product_id);

        return ProductActivityLog::forBusiness($business_id)
            ->forProduct($product_id)
            ->withinLastYear()
            ->withinPeriod($period)
            ->with(['user:id,surname,first_name,last_name'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function deleteLog(int $business_id, int $product_id, int $log_id): void
    {
        $log = ProductActivityLog::forBusiness($business_id)
            ->forProduct($product_id)
            ->where('id', $log_id)
            ->firstOrFail();

        $log->delete();
    }

    protected function buildDescription(string $action_type, array $metadata = []): string
    {
        $map = [
            ProductActivityLog::ACTION_FABRIC_CREATED => __('product.activity_fabric_created'),
            ProductActivityLog::ACTION_SETTINGS_UPDATED => __('product.activity_settings_updated'),
            ProductActivityLog::ACTION_IMAGE_ADDED => __('product.activity_image_added'),
            ProductActivityLog::ACTION_IMAGE_REMOVED => __('product.activity_image_removed'),
            ProductActivityLog::ACTION_COMPOSITION_UPDATED => __('product.activity_composition_updated'),
            ProductActivityLog::ACTION_PANTONE_UPDATED => __('product.activity_pantone_updated'),
            ProductActivityLog::ACTION_SALE_ADDED => __('product.activity_sale_added'),
            ProductActivityLog::ACTION_SUBMITTED_FOR_APPROVAL => __('product.activity_submitted_for_approval'),
            ProductActivityLog::ACTION_APPROVED => __('product.activity_approved'),
            ProductActivityLog::ACTION_REJECTED => __('product.activity_rejected'),
        ];

        if ($action_type === ProductActivityLog::ACTION_ATTACHMENT_ADDED) {
            $count = max(1, (int) ($metadata['count'] ?? 1));

            return trans_choice('product.activity_attachment_added', $count, ['count' => $count]);
        }

        return $map[$action_type] ?? __('product.product_activity');
    }
}
