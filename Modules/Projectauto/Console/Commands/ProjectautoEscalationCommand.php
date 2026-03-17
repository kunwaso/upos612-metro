<?php

namespace Modules\Projectauto\Console\Commands;

use App\System;
use Illuminate\Console\Command;
use Modules\Projectauto\Entities\ProjectautoPendingTask;
use Modules\Projectauto\Utils\ProjectautoUtil;

class ProjectautoEscalationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projectauto:escalate {--action=} {--chunk=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Escalate or auto-reject expired Projectauto pending tasks.';

    protected ProjectautoUtil $projectautoUtil;

    public function __construct(ProjectautoUtil $projectautoUtil)
    {
        parent::__construct();

        $this->projectautoUtil = $projectautoUtil;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (empty(System::getProperty('projectauto_version'))) {
            $this->line('Projectauto is not installed. Skipping.');

            return self::SUCCESS;
        }

        $action = (string) ($this->option('action') ?: config('projectauto.task.escalation_action', 'none'));
        $chunk = (int) ($this->option('chunk') ?: config('projectauto.task.escalation_chunk', 100));

        if ($this->option('dry-run')) {
            $count = ProjectautoPendingTask::query()
                ->where('status', ProjectautoPendingTask::STATUS_PENDING)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->count();

            $this->info("Dry run complete. Expired pending tasks: {$count}");

            return self::SUCCESS;
        }

        $processed = $this->projectautoUtil->escalateExpiredTasks($action, $chunk);
        $this->info("Processed expired tasks: {$processed}");

        return self::SUCCESS;
    }
}
