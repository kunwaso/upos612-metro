<?php

namespace Modules\VasAccounting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\VasAccounting\Services\IntegrationHubService;

class RunIntegrationTaskJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $afterCommit = true;

    public function __construct(public int $runId)
    {
    }

    public function handle(IntegrationHubService $integrationHubService): void
    {
        $integrationHubService->processRunById($this->runId);
    }
}
