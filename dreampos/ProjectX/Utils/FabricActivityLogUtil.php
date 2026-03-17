<?php

namespace Modules\ProjectX\Utils;

use Illuminate\Database\Eloquent\Collection;
use Modules\ProjectX\Entities\Fabric;
use Modules\ProjectX\Entities\FabricActivityLog;

class FabricActivityLogUtil
{
    /**
     * Store one activity log row, scoped by tenant and fabric ownership.
     */
    public function log(
        int $business_id,
        int $fabric_id,
        string $action_type,
        ?string $description = null,
        ?int $user_id = null,
        array $metadata = []
    ): FabricActivityLog {
        Fabric::forBusiness($business_id)->findOrFail($fabric_id);

        return FabricActivityLog::create([
            'business_id' => $business_id,
            'fabric_id' => $fabric_id,
            'action_type' => $action_type,
            'description' => $description ?: $this->buildDescription($action_type, $metadata),
            'user_id' => $user_id,
            'metadata' => empty($metadata) ? null : $metadata,
        ]);
    }

    /**
     * Get activity logs for one fabric in a given period.
     */
    public function getForFabric(int $business_id, int $fabric_id, string $period): Collection
    {
        Fabric::forBusiness($business_id)->findOrFail($fabric_id);

        return FabricActivityLog::forBusiness($business_id)
            ->forFabric($fabric_id)
            ->withinLastYear()
            ->withinPeriod($period)
            ->with([
                'user:id,surname,first_name,last_name',
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete one activity row after tenant/fabric ownership checks.
     */
    public function deleteLog(int $business_id, int $fabric_id, int $log_id): void
    {
        $log = FabricActivityLog::forBusiness($business_id)
            ->forFabric($fabric_id)
            ->where('id', $log_id)
            ->firstOrFail();

        $log->delete();
    }

    protected function buildDescription(string $action_type, array $metadata = []): string
    {
        if ($action_type === FabricActivityLog::ACTION_FABRIC_CREATED) {
            return __('projectx::lang.activity_fabric_created');
        }

        if ($action_type === FabricActivityLog::ACTION_SETTINGS_UPDATED) {
            return __('projectx::lang.activity_settings_updated');
        }

        if ($action_type === FabricActivityLog::ACTION_IMAGE_ADDED) {
            return __('projectx::lang.activity_image_added');
        }

        if ($action_type === FabricActivityLog::ACTION_IMAGE_REMOVED) {
            return __('projectx::lang.activity_image_removed');
        }

        if ($action_type === FabricActivityLog::ACTION_ATTACHMENT_ADDED) {
            $count = max(1, (int) ($metadata['count'] ?? 1));

            return trans_choice('projectx::lang.activity_attachment_added', $count, ['count' => $count]);
        }

        if ($action_type === FabricActivityLog::ACTION_COMPOSITION_UPDATED) {
            return __('projectx::lang.activity_composition_updated');
        }

        if ($action_type === FabricActivityLog::ACTION_PANTONE_UPDATED) {
            return __('projectx::lang.activity_pantone_updated');
        }

        if ($action_type === FabricActivityLog::ACTION_SALE_ADDED) {
            return __('projectx::lang.activity_sale_added');
        }

        if ($action_type === FabricActivityLog::ACTION_SUBMITTED_FOR_APPROVAL) {
            return __('projectx::lang.activity_submitted_for_approval');
        }

        if ($action_type === FabricActivityLog::ACTION_APPROVED) {
            return __('projectx::lang.activity_approved');
        }

        if ($action_type === FabricActivityLog::ACTION_REJECTED) {
            return __('projectx::lang.activity_rejected');
        }

        return __('projectx::lang.fabric_activity');
    }
}
