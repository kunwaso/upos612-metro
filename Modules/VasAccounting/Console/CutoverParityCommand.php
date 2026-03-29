<?php

namespace Modules\VasAccounting\Console;

use Illuminate\Console\Command;
use Modules\VasAccounting\Services\CutoverParityService;

class CutoverParityCommand extends Command
{
    protected $signature = 'vas:cutover:parity
        {business_id : Business id}
        {--period= : Optional accounting month token (YYYY-MM)}
        {--branch=* : Optional branch ids}
        {--format=screen : screen or csv}';

    protected $description = 'Compare legacy accounting parity against VAS by domain and branch.';

    public function handle(CutoverParityService $parityService): int
    {
        $report = $parityService->build(
            (int) $this->argument('business_id'),
            $this->option('period') ? (string) $this->option('period') : null,
            array_map('intval', (array) $this->option('branch'))
        );

        if ((string) $this->option('format') === 'csv') {
            $this->writeCsv($report);

            return self::SUCCESS;
        }

        $this->info('Parity window: ' . $report['period']['label'] . ' (' . $report['period']['start_date'] . ' to ' . $report['period']['end_date'] . ')');
        $this->table(
            ['Section', 'Legacy', 'VAS', 'Delta', 'Status'],
            collect($report['sections'])->map(fn (array $section) => [
                $section['label'],
                number_format((float) $section['legacy_value'], 2),
                number_format((float) $section['vas_value'], 2),
                number_format((float) $section['delta'], 2),
                strtoupper((string) $section['status']),
            ])->all()
        );

        if (! empty($report['branches'])) {
            $this->newLine();
            $this->table(
                ['Branch', 'Treasury delta', 'AR delta', 'AP delta', 'Inventory delta', 'Status'],
                collect($report['branches'])->map(fn (array $row) => [
                    $row['branch_name'],
                    number_format((float) $row['treasury_delta'], 2),
                    number_format((float) $row['receivables_delta'], 2),
                    number_format((float) $row['payables_delta'], 2),
                    number_format((float) $row['inventory_delta'], 2),
                    strtoupper((string) $row['overall_status']),
                ])->all()
            );
        }

        return self::SUCCESS;
    }

    protected function writeCsv(array $report): void
    {
        $stream = fopen('php://output', 'w');
        fputcsv($stream, ['period', $report['period']['token'], $report['period']['start_date'], $report['period']['end_date']]);
        fputcsv($stream, ['section', 'legacy_value', 'vas_value', 'delta', 'status']);

        foreach ($report['sections'] as $section) {
            fputcsv($stream, [
                $section['key'],
                $section['legacy_value'],
                $section['vas_value'],
                $section['delta'],
                $section['status'],
            ]);
        }

        if (! empty($report['branches'])) {
            fputcsv($stream, []);
            fputcsv($stream, ['branch', 'treasury_delta', 'receivables_delta', 'payables_delta', 'inventory_delta', 'status']);

            foreach ($report['branches'] as $row) {
                fputcsv($stream, [
                    $row['branch_name'],
                    $row['treasury_delta'],
                    $row['receivables_delta'],
                    $row['payables_delta'],
                    $row['inventory_delta'],
                    $row['overall_status'],
                ]);
            }
        }

        fclose($stream);
    }
}
