<?php

namespace Modules\Aichat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Modules\Aichat\Entities\ChatMemory;

class EncryptChatMemoryCommand extends Command
{
    protected $signature = 'aichat:encrypt-chat-memory {--business_id=} {--dry-run}';

    protected $description = 'Encrypt plaintext rows in aichat_chat_memory.memory_value';

    public function handle()
    {
        if (! DB::getSchemaBuilder()->hasTable('aichat_chat_memory')) {
            $this->warn('aichat_chat_memory table does not exist.');

            return 0;
        }

        $businessOption = $this->option('business_id');
        $dryRun = (bool) $this->option('dry-run');
        $businessIdFilter = is_null($businessOption) || $businessOption === '' ? null : (int) $businessOption;

        $query = DB::table('aichat_chat_memory')
            ->select(['id', 'business_id', 'memory_value'])
            ->orderBy('id');

        if (! is_null($businessIdFilter) && $businessIdFilter > 0) {
            $query->where('business_id', $businessIdFilter);
        }

        $scanned = 0;
        $alreadyEncrypted = 0;
        $rewritten = 0;
        $missingModels = 0;

        $query->chunkById(200, function ($rows) use (&$scanned, &$alreadyEncrypted, &$rewritten, &$missingModels, $dryRun) {
            foreach ($rows as $row) {
                $scanned++;
                $rawValue = (string) ($row->memory_value ?? '');

                if ($rawValue === '') {
                    $alreadyEncrypted++;
                    continue;
                }

                if ($this->looksEncrypted($rawValue)) {
                    $alreadyEncrypted++;
                    continue;
                }

                $rewritten++;
                if ($dryRun) {
                    continue;
                }

                $memory = ChatMemory::query()->find((int) $row->id);
                if (! $memory) {
                    $missingModels++;
                    continue;
                }

                $memory->memory_value = $rawValue;
                $memory->save();
            }
        }, 'id');

        $this->info('Scan complete.');
        $this->line('Rows scanned: ' . $scanned);
        $this->line('Already encrypted/empty: ' . $alreadyEncrypted);
        $this->line(($dryRun ? 'Would rewrite: ' : 'Rewritten: ') . $rewritten);
        if (! $dryRun && $missingModels > 0) {
            $this->warn('Rows skipped because model was not found: ' . $missingModels);
        }

        return 0;
    }

    protected function looksEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException $exception) {
            return false;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}

