<?php

declare(strict_types=1);

namespace ReadFileCacheMcp\Tests;

use PHPUnit\Framework\TestCase;
use ReadFileCacheMcp\PathGuard;
use ReadFileCacheMcp\ReadFileException;
use ReadFileCacheMcp\Tests\Support\CreatesTempWorkspace;

final class PathGuardTest extends TestCase
{
    use CreatesTempWorkspace;

    private PathGuard $guard;

    protected function setUp(): void
    {
        $this->createWorkspace();
        $this->writeWorkspaceFile('notes/example.txt', "alpha\nbeta\n");
        $this->writeWorkspaceFile('.env', "APP_KEY=secret\n");
        $this->writeWorkspaceFile('vendor/autoload.php', "<?php\n");
        $this->writeWorkspaceFile('.git/config', "[core]\n");
        $this->writeWorkspaceFile('storage/logs/laravel.log', "secret\n");
        $this->writeWorkspaceFile('docs/secret-notes/readme.txt', "hidden\n");
        $this->writeWorkspaceFile('docs/passwords/list.txt', "hidden\n");
        $this->writeWorkspaceFile('certs/client.key', "private\n");
        mkdir($this->workspacePath('notes/folder'), 0777, true);

        $this->guard = new PathGuard($this->workspaceRoot());
    }

    protected function tearDown(): void
    {
        $this->removeWorkspace();
    }

    public function test_it_resolves_relative_absolute_and_mixed_separator_paths_inside_workspace(): void
    {
        $expected = $this->workspacePath('notes/example.txt');

        self::assertSame($expected, $this->normalizePath($this->guard->assertReadableFile('notes/example.txt')));
        self::assertSame($expected, $this->normalizePath($this->guard->assertReadableFile($expected)));
        self::assertSame(
            $expected,
            $this->normalizePath($this->guard->assertReadableFile(str_replace('/', '\\', $expected)))
        );
        self::assertSame('notes/example.txt', $this->guard->relativePath($expected));
    }

    /**
     * @dataProvider blockedPathProvider
     */
    public function test_it_rejects_blocked_paths_with_stable_error_codes(string $path): void
    {
        $this->assertReadFileException('PATH_NOT_ALLOWED', $path, function () use ($path): void {
            $this->guard->assertReadableFile($path);
        });
    }

    public function test_it_rejects_missing_files_directories_and_outside_paths(): void
    {
        $outside = dirname($this->workspaceRoot()).'/outside.txt';
        file_put_contents($outside, "nope\n");

        try {
            $this->assertReadFileException('FILE_NOT_FOUND', 'notes/missing.txt', function (): void {
                $this->guard->assertReadableFile('notes/missing.txt');
            });
            $this->assertReadFileException('NOT_A_FILE', 'notes/folder', function (): void {
                $this->guard->assertReadableFile('notes/folder');
            });
            $this->assertReadFileException('PATH_NOT_ALLOWED', $outside, function () use ($outside): void {
                $this->guard->assertReadableFile($outside);
            });
            $this->assertReadFileException('PATH_NOT_ALLOWED', '../outside.txt', function (): void {
                $this->guard->assertReadableFile('../outside.txt');
            });
        } finally {
            @unlink($outside);
        }
    }

    public function test_it_accepts_drive_letter_case_differences_on_case_insensitive_platforms(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            self::markTestSkipped('This case-insensitive path check is only relevant on Windows.');
        }

        $expected = $this->workspacePath('notes/example.txt');
        $variant = $this->alternateDriveLetterCase($expected);

        self::assertSame($expected, $this->normalizePath($this->guard->assertReadableFile($variant)));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function blockedPathProvider(): iterable
    {
        yield 'dotenv' => ['.env'];
        yield 'dotenv variant' => ['.env.local'];
        yield 'vendor file' => ['vendor/autoload.php'];
        yield 'git file' => ['.git/config'];
        yield 'storage file' => ['storage/logs/laravel.log'];
        yield 'secret segment' => ['docs/secret-notes/readme.txt'];
        yield 'password segment' => ['docs/passwords/list.txt'];
        yield 'key extension' => ['certs/client.key'];
        yield 'pem extension' => ['certs/client.pem'];
        yield 'crt extension' => ['certs/client.crt'];
        yield 'p12 extension' => ['certs/client.p12'];
    }

    /**
     * @param callable(): void $callback
     */
    private function assertReadFileException(string $expectedCode, ?string $expectedPath, callable $callback): void
    {
        try {
            $callback();
            self::fail('Expected ReadFileException was not thrown.');
        } catch (ReadFileException $exception) {
            self::assertSame($expectedCode, $exception->errorCode());
            self::assertSame($expectedPath, $exception->path());
        }
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function alternateDriveLetterCase(string $path): string
    {
        if (preg_match('/^[A-Za-z]:/', $path) !== 1) {
            return $path;
        }

        $first = $path[0];
        $alternate = ctype_lower($first) ? strtoupper($first) : strtolower($first);

        return $alternate.substr($path, 1);
    }
}
