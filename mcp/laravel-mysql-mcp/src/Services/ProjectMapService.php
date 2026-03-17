<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Services;

use LaravelMysqlMcp\Safety\PathGuard;

final class ProjectMapService
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly PathGuard $pathGuard,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildMap(bool $includeFiles = false, int $maxFiles = 200): array
    {
        $composer = $this->readJsonFile($this->projectRoot.'/composer.json');
        $moduleStatuses = $this->readJsonFile($this->projectRoot.'/modules_statuses.json');

        $moduleDirs = [];
        $modulesPath = $this->projectRoot.'/Modules';
        if (is_dir($modulesPath)) {
            $items = scandir($modulesPath) ?: [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                if (is_dir($modulesPath.'/'.$item)) {
                    $moduleDirs[] = $item;
                }
            }
        }
        sort($moduleDirs);

        $files = [];
        if ($includeFiles) {
            $targets = [
                'app/Http/Controllers',
                'app/Utils',
                'app/Http/Middleware',
                'routes',
            ];

            foreach ($targets as $target) {
                $files = array_merge($files, $this->collectPhpFiles($this->projectRoot.'/'.$target, $maxFiles - count($files)));
                if (count($files) >= $maxFiles) {
                    break;
                }
            }
        }

        return [
            'framework' => [
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
            ],
            'root' => $this->projectRoot,
            'directories' => [
                'app' => is_dir($this->projectRoot.'/app'),
                'routes' => is_dir($this->projectRoot.'/routes'),
                'config' => is_dir($this->projectRoot.'/config'),
                'resources_views' => is_dir($this->projectRoot.'/resources/views'),
                'modules' => is_dir($modulesPath),
            ],
            'counts' => [
                'controllers' => $this->countPhpFiles($this->projectRoot.'/app/Http/Controllers'),
                'utils' => $this->countPhpFiles($this->projectRoot.'/app/Utils'),
                'middleware' => $this->countPhpFiles($this->projectRoot.'/app/Http/Middleware'),
                'models' => $this->countPhpFiles($this->projectRoot.'/app', maxDepth: 1),
                'routes' => $this->countPhpFiles($this->projectRoot.'/routes'),
            ],
            'composer' => [
                'name' => $composer['name'] ?? null,
                'php_requirement' => $composer['require']['php'] ?? null,
                'laravel_requirement' => $composer['require']['laravel/framework'] ?? null,
                'nwidart_modules' => $composer['require']['nwidart/laravel-modules'] ?? null,
            ],
            'modules' => [
                'directories_present' => $moduleDirs,
                'statuses_enabled' => array_keys(array_filter(is_array($moduleStatuses) ? $moduleStatuses : [])),
            ],
            'conventions' => [
                'models_path' => 'app/',
                'utils_path' => 'app/Utils/',
                'core_routes' => ['routes/web.php', 'routes/api.php'],
            ],
            'files' => $files,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return string[]
     */
    private function collectPhpFiles(string $path, int $limit): array
    {
        if (!is_dir($path) || $limit <= 0) {
            return [];
        }

        $result = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $absolute = str_replace('\\', '/', $file->getPathname());
            if (!$this->pathGuard->isAllowed($absolute)) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($this->projectRoot))), '/');
            $result[] = $relative;

            if (count($result) >= $limit) {
                break;
            }
        }

        sort($result);

        return $result;
    }

    private function countPhpFiles(string $path, int $maxDepth = PHP_INT_MAX): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $depth = $iterator->getDepth();
            if ($depth > $maxDepth) {
                continue;
            }

            if (strtolower($file->getExtension()) === 'php') {
                $count++;
            }
        }

        return $count;
    }
}