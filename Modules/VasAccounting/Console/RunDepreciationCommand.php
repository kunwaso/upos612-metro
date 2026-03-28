<?php

namespace Modules\VasAccounting\Console;

use Illuminate\Console\Command;
use Modules\VasAccounting\Services\VasDepreciationService;

class RunDepreciationCommand extends Command
{
    protected $signature = 'vasaccounting:run-depreciation {business_id : Business id} {period_id? : Optional period id} {--user=1 : User id for voucher posting}';

    protected $description = 'Run monthly depreciation for VAS fixed assets.';

    public function handle(VasDepreciationService $depreciationService): int
    {
        $result = $depreciationService->run(
            (int) $this->argument('business_id'),
            $this->argument('period_id') ? (int) $this->argument('period_id') : null,
            (int) $this->option('user')
        );

        $this->info("Depreciation run completed for period [{$result['period']->name}] with {$result['depreciations_created']} new entries.");

        return self::SUCCESS;
    }
}
