<?php

declare(strict_types=1);

namespace ReadFileCacheMcp\Tests;

use PHPUnit\Framework\TestCase;
use ReadFileCacheMcp\FileCache;
use ReadFileCacheMcp\ReadFileException;
use ReadFileCacheMcp\Tests\Support\CreatesTempWorkspace;

final class FileCacheTest extends TestCase
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

    public function test_it_returns_a_cache_miss_then_a_cache_hit_and_normalizes_lines(): void
    {
        $file = $this->writeWorkspaceFile('docs/sample.txt', "one\r\ntwo\r\n");
        $cache = $this->makeCache();

        $first = $cache->get($file);
        $second = $cache->get($file);

        self::assertFalse($first['cache_hit']);
        self::assertTrue($second['cache_hit']);
        self::assertSame(['one', 'two'], $second['lines']);
    }

    public function test_it_refreshes_when_the_source_file_changes(): void
    {
        $file = $this->writeWorkspaceFile('docs/changing.txt', "old\n");
        $cache = $this->makeCache();

        $cache->get($file);

        file_put_contents($file, "new\ncontent\n");
        touch($file, time() + 2);

        $updated = $cache->get($file);

        self::assertFalse($updated['cache_hit']);
        self::assertSame(['new', 'content'], $updated['lines']);
    }

    public function test_it_refreshes_when_only_the_file_size_changes(): void
    {
        $file = $this->writeWorkspaceFile('docs/size-changing.txt', "old\n");
        $cache = $this->makeCache();

        $fixedMtime = 1_700_000_000;
        touch($file, $fixedMtime);
        $cache->get($file);

        file_put_contents($file, "new\ncontent\n");
        touch($file, $fixedMtime);

        $updated = $cache->get($file);

        self::assertFalse($updated['cache_hit']);
        self::assertSame(['new', 'content'], $updated['lines']);
    }

    public function test_it_rejects_binary_files(): void
    {
        $file = $this->writeWorkspaceFile('docs/binary.bin', "abc\0def");
        $cache = $this->makeCache();

        $this->expectException(ReadFileException::class);
        $this->expectExceptionMessage('Binary files are not supported.');

        $cache->get($file);
    }

    public function test_it_rejects_files_that_exceed_the_max_file_size(): void
    {
        $file = $this->writeWorkspaceFile('docs/oversized.txt', '12345');
        $cache = $this->makeCache(maxFileBytes: 4);

        try {
            $cache->get($file);
            self::fail('Expected ReadFileException was not thrown.');
        } catch (ReadFileException $exception) {
            self::assertSame('FILE_TOO_LARGE', $exception->errorCode());
            self::assertSame($file, $exception->path());
        }
    }

    public function test_it_evicts_the_least_recently_used_entry_when_max_file_count_is_exceeded(): void
    {
        $a = $this->writeWorkspaceFile('docs/a.txt', 'alpha');
        $b = $this->writeWorkspaceFile('docs/b.txt', 'bravo');
        $c = $this->writeWorkspaceFile('docs/c.txt', 'charlie');
        $cache = $this->makeCache(maxCacheFiles: 2, maxCacheBytes: 1024);

        $cache->get($a);
        $cache->get($b);
        self::assertTrue($cache->get($a)['cache_hit']);

        $cache->get($c);

        self::assertTrue($cache->get($a)['cache_hit']);
        self::assertFalse($cache->get($b)['cache_hit']);
    }

    public function test_it_evicts_the_least_recently_used_entry_when_max_cache_bytes_is_exceeded(): void
    {
        $a = $this->writeWorkspaceFile('docs/a.txt', '12345');
        $b = $this->writeWorkspaceFile('docs/b.txt', '67890');
        $c = $this->writeWorkspaceFile('docs/c.txt', 'abcde');
        $cache = $this->makeCache(maxCacheFiles: 10, maxCacheBytes: 10);

        $cache->get($a);
        $cache->get($b);
        self::assertTrue($cache->get($a)['cache_hit']);

        $cache->get($c);

        self::assertTrue($cache->get($a)['cache_hit']);
        self::assertFalse($cache->get($b)['cache_hit']);
    }

    private function makeCache(int $maxFileBytes = 1024, int $maxCacheFiles = 8, int $maxCacheBytes = 4096): FileCache
    {
        return new FileCache($maxFileBytes, $maxCacheFiles, $maxCacheBytes);
    }
}
