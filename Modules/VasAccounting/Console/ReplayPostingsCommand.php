<?php

namespace Modules\VasAccounting\Console;

use Illuminate\Console\Command;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Services\VasPostingService;

class ReplayPostingsCommand extends Command
{
    protected $signature = 'vasaccounting:replay-postings {business_id? : Optional business id} {--source_type=} {--source_id=}';

    protected $description = 'Replay unresolved VAS posting failures.';

    public function handle(VasPostingService $postingService): int
    {
        $query = VasPostingFailure::query()->whereNull('resolved_at');

        if ($businessId = $this->argument('business_id')) {
            $query->where('business_id', (int) $businessId);
        }

        if ($sourceType = $this->option('source_type')) {
            $query->where('source_type', (string) $sourceType);
        }

        if ($sourceId = $this->option('source_id')) {
            $query->where('source_id', (int) $sourceId);
        }

        $failures = $query->get();
        foreach ($failures as $failure) {
            $postingService->replayFailure($failure);
            $this->info("Replayed posting failure [{$failure->id}] for {$failure->source_type}:{$failure->source_id}.");
        }

        return self::SUCCESS;
    }
}
