<?php

namespace Modules\VasAccounting\Console;

use Illuminate\Console\Command;
use Modules\VasAccounting\Services\ProviderHealthService;

class ProvidersHealthCommand extends Command
{
    protected $signature = 'vas:providers:health {business_id : Business id}';

    protected $description = 'Show bank, tax, e-invoice, and payroll provider readiness for a business.';

    public function handle(ProviderHealthService $providerHealthService): int
    {
        $rows = collect($providerHealthService->healthForBusiness((int) $this->argument('business_id')))
            ->map(fn (array $row) => [
                $row['domain'],
                $row['provider'],
                $row['adapter_registered'] ? 'yes' : 'no',
                $row['production_ready'] ? 'yes' : 'no',
                $row['ready'] ? 'yes' : 'no',
                empty($row['missing_config']) ? '-' : implode(', ', $row['missing_config']),
            ])
            ->all();

        $this->table(
            ['Domain', 'Provider', 'Adapter', 'Production ready', 'Ready', 'Missing config'],
            $rows
        );

        return self::SUCCESS;
    }
}
