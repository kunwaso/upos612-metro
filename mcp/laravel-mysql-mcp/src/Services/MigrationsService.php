<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Services;

use Illuminate\Support\Facades\DB;

final class MigrationsService
{
    public function __construct(private readonly string $projectRoot)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listMigrationFiles(string $pathScope = 'core'): array
    {
        $files = [];

        $corePath = $this->projectRoot.'/database/migrations';
        $files = array_merge($files, $this->scanMigrationPath($corePath, 'core', null));

        if ($pathScope === 'all') {
            $modulesRoot = $this->projectRoot.'/Modules';
            if (is_dir($modulesRoot)) {
                $modules = scandir($modulesRoot) ?: [];
                foreach ($modules as $module) {
                    if ($module === '.' || $module === '..') {
                        continue;
                    }

                    $path = $modulesRoot.'/'.$module.'/Database/Migrations';
                    if (!is_dir($path)) {
                        continue;
                    }

                    $files = array_merge($files, $this->scanMigrationPath($path, 'module', $module));
                }
            }
        }

        usort($files, static fn (array $a, array $b): int => strcmp((string) $a['migration'], (string) $b['migration']));

        return $files;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function status(bool $pendingOnly = false, ?string $database = null, string $pathScope = 'core'): array
    {
        $files = $this->listMigrationFiles($pathScope);
        $ran = $this->ranMigrations($database);

        $rows = [];
        foreach ($files as $file) {
            $name = $file['migration'];
            $batch = $ran[$name]['batch'] ?? null;
            $isRan = $batch !== null;

            if ($pendingOnly && $isRan) {
                continue;
            }

            $rows[] = [
                'migration' => $name,
                'class' => $file['class'],
                'path' => $file['path'],
                'scope' => $file['scope'],
                'module' => $file['module'],
                'ran' => $isRan,
                'batch' => $batch,
                'status' => $isRan ? 'ran' : 'pending',
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findMigration(string $identifier, string $pathScope = 'all'): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        foreach ($this->listMigrationFiles($pathScope) as $file) {
            if ($file['migration'] === $identifier || basename((string) $file['path']) === $identifier || $file['class'] === $identifier) {
                return $file;
            }

            $basename = pathinfo((string) $file['path'], PATHINFO_FILENAME);
            if ($basename === $identifier) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @param string|null $database
     *
     * @return array<string, array{batch: int}>
     */
    private function ranMigrations(?string $database): array
    {
        $connection = $database !== null && $database !== '' ? DB::connection($database) : DB::connection();

        if (!$connection->getSchemaBuilder()->hasTable('migrations')) {
            return [];
        }

        $rows = $connection->table('migrations')->select(['migration', 'batch'])->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row->migration] = [
                'batch' => (int) $row->batch,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scanMigrationPath(string $path, string $scope, ?string $module): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $files = glob($path.'/*.php') ?: [];

        $rows = [];
        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $migrationName = pathinfo($filename, PATHINFO_FILENAME);
            $class = $this->extractClassName($filePath);
            $timestamp = preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})/', $migrationName, $matches) === 1 ? $matches[1] : null;

            $rows[] = [
                'migration' => $migrationName,
                'class' => $class,
                'path' => str_replace('\\', '/', $filePath),
                'filename' => $filename,
                'timestamp' => $timestamp,
                'scope' => $scope,
                'module' => $module,
            ];
        }

        return $rows;
    }

    private function extractClassName(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        if (preg_match('/class\s+([A-Za-z0-9_]+)/', $content, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}