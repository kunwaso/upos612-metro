<?php

declare(strict_types=1);

namespace ReadFileCacheMcp\Tests;

use PHPUnit\Framework\TestCase;
use ReadFileCacheMcp\FileDiscovery;
use ReadFileCacheMcp\PathGuard;
use ReadFileCacheMcp\Tests\Support\CreatesTempWorkspace;

final class FileDiscoveryTest extends TestCase
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

    public function test_it_discovers_only_allowed_source_files_and_skips_cache_trees(): void
    {
        $this->writeWorkspaceFile('app/Services/Example.php', "<?php\n");
        $this->writeWorkspaceFile('docs/readme.md', "# hello\n");
        $this->writeWorkspaceFile('.cache/read-file-cache-mcp/debug.log', "skip\n");
        $this->writeWorkspaceFile('.phpunit.cache/test-results', "skip\n");
        $this->writeWorkspaceFile('vendor/package/file.php', "<?php\n");
        $this->writeWorkspaceFile('node_modules/pkg/index.js', "export {};\n");
        $this->writeWorkspaceFile('storage/logs/laravel.log', "skip\n");

        $guard = new PathGuard($this->workspaceRoot());
        $discovery = new FileDiscovery($guard);

        $paths = array_map(
            static fn (string $path): string => $guard->relativePath($path),
            $discovery->discover('', 20)
        );

        self::assertContains('app/Services/Example.php', $paths);
        self::assertContains('docs/readme.md', $paths);
        self::assertNotContains('.cache/read-file-cache-mcp/debug.log', $paths);
        self::assertNotContains('.phpunit.cache/test-results', $paths);
        self::assertNotContains('vendor/package/file.php', $paths);
        self::assertNotContains('node_modules/pkg/index.js', $paths);
        self::assertNotContains('storage/logs/laravel.log', $paths);
    }

    public function test_it_honors_subdirectory_scope(): void
    {
        $this->writeWorkspaceFile('app/Http/Controller.php', "<?php\n");
        $this->writeWorkspaceFile('docs/readme.md', "# docs\n");

        $guard = new PathGuard($this->workspaceRoot());
        $discovery = new FileDiscovery($guard);

        $paths = array_map(
            static fn (string $path): string => $guard->relativePath($path),
            $discovery->discover('app', 20)
        );

        self::assertSame(['app/Http/Controller.php'], $paths);
    }
}
