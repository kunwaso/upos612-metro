<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Tests;

use PHPUnit\Framework\TestCase;
use SemanticCodeSearchMcp\PathGuard;
use SemanticCodeSearchMcp\SemanticCodeSearchException;
use SemanticCodeSearchMcp\Tests\Support\CreatesTempWorkspace;

final class PathGuardTest extends TestCase
{
    use CreatesTempWorkspace;

    private PathGuard $guard;

    protected function setUp(): void
    {
        $this->createWorkspace();
        $this->writeWorkspaceFile('app/Example.php', "<?php\n");
        $this->writeWorkspaceFile('.env', "APP_KEY=secret\n");
        $this->writeWorkspaceFile('vendor/autoload.php', "<?php\n");
        $this->writeWorkspaceFile('.git/config', "[core]\n");
        $this->writeWorkspaceFile('storage/logs/laravel.log', "nope\n");
        $this->writeWorkspaceFile('.cache/semantic-code-search-mcp/index.sqlite', "db\n");
        $this->writeWorkspaceFile('public/assets/app.js', "console.log('x');\n");
        $this->writeWorkspaceFile('public/modules/module.js', "console.log('y');\n");
        $this->writeWorkspaceFile('docs/password-reset/readme.txt', "hidden\n");
        $this->writeWorkspaceFile('certs/client.key', "private\n");

        $this->guard = new PathGuard($this->workspaceRoot());
    }

    protected function tearDown(): void
    {
        $this->removeWorkspace();
    }

    public function test_it_resolves_relative_and_absolute_paths_inside_workspace(): void
    {
        $expected = $this->workspacePath('app/Example.php');

        self::assertSame($expected, $this->normalizePath($this->guard->assertSearchPath('app/Example.php')));
        self::assertSame($expected, $this->normalizePath($this->guard->assertSearchPath($expected)));
        self::assertSame('app/Example.php', $this->guard->relativePath($expected));
    }

    /**
     * @dataProvider blockedPathProvider
     */
    public function test_it_rejects_blocked_paths(string $path): void
    {
        try {
            $this->guard->assertSearchPath($path);
            self::fail('Expected SemanticCodeSearchException was not thrown.');
        } catch (SemanticCodeSearchException $exception) {
            self::assertSame('PATH_NOT_ALLOWED', $exception->errorCode());
            self::assertSame($path, $exception->path());
        }
    }

    public function test_it_rejects_missing_and_outside_paths(): void
    {
        $outside = dirname($this->workspaceRoot()).'/outside.txt';
        file_put_contents($outside, "nope\n");

        try {
            $this->expectException(SemanticCodeSearchException::class);
            $this->guard->assertSearchPath('../outside.txt');
        } finally {
            @unlink($outside);
        }
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function blockedPathProvider(): iterable
    {
        yield 'dotenv' => ['.env'];
        yield 'vendor' => ['vendor/autoload.php'];
        yield 'git' => ['.git/config'];
        yield 'storage' => ['storage/logs/laravel.log'];
        yield 'cache' => ['.cache/semantic-code-search-mcp/index.sqlite'];
        yield 'public assets' => ['public/assets/app.js'];
        yield 'public modules' => ['public/modules/module.js'];
        yield 'password segment' => ['docs/password-reset/readme.txt'];
        yield 'key extension' => ['certs/client.key'];
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
