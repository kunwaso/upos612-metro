<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Tests;

use PHPUnit\Framework\TestCase;
use SemanticCodeSearchMcp\Index\FileDiscovery;
use SemanticCodeSearchMcp\PathGuard;
use SemanticCodeSearchMcp\SemanticCodeSearchException;
use SemanticCodeSearchMcp\Tests\Support\CreatesTempWorkspace;

final class FileDiscoveryTest extends TestCase
{
    use CreatesTempWorkspace;

    private FileDiscovery $discovery;

    protected function setUp(): void
    {
        $this->createWorkspace();
        $this->writeWorkspaceFile('app/Example.php', "<?php\nreturn 'ok';\n");
        $this->writeWorkspaceFile('AGENTS.md', "Plan guidance\n");
        $this->writeWorkspaceFile('docs/ignored.md', "outside default roots\n");
        $this->writeWorkspaceFile('public/assets/app.js', "ignored\n");
        $this->writeWorkspaceFile('Modules/ProjectX/readme.md', "module docs\n");
        $this->writeWorkspaceFile('app/binary.txt', "abc\0def");
        $this->writeWorkspaceFile('app/large.txt', str_repeat('x', 200));

        $this->discovery = new FileDiscovery(new PathGuard($this->workspaceRoot()), 64);
    }

    protected function tearDown(): void
    {
        $this->removeWorkspace();
    }

    public function test_it_discovers_default_scope_without_excluded_paths(): void
    {
        $result = $this->discovery->discover();
        $paths = array_column($result['files'], 'path');

        self::assertContains('AGENTS.md', $paths);
        self::assertContains('app/Example.php', $paths);
        self::assertContains('Modules/ProjectX/readme.md', $paths);
        self::assertNotContains('docs/ignored.md', $paths);
        self::assertNotContains('public/assets/app.js', $paths);
        self::assertNotContains('app/large.txt', $paths);
    }

    public function test_it_rejects_binary_file_contents_when_reading(): void
    {
        $result = $this->discovery->discover('app');
        $binary = null;

        foreach ($result['files'] as $file) {
            if ($file['path'] === 'app/binary.txt') {
                $binary = $file;
                break;
            }
        }

        self::assertNotNull($binary);

        try {
            $this->discovery->readTextFile($binary);
            self::fail('Expected binary file rejection.');
        } catch (SemanticCodeSearchException $exception) {
            self::assertSame('BINARY_FILE', $exception->errorCode());
        }
    }
}
