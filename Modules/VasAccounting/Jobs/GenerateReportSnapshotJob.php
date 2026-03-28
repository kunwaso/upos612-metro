<?php

namespace Modules\VasAccounting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\VasAccounting\Services\ReportSnapshotService;

class GenerateReportSnapshotJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $afterCommit = true;

    public function __construct(public int $snapshotId)
    {
    }

    public function handle(ReportSnapshotService $snapshotService): void
    {
        $snapshotService->generateSnapshotById($this->snapshotId);
    }
}
