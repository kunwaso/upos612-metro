<?php

declare(strict_types=1);

namespace ReadFileCacheMcp;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class FileDiscovery
{
    public function __construct(
        private readonly PathGuard $pathGuard,
    ) {
    }

    /**
     * Discover allowed file paths under the given directory (relative to workspace root).
     *
     * @return list<string> Absolute paths of allowed, readable text files.
     */
    public function discover(string $underDir = '', int $maxFiles = 10000): array
    {
        $root = $this->pathGuard->workspaceRoot();
        $start = $underDir === '' ? $root : $this->pathGuard->resolve($underDir);

        if (!is_dir($start)) {
            return [];
        }

        $paths = [];
        $directoryIterator = new RecursiveDirectoryIterator($start, RecursiveDirectoryIterator::SKIP_DOTS);
        $filteredIterator = new RecursiveCallbackFilterIterator(
            $directoryIterator,
            function ($current): bool {
                $path = str_replace('\\', '/', $current->getPathname());

                if ($current->isDir()) {
                    return $this->pathGuard->isDiscoverablePath($path);
                }

                return $this->pathGuard->isDiscoverablePath($path) && is_readable($path);
            }
        );
        $iterator = new RecursiveIteratorIterator(
            $filteredIterator,
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if (count($paths) >= $maxFiles) {
                break;
            }
            if (!$fileInfo->isFile()) {
                continue;
            }
            $path = $fileInfo->getPathname();
            $pathNormalized = str_replace('\\', '/', $path);
            $paths[] = $pathNormalized;
        }

        return $paths;
    }
}
