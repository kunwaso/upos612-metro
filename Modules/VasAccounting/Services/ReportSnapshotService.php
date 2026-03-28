<?php

namespace Modules\VasAccounting\Services;

use Modules\VasAccounting\Entities\VasReportSnapshot;
use Modules\VasAccounting\Jobs\GenerateReportSnapshotJob;
use Throwable;

class ReportSnapshotService
{
    public function __construct(protected EnterpriseReportingService $enterpriseReportingService)
    {
    }

    public function recentSnapshots(int $businessId, int $limit = 20)
    {
        return VasReportSnapshot::query()
            ->where('business_id', $businessId)
            ->latest()
            ->take($limit)
            ->get();
    }

    public function queueSnapshot(int $businessId, string $reportKey, array $filters, int $userId): VasReportSnapshot
    {
        if (! $this->enterpriseReportingService->supports($reportKey)) {
            throw new \InvalidArgumentException("Unsupported VAS snapshot report [{$reportKey}].");
        }

        $definition = $this->enterpriseReportingService->definition($reportKey);

        $snapshot = VasReportSnapshot::create([
            'business_id' => $businessId,
            'accounting_period_id' => $filters['period_id'] ?? null,
            'report_key' => $reportKey,
            'snapshot_name' => $filters['snapshot_name'] ?? ($definition['title'] ?? ucfirst(str_replace('_', ' ', $reportKey))),
            'status' => 'queued',
            'generated_by' => $userId,
            'filters' => $filters,
        ]);

        dispatch(new GenerateReportSnapshotJob((int) $snapshot->id));

        return $snapshot;
    }

    public function generateSnapshotById(int $snapshotId): ?VasReportSnapshot
    {
        $snapshot = VasReportSnapshot::query()->find($snapshotId);
        if (! $snapshot) {
            return null;
        }

        return $this->generateSnapshot($snapshot);
    }

    public function generateSnapshot(VasReportSnapshot $snapshot): VasReportSnapshot
    {
        $snapshot->status = 'processing';
        $snapshot->error_message = null;
        $snapshot->save();

        try {
            $dataset = $this->enterpriseReportingService->buildDataset((string) $snapshot->report_key, (int) $snapshot->business_id, (array) $snapshot->filters);

            $snapshot->snapshot_name = $snapshot->snapshot_name ?: (string) ($dataset['title'] ?? $snapshot->report_key);
            $snapshot->payload = $dataset;
            $snapshot->generated_at = now();
            $snapshot->status = 'ready';
            $snapshot->save();

            return $snapshot->fresh();
        } catch (Throwable $exception) {
            $snapshot->status = 'failed';
            $snapshot->error_message = $exception->getMessage();
            $snapshot->save();

            throw $exception;
        }
    }
}
