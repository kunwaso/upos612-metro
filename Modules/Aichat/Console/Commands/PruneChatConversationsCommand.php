<?php

namespace Modules\Aichat\Console\Commands;

use Illuminate\Console\Command;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Utils\ChatUtil;

class PruneChatConversationsCommand extends Command
{
    protected $signature = 'aichat:chat-prune {--business_id=} {--dry-run}';

    protected $description = 'Prune Aichat chat conversations older than configured retention policy.';

    protected ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        parent::__construct();
        $this->chatUtil = $chatUtil;
    }

    public function handle()
    {
        $businessOption = $this->option('business_id');
        $dryRun = (bool) $this->option('dry-run');

        $query = ChatConversation::query()->select('business_id')->distinct();
        if (! empty($businessOption)) {
            $query->where('business_id', (int) $businessOption);
        }

        $businessIds = $query->pluck('business_id')->map(function ($id) {
            return (int) $id;
        })->filter()->values()->all();

        if (empty($businessIds)) {
            $this->info('No chat conversations found to prune.');

            return 0;
        }

        $totalDeleted = 0;
        foreach ($businessIds as $businessId) {
            $retentionDays = $this->chatUtil->getEffectiveRetentionDays($businessId);
            $cutoff = now()->subDays($retentionDays);

            $businessQuery = ChatConversation::forBusiness($businessId)
                ->where('updated_at', '<', $cutoff);

            $count = (int) $businessQuery->count();
            if ($count <= 0) {
                $this->line("Business {$businessId}: nothing to prune.");
                continue;
            }

            if ($dryRun) {
                $this->line("Business {$businessId}: would delete {$count} conversation(s) older than {$cutoff->toDateTimeString()}.");
                continue;
            }

            $deleted = (int) $businessQuery->delete();
            $totalDeleted += $deleted;
            $this->line("Business {$businessId}: deleted {$deleted} conversation(s).");
        }

        if ($dryRun) {
            $this->info('Dry run completed.');
        } else {
            $this->info("Prune completed. Deleted {$totalDeleted} conversation(s).");
        }

        return 0;
    }
}


