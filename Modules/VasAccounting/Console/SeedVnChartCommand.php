<?php

namespace Modules\VasAccounting\Console;

use App\Business;
use Illuminate\Console\Command;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class SeedVnChartCommand extends Command
{
    protected $signature = 'vasaccounting:seed-vn-chart {business_id? : Optional business id} {--force : Reseed and refresh defaults}';

    protected $description = 'Seed the Vietnam chart of accounts, tax codes, and document sequences for VAS accounting.';

    public function handle(VasAccountingUtil $vasUtil): int
    {
        $businessId = $this->argument('business_id');
        $businessIds = $businessId ? [(int) $businessId] : Business::query()->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($businessIds as $id) {
            $result = $vasUtil->bootstrapBusiness($id, 1);
            $this->info("Seeded VAS defaults for business [{$id}] with period [{$result['period']->name}].");
        }

        return self::SUCCESS;
    }
}
