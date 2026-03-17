<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Index;

use DirectoryIterator;
use SemanticCodeSearchMcp\PathGuard;
use SemanticCodeSearchMcp\SemanticCodeSearchException;

final class FileDiscovery
{
    /**
     * @var string[]
     */
    private array $defaultRoots = [
        'app',
        'Modules',
        'routes',
        'resources',
        'mcp',
        'ai',
        '.cursor',
        'tests',
        'config',
        'src',
    ];

    /**
     * @var string[]
     */
    private array $defaultRootFiles = [
        'AGENTS.md',
        'composer.json',
        'composer.lock',
        'modules_statuses.json',
        'README.md',
    ];

    /**
     * @var string[]
     */
    private array $likelyBinaryExtensions = [
        '7z',
        'avi',
        'bmp',
        'class',
        'dll',
        'doc',
        'docx',
        'eot',
        'exe',
        'gif',
        'gz',
        'ico',
        'jar',
        'jpeg',
        'jpg',
        'mov',
        'mp3',
        'mp4',
        'otf',
        'pdf',
        'png',
        'rar',
        'tar',
        'ttf',
        'wav',
        'webm',
        'webp',
        'woff',
        'woff2',
        'xls',
        'xlsx',
        'zip',
    ];

    public function __construct(
        private readonly PathGuard $pathGuard,
        private readonly int $maxFileBytes,
    ) {
    }

    /**
     * @return array{
     *   scope: array{absolute_path: string, path: string, is_file: bool},
     *   files: array<int, array{absolute_path: string, path: string, size: int, mtime: int}>
     * }
     */
    public function discover(?string $workspacePath = null): array
    {
        $scope = $this->resolveScope($workspacePath);
        $files = [];

        if ($workspacePath === null) {
            foreach ($this->defaultRoots as $root) {
                $absolute = $this->pathGuard->resolve($root);
                if (!file_exists($absolute) || !$this->pathGuard->isPathAllowed($absolute)) {
                    continue;
                }

                $this->collectPath($absolute, $files);
            }

            foreach ($this->defaultRootFiles as $file) {
                $absolute = $this->pathGuard->resolve($file);
                if (!is_file($absolute) || !$this->pathGuard->isPathAllowed($absolute)) {
                    continue;
                }

                $this->collectFile($absolute, $files);
            }
        } else {
            $this->collectPath($scope['absolute_path'], $files);
        }

        ksort($files);

        return [
            'scope' => $scope,
            'files' => array_values($files),
        ];
    }

    /**
     * @param array{absolute_path: string, path: string, size: int, mtime: int} $file
     */
    public function readTextFile(array $file): string
    {
        if ($file['size'] > $this->maxFileBytes) {
            throw new SemanticCodeSearchException('FILE_TOO_LARGE', 'File exceeds the max indexable size.', $file['path']);
        }

        $contents = @file_get_contents($file['absolute_path']);
        if ($contents === false) {
            throw new SemanticCodeSearchException('READ_FAILED', 'Unable to read file for indexing.', $file['path']);
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        if (str_contains($contents, "\0") || preg_match('//u', $contents) !== 1) {
            throw new SemanticCodeSearchException('BINARY_FILE', 'File is binary and cannot be indexed.', $file['path']);
        }

        return $contents;
    }

    /**
     * @return array{absolute_path: string, path: string, is_file: bool}
     */
    public function resolveScope(?string $workspacePath): array
    {
        if ($workspacePath === null) {
            return [
                'absolute_path' => $this->pathGuard->workspaceRoot(),
                'path' => '.',
                'is_file' => false,
            ];
        }

        $resolved = $this->pathGuard->assertSearchPath($workspacePath);

        return [
            'absolute_path' => $resolved,
            'path' => $this->pathGuard->relativePath($resolved),
            'is_file' => is_file($resolved),
        ];
    }

    /**
     * @param array<string, array{absolute_path: string, path: string, size: int, mtime: int}> $files
     */
    private function collectPath(string $absolutePath, array &$files): void
    {
        if (is_file($absolutePath)) {
            $this->collectFile($absolutePath, $files);

            return;
        }

        foreach (new DirectoryIterator($absolutePath) as $item) {
            if ($item->isDot()) {
                continue;
            }

            $childPath = str_replace('\\', '/', $item->getPathname());
            if (!$this->pathGuard->isPathAllowed($childPath)) {
                continue;
            }

            if ($item->isDir()) {
                $this->collectPath($childPath, $files);
                continue;
            }

            $this->collectFile($childPath, $files);
        }
    }

    /**
     * @param array<string, array{absolute_path: string, path: string, size: int, mtime: int}> $files
     */
    private function collectFile(string $absolutePath, array &$files): void
    {
        $absolutePath = str_replace('\\', '/', $absolutePath);
        $relativePath = $this->pathGuard->relativePath($absolutePath);

        if (!$this->pathGuard->isPathAllowed($absolutePath)) {
            return;
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if ($extension !== '' && in_array($extension, $this->likelyBinaryExtensions, true)) {
            return;
        }

        $size = filesize($absolutePath);
        if (!is_int($size) || $size > $this->maxFileBytes) {
            return;
        }

        $mtime = filemtime($absolutePath);
        $files[$relativePath] = [
            'absolute_path' => $absolutePath,
            'path' => $relativePath,
            'size' => $size,
            'mtime' => is_int($mtime) ? $mtime : (int) $mtime,
        ];
    }
}
