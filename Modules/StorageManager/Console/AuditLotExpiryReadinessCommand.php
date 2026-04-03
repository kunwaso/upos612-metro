<?php

namespace Modules\StorageManager\Console;

use Illuminate\Console\Command;
use Modules\StorageManager\Services\ReconciliationService;

class AuditLotExpiryReadinessCommand extends Command
{
    protected $signature = 'storage-manager:lot-readiness {business_id} {location_id?}';

    protected $description = 'Audit lot and expiry data readiness for StorageManager execution rollout.';

    public function handle(ReconciliationService $reconciliationService): int
    {
        $businessId = (int) $this->argument('business_id');
        $locationId = $this->argument('location_id');

        if ($businessId <= 0) {
            $this->error('business_id is required.');

            return self::FAILURE;
        }

        $result = $reconciliationService->lotExpiryReadinessAudit(
            $businessId,
            $locationId !== null ? (int) $locationId : null
        );

        $this->line('Tracked rows: ' . $result['tracked_rows']);
        $this->line('Missing lot rows: ' . $result['lot_missing_count']);
        $this->line('Missing expiry rows: ' . $result['expiry_missing_count']);
        $this->info($result['ready'] ? 'READY' : 'ATTENTION REQUIRED');

        return $result['ready'] ? self::SUCCESS : self::FAILURE;
    }
}
