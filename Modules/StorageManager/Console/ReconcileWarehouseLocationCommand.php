<?php

namespace Modules\StorageManager\Console;

use Illuminate\Console\Command;
use Modules\StorageManager\Services\ReconciliationService;

class ReconcileWarehouseLocationCommand extends Command
{
    protected $signature = 'storage-manager:reconcile {business_id} {location_id?}';

    protected $description = 'Reconcile StorageManager slot stock against source location stock.';

    public function handle(ReconciliationService $reconciliationService): int
    {
        $businessId = (int) $this->argument('business_id');
        $locationId = $this->argument('location_id');

        if ($businessId <= 0) {
            $this->error('business_id is required.');

            return self::FAILURE;
        }

        if ($locationId !== null) {
            $result = $reconciliationService->reconcileLocation($businessId, (int) $locationId);
            $this->renderLocationResult($result);

            return ($result['has_blockers'] ?? false) ? self::FAILURE : self::SUCCESS;
        }

        $summary = $reconciliationService->controlTowerSummary($businessId);
        $this->info('Configured locations: ' . $summary['configured_locations']);
        $this->info('Locations with blockers: ' . $summary['mismatch_locations']);

        foreach ($summary['location_rows'] as $row) {
            $this->renderLocationResult($row);
        }

        return $summary['mismatch_locations'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function renderLocationResult(array $row): void
    {
        $this->line(sprintf(
            '[%s] slot=%s source=%s mismatches=%s status=%s',
            $row['location_name'] ?: $row['location_id'],
            $row['slot_total'],
            $row['source_total'],
            $row['mismatch_count'],
            $row['has_blockers'] ? 'BLOCKED' : 'ALIGNED'
        ));
    }
}
