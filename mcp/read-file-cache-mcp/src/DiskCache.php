<?php

declare(strict_types=1);

namespace ReadFileCacheMcp;

use PDO;
use PDOException;
use Throwable;

final class DiskCache
{
    private const DEFAULT_BUSY_TIMEOUT_SECONDS = 1;

    private const DEFAULT_BUSY_TIMEOUT_MS = 1000;

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
        $databaseExists = is_file($dbPath);
        $this->pdo = new PDO('sqlite:'.$dbPath, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => self::DEFAULT_BUSY_TIMEOUT_SECONDS,
        ]);
        $this->configureConnection();
        $this->ensureSchema($databaseExists);
    }

    private function configureConnection(): void
    {
        $this->bestEffortExec(sprintf('PRAGMA busy_timeout = %d', self::DEFAULT_BUSY_TIMEOUT_MS));
        $this->bestEffortExec('PRAGMA synchronous = NORMAL');
        $this->bestEffortExec('PRAGMA journal_mode = WAL');
    }

    private function ensureSchema(bool $databaseExists): void
    {
        if ($databaseExists && $this->hasCacheTable()) {
            return;
        }

        try {
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
        } catch (PDOException $exception) {
            if ($this->isDatabaseLockException($exception) && ($databaseExists || $this->hasCacheTable())) {
                return;
            }

            throw $exception;
        }
    }

    private function hasCacheTable(): bool
    {
        try {
            $result = $this->pdo->query(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'cache' LIMIT 1"
            );

            return $result !== false && $result->fetchColumn() !== false;
        } catch (PDOException $exception) {
            if ($this->isDatabaseLockException($exception)) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * @param array{mtime: int, size: int} $stat
     */
    public function get(string $absolutePath, array $stat): ?string
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT content FROM cache WHERE path = ? AND mtime = ? AND size = ?'
            );
            $stmt->execute([$absolutePath, $stat['mtime'], $stat['size']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            if ($this->isDatabaseLockException($exception)) {
                return null;
            }

            throw $exception;
        }
        if ($row === false) {
            return null;
        }

        $this->touchLastAccess($absolutePath);

        return $row['content'];
    }

    public function set(string $absolutePath, int $mtime, int $size, string $content): void
    {
        if (strlen($content) > $this->maxFileBytes) {
            return;
        }

        $now = microtime(true);
        try {
            $this->pdo->prepare(
                'INSERT OR REPLACE INTO cache (path, mtime, size, content, last_access) VALUES (?, ?, ?, ?, ?)'
            )->execute([$absolutePath, $mtime, $size, $content, $now]);

            $this->evictIfNeeded();
        } catch (PDOException $exception) {
            if ($this->isDatabaseLockException($exception)) {
                return;
            }

            throw $exception;
        }
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

    private function touchLastAccess(string $absolutePath): void
    {
        try {
            $this->pdo->prepare('UPDATE cache SET last_access = ? WHERE path = ?')
                ->execute([microtime(true), $absolutePath]);
        } catch (PDOException $exception) {
            if (!$this->isDatabaseLockException($exception)) {
                throw $exception;
            }
        }
    }

    private function bestEffortExec(string $sql): void
    {
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $exception) {
            if (!$this->isDatabaseLockException($exception)) {
                throw $exception;
            }
        }
    }

    private function isDatabaseLockException(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'database is locked')
            || str_contains($message, 'database table is locked');
    }
}
