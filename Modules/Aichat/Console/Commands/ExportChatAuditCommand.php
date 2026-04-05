<?php

namespace Modules\Aichat\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Aichat\Entities\ChatAuditLog;

class ExportChatAuditCommand extends Command
{
    protected $signature = 'aichat:audit-export
                            {--business_id= : Scope to a single business (omit for all — superadmin only)}
                            {--action= : Filter to a specific audit action string}
                            {--since= : Relative or absolute date, e.g. "-7 days", "2026-04-01"}
                            {--limit= : Maximum number of rows to export}
                            {--format=json : Output format: json or csv}
                            {--output= : Write to file path instead of stdout}';

    protected $description = 'Export aichat_chat_audit_logs for developer analysis. Output is JSON or CSV.';

    public function handle(): int
    {
        $businessId = $this->option('business_id') !== null ? (int) $this->option('business_id') : null;
        $action = $this->option('action') ? trim((string) $this->option('action')) : null;
        $since = $this->option('since') ? trim((string) $this->option('since')) : null;
        $limit = $this->option('limit') ? max(1, (int) $this->option('limit')) : 5000;
        $format = strtolower(trim((string) ($this->option('format') ?: 'json')));
        $outputPath = $this->option('output') ? trim((string) $this->option('output')) : null;

        if (! in_array($format, ['json', 'csv'], true)) {
            $this->error('--format must be "json" or "csv".');

            return 1;
        }

        $query = ChatAuditLog::query()
            ->select(['id', 'business_id', 'user_id', 'conversation_id', 'action', 'provider', 'model', 'metadata', 'created_at'])
            ->orderBy('created_at', 'desc');

        if ($businessId !== null) {
            $query->where('business_id', $businessId);
        }

        if ($action !== null) {
            $query->where('action', $action);
        }

        if ($since !== null) {
            try {
                $sinceDate = Carbon::parse($since);
                $query->where('created_at', '>=', $sinceDate);
            } catch (\Throwable $exception) {
                $this->error('Could not parse --since value: ' . $since);

                return 1;
            }
        }

        $query->limit($limit);

        $rows = $query->get()->map(function (ChatAuditLog $log) {
            return [
                'id' => (int) $log->id,
                'business_id' => (int) $log->business_id,
                'user_id' => $log->user_id !== null ? (int) $log->user_id : null,
                'conversation_id' => $log->conversation_id,
                'action' => $log->action,
                'provider' => $log->provider,
                'model' => $log->model,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at ? $log->created_at->toIso8601String() : null,
            ];
        })->all();

        if (empty($rows)) {
            $this->line('No rows matched the given filters.');

            return 0;
        }

        $output = $format === 'csv' ? $this->toCsv($rows) : json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($outputPath !== null) {
            file_put_contents($outputPath, $output);
            $this->info('Written ' . count($rows) . ' row(s) to ' . $outputPath);
        } else {
            $this->output->write($output);
        }

        return 0;
    }

    protected function toCsv(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $handle = fopen('php://temp', 'r+b');

        fputcsv($handle, array_keys(reset($rows)));

        foreach ($rows as $row) {
            $flat = array_map(function ($value) {
                if (is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                return $value;
            }, $row);
            fputcsv($handle, $flat);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }
}
