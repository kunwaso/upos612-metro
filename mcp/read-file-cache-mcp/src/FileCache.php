<?php

declare(strict_types=1);

namespace ReadFileCacheMcp;

final class FileCache
{
    /**
     * @var array<string, array{
     *     lines: array<int, string>,
     *     mtime: int,
     *     size: int,
     *     cached_bytes: int,
     *     last_access: float
     * }>
     */
    private array $memoryCache = [];

    private int $maxFileBytes;

    private int $maxCacheFiles;

    private int $maxCacheBytes;

    private int $totalCachedBytes = 0;

    public function __construct(
        int $maxFileBytes,
        int $maxCacheFiles,
        int $maxCacheBytes,
        private readonly ?DiskCache $diskCache = null,
    ) {
        $this->maxFileBytes = max(1, $maxFileBytes);
        $this->maxCacheFiles = max(1, $maxCacheFiles);
        $this->maxCacheBytes = max(1, $maxCacheBytes);
    }

    /**
     * @return array{
     *     lines: array<int, string>,
     *     mtime: int,
     *     size: int,
     *     cached_bytes: int,
     *     last_access: float,
     *     cache_hit: bool
     * }
     */
    public function get(string $absolutePath): array
    {
        $stat = $this->statFile($absolutePath);
        if ($stat['size'] > $this->maxFileBytes) {
            throw new ReadFileException('FILE_TOO_LARGE', 'File exceeds the max file byte limit.', $absolutePath);
        }

        $memoryEntry = $this->memoryCache[$absolutePath] ?? null;
        if ($memoryEntry !== null && $this->matchesStat($memoryEntry, $stat)) {
            return $this->touchEntry($absolutePath, true);
        }

        if ($this->diskCache !== null) {
            $content = $this->diskCache->get($absolutePath, $stat);
            if ($content !== null) {
                $entry = $this->buildEntryFromNormalized($content, $stat['mtime'], $stat['size']);
                $this->storeEntry($absolutePath, $entry);
                return $entry + ['cache_hit' => true];
            }
        }

        $raw = @file_get_contents($absolutePath);
        if ($raw === false) {
            throw new ReadFileException('READ_FAILED', 'Unable to read file.', $absolutePath);
        }

        if (str_contains($raw, "\0")) {
            throw new ReadFileException('BINARY_FILE', 'Binary files are not supported.', $absolutePath);
        }

        $entry = $this->buildEntry($raw, $stat['mtime'], $stat['size']);
        $this->storeEntry($absolutePath, $entry);

        if ($this->diskCache !== null) {
            $normalized = $this->normalizeLineEndings($raw);
            if (strlen($normalized) <= $this->maxFileBytes) {
                $this->diskCache->set($absolutePath, $stat['mtime'], $stat['size'], $normalized);
            }
        }

        return $entry + ['cache_hit' => false];
    }

    /**
     * @return array{
     *     lines: array<int, string>,
     *     mtime: int,
     *     size: int,
     *     cached_bytes: int,
     *     last_access: float
     * }
     */
    private function buildEntry(string $raw, int $mtime, int $size): array
    {
        $normalizedText = $this->normalizeLineEndings($raw);
        return $this->buildEntryFromNormalized($normalizedText, $mtime, $size);
    }

    /**
     * @return array{
     *     lines: array<int, string>,
     *     mtime: int,
     *     size: int,
     *     cached_bytes: int,
     *     last_access: float
     * }
     */
    private function buildEntryFromNormalized(string $normalizedText, int $mtime, int $size): array
    {
        $lines = $this->splitLines($normalizedText);
        return [
            'lines' => $lines,
            'mtime' => $mtime,
            'size' => $size,
            'cached_bytes' => strlen($normalizedText),
            'last_access' => microtime(true),
        ];
    }

    /**
     * @return array{mtime: int, size: int}
     */
    private function statFile(string $absolutePath): array
    {
        if (!file_exists($absolutePath)) {
            throw new ReadFileException('FILE_NOT_FOUND', 'File not found.', $absolutePath);
        }

        if (is_dir($absolutePath) || !is_file($absolutePath)) {
            throw new ReadFileException('NOT_A_FILE', 'Path does not point to a regular file.', $absolutePath);
        }

        $mtime = filemtime($absolutePath);
        $size = filesize($absolutePath);
        if ($mtime === false || $size === false) {
            throw new ReadFileException('READ_FAILED', 'Unable to inspect file.', $absolutePath);
        }

        return [
            'mtime' => (int) $mtime,
            'size' => (int) $size,
        ];
    }

    /**
     * @param array{mtime: int, size: int} $stat
     * @param array{
     *     lines: array<int, string>,
     *     mtime: int,
     *     size: int,
     *     cached_bytes: int,
     *     last_access: float
     * } $entry
     */
    private function matchesStat(array $entry, array $stat): bool
    {
        return $entry['mtime'] === $stat['mtime'] && $entry['size'] === $stat['size'];
    }

    /**
     * @return array{
     *     lines: array<int, string>,
     *     mtime: int,
     *     size: int,
     *     cached_bytes: int,
     *     last_access: float,
     *     cache_hit: bool
     * }
     */
    private function touchEntry(string $absolutePath, bool $cacheHit): array
    {
        $entry = $this->memoryCache[$absolutePath];
        $entry['last_access'] = microtime(true);
        $this->memoryCache[$absolutePath] = $entry;

        return $entry + ['cache_hit' => $cacheHit];
    }

    /**
     * @param array{
     *     lines: array<int, string>,
     *     mtime: int,
     *     size: int,
     *     cached_bytes: int,
     *     last_access: float
     * } $entry
     */
    private function storeEntry(string $absolutePath, array $entry): void
    {
        $existing = $this->memoryCache[$absolutePath] ?? null;
        if ($existing !== null) {
            $this->totalCachedBytes -= $existing['cached_bytes'];
        }

        $this->memoryCache[$absolutePath] = $entry;
        $this->totalCachedBytes += $entry['cached_bytes'];
        $this->evictIfNeeded();
    }

    /**
     * @return array<int, string>
     */
    private function splitLines(string $content): array
    {
        if ($content === '') {
            return [];
        }

        if (str_ends_with($content, "\n")) {
            $content = substr($content, 0, -1);
        }

        if ($content === '') {
            return [''];
        }

        return explode("\n", $content);
    }

    private function normalizeLineEndings(string $content): string
    {
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    private function evictIfNeeded(): void
    {
        while (count($this->memoryCache) > $this->maxCacheFiles || $this->totalCachedBytes > $this->maxCacheBytes) {
            $lruPath = $this->leastRecentlyUsedPath();
            if ($lruPath === null) {
                return;
            }

            $this->removeEntry($lruPath);
        }
    }

    private function leastRecentlyUsedPath(): ?string
    {
        $lruPath = null;
        $lruAccess = null;

        foreach ($this->memoryCache as $path => $entry) {
            if ($lruAccess === null || $entry['last_access'] < $lruAccess) {
                $lruAccess = $entry['last_access'];
                $lruPath = $path;
            }
        }

        return $lruPath;
    }

    private function removeEntry(string $absolutePath): void
    {
        if (!isset($this->memoryCache[$absolutePath])) {
            return;
        }

        $this->totalCachedBytes -= $this->memoryCache[$absolutePath]['cached_bytes'];
        unset($this->memoryCache[$absolutePath]);
    }
}
