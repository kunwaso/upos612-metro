<?php

declare(strict_types=1);

namespace ReadFileCacheMcp\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use ReadFileCacheMcp\DiskCache;
use ReadFileCacheMcp\Tests\Support\CreatesTempWorkspace;

final class DiskCacheTest extends TestCase
{
    use CreatesTempWorkspace;

    protected function setUp(): void
    {
        $this->createWorkspace();
    }

    protected function tearDown(): void
    {
        $this->removeWorkspace();
    }

    public function test_it_returns_cached_content_even_when_last_access_touch_is_locked(): void
    {
        $cacheRoot = $this->workspacePath('.cache/read-file-cache-mcp');
        $cache = new DiskCache($cacheRoot, 1024, 16, 4096);
        $cache->set('/virtual/path.txt', 1, 1, 'alpha');

        $lock = $this->openLockingConnection($cacheRoot);

        try {
            $lock->exec('BEGIN IMMEDIATE');

            self::assertSame(
                'alpha',
                $cache->get('/virtual/path.txt', ['mtime' => 1, 'size' => 1])
            );
        } finally {
            $lock->exec('ROLLBACK');
        }
    }

    public function test_it_skips_disk_writes_when_database_is_locked(): void
    {
        $cacheRoot = $this->workspacePath('.cache/read-file-cache-mcp');
        $writer = new DiskCache($cacheRoot, 1024, 16, 4096);
        $writer->set('/virtual/original.txt', 1, 1, 'alpha');

        $lock = $this->openLockingConnection($cacheRoot);

        try {
            $lock->exec('BEGIN IMMEDIATE');
            $writer->set('/virtual/blocked.txt', 1, 1, 'beta');
        } finally {
            $lock->exec('ROLLBACK');
        }

        $reader = new DiskCache($cacheRoot, 1024, 16, 4096);

        self::assertNull($reader->get('/virtual/blocked.txt', ['mtime' => 1, 'size' => 1]));
        self::assertSame('alpha', $reader->get('/virtual/original.txt', ['mtime' => 1, 'size' => 1]));
    }

    private function openLockingConnection(string $cacheRoot): PDO
    {
        $dbPath = $cacheRoot.\DIRECTORY_SEPARATOR.'read-file-cache.sqlite';
        $pdo = new PDO('sqlite:'.$dbPath, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 1,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL');

        return $pdo;
    }
}
