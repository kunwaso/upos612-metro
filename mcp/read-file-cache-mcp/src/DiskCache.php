<?php

declare(strict_types=1);

namespace ReadFileCacheMcp;

use PDO;

final class DiskCache
{
    private PDO $pdo;

    private int $maxFiles;

    private int $maxBytes;

    private int $maxFileBytes;

    public function __construct(string $cacheRoot, int $maxFileBytes, int $maxFiles, int $maxBytes)
    {
        $this->maxFileBytes = max(1, $maxFileBytes);
        $this->maxFiles = max(1, $maxFiles);
        $this->maxBytes = max(1, $maxBytes);

        if (!is_dir($cacheRoot)) {
            if (!@mkdir($cacheRoot, 0755, true)) {
                throw new \RuntimeException(sprintf('Cannot create cache directory: %s', $cacheRoot));
            }
        }

        $dbPath = $cacheRoot.\DIRECTORY_SEPARATOR.'read-file-cache.sqlite';
        $this->pdo = new PDO('sqlite:'.$dbPath, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->initSchema();
    }

    private function initSchema(): void
    {
        $this->pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS cache (
                path TEXT PRIMARY KEY,
                mtime INTEGER NOT NULL,
                size INTEGER NOT NULL,
                content TEXT NOT NULL,
                last_access REAL NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_cache_last_access ON cache (last_access);
SQL);
    }

    /**
     * @param array{mtime: int, size: int} $stat
     */
    public function get(string $absolutePath, array $stat): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT content FROM cache WHERE path = ? AND mtime = ? AND size = ?'
        );
        $stmt->execute([$absolutePath, $stat['mtime'], $stat['size']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $this->pdo->prepare('UPDATE cache SET last_access = ? WHERE path = ?')
            ->execute([microtime(true), $absolutePath]);

        return $row['content'];
    }

    public function set(string $absolutePath, int $mtime, int $size, string $content): void
    {
        if (strlen($content) > $this->maxFileBytes) {
            return;
        }

        $now = microtime(true);
        $this->pdo->prepare(
            'INSERT OR REPLACE INTO cache (path, mtime, size, content, last_access) VALUES (?, ?, ?, ?, ?)'
        )->execute([$absolutePath, $mtime, $size, $content, $now]);

        $this->evictIfNeeded();
    }

    private function evictIfNeeded(): void
    {
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM cache')->fetchColumn();
        $totalBytes = (int) $this->pdo->query('SELECT COALESCE(SUM(LENGTH(content)), 0) FROM cache')->fetchColumn();

        while ($count > $this->maxFiles || $totalBytes > $this->maxBytes) {
            $row = $this->pdo->query(
                'SELECT path, LENGTH(content) AS len FROM cache ORDER BY last_access ASC LIMIT 1'
            )->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                break;
            }
            $this->pdo->prepare('DELETE FROM cache WHERE path = ?')->execute([$row['path']]);
            $count--;
            $totalBytes -= (int) $row['len'];
        }
    }

    public function pathCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM cache')->fetchColumn();
    }

    public function totalBytes(): int
    {
        return (int) $this->pdo->query('SELECT COALESCE(SUM(LENGTH(content)), 0) FROM cache')->fetchColumn();
    }
}
