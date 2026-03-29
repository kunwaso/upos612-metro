<?php

namespace Modules\VasAccounting\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\VasAccounting\Services\LegacyAccountingBackfillService;

class CutoverBackfillCommand extends Command
{
    protected $signature = 'vas:cutover:backfill
        {business_id : Business id}
        {--from= : Optional inclusive start date (YYYY-MM-DD)}
        {--to= : Optional inclusive end date (YYYY-MM-DD)}
        {--user=1 : User id recorded on imported vouchers}
        {--dry-run : Preview the backfill without creating vouchers}';

    protected $description = 'Import legacy opening balances and historical treasury movements into VAS.';

    public function handle(LegacyAccountingBackfillService $backfillService): int
    {
        $summary = $backfillService->run(
            (int) $this->argument('business_id'),
            $this->dateOption('from'),
            $this->dateOption('to'),
            (int) $this->option('user'),
            (bool) $this->option('dry-run')
        );

        $this->table(
            ['Metric', 'Value'],
            [
                ['Business', $summary['business_id']],
                ['From date', $summary['from_date']],
                ['To date', $summary['to_date']],
                ['Dry run', $summary['dry_run'] ? 'yes' : 'no'],
                ['Batch id', $summary['batch_id'] ?: '-'],
                ['Opening vouchers', $summary['opening_balance_count']],
                ['Historical vouchers', $summary['historical_transaction_count']],
                ['Opening total', number_format((float) $summary['opening_balance_total'], 2)],
                ['Historical total', number_format((float) $summary['historical_transaction_total'], 2)],
            ]
        );

        return self::SUCCESS;
    }

    protected function dateOption(string $option): ?Carbon
    {
        $value = $this->option($option);

        return $value ? Carbon::parse((string) $value) : null;
    }
}
