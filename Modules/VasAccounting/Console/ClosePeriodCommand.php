<?php

namespace Modules\VasAccounting\Console;

use Illuminate\Console\Command;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Services\VasPeriodCloseService;

class ClosePeriodCommand extends Command
{
    protected $signature = 'vasaccounting:close-period {business_id : Business id} {period_id : Period id} {--user=1 : User id for close action}';

    protected $description = 'Close an open VAS accounting period after validations.';

    public function handle(VasPeriodCloseService $periodCloseService): int
    {
        $period = VasAccountingPeriod::query()
            ->where('business_id', (int) $this->argument('business_id'))
            ->findOrFail((int) $this->argument('period_id'));

        $periodCloseService->closePeriod($period, (int) $this->option('user'));

        $this->info("Closed VAS period [{$period->name}] successfully.");

        return self::SUCCESS;
    }
}
