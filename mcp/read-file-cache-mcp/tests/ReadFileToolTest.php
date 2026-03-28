<?php

declare(strict_types=1);

namespace ReadFileCacheMcp\Tests;

use Mcp\Schema\Result\CallToolResult;
use PHPUnit\Framework\TestCase;
use ReadFileCacheMcp\FileCache;
use ReadFileCacheMcp\FileDiscovery;
use ReadFileCacheMcp\PathGuard;
use ReadFileCacheMcp\ReadFileTool;
use ReadFileCacheMcp\Tests\Support\CreatesTempWorkspace;

final class ReadFileToolTest extends TestCase
{
    use CreatesTempWorkspace;

    protected function setUp(): void
    {
        $this->createWorkspace();
        $this->writeWorkspaceFile('.env', "APP_KEY=secret\n");
        mkdir($this->workspacePath('docs/folder'), 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeWorkspace();
    }

    public function test_it_reads_a_slice_with_full_metadata(): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "one\ntwo\nthree\nfour\n");
        $tool = $this->makeTool(maxResponseBytes: 64);

        $result = $tool->read_file('AGENTS.md', 2, 2);

        self::assertInstanceOf(CallToolResult::class, $result);
        self::assertFalse($result->isError);
        self::assertSame("two\nthree", $result->content[0]->text);
        self::assertSame([
            'text' => "two\nthree",
            'path' => 'AGENTS.md',
            'requested_offset' => 2,
            'requested_limit' => 2,
            'start_line' => 2,
            'end_line' => 3,
            'total_lines' => 4,
            'eof' => false,
            'truncated' => false,
            'next_offset' => 4,
            'cache_hit' => false,
        ], $result->structuredContent);
    }

    public function test_it_uses_the_default_limit_when_limit_is_omitted(): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "one\ntwo\nthree\nfour\n");
        $tool = $this->makeTool(defaultLimit: 2, maxLimit: 3, maxResponseBytes: 64);

        $result = $tool->read_file('AGENTS.md', 2);

        self::assertSame("two\nthree", $result->content[0]->text);
        self::assertSame(2, $result->structuredContent['requested_limit']);
        self::assertSame(3, $result->structuredContent['end_line']);
        self::assertSame(4, $result->structuredContent['next_offset']);
    }

    public function test_it_reports_a_cache_hit_on_repeated_reads(): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "one\ntwo\n");
        $tool = $this->makeTool(maxResponseBytes: 64);

        $first = $tool->read_file('AGENTS.md', 1, 1);
        $second = $tool->read_file('AGENTS.md', 1, 1);

        self::assertFalse($first->structuredContent['cache_hit']);
        self::assertTrue($second->structuredContent['cache_hit']);
    }

    public function test_it_returns_empty_content_for_out_of_range_offset(): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "one\ntwo\n");
        $tool = $this->makeTool(maxResponseBytes: 64);

        $result = $tool->read_file('AGENTS.md', 10, 5);

        self::assertSame('', $result->content[0]->text);
        self::assertSame([
            'text' => '',
            'path' => 'AGENTS.md',
            'requested_offset' => 10,
            'requested_limit' => 5,
            'start_line' => 10,
            'end_line' => 9,
            'total_lines' => 2,
            'eof' => true,
            'truncated' => false,
            'next_offset' => null,
            'cache_hit' => false,
        ], $result->structuredContent);
    }

    public function test_it_returns_empty_metadata_for_an_empty_file(): void
    {
        $this->writeWorkspaceFile('empty.txt', '');
        $tool = $this->makeTool(maxResponseBytes: 64);

        $result = $tool->read_file('empty.txt', 1, 5);

        self::assertSame('', $result->content[0]->text);
        self::assertSame([
            'text' => '',
            'path' => 'empty.txt',
            'requested_offset' => 1,
            'requested_limit' => 5,
            'start_line' => 1,
            'end_line' => 0,
            'total_lines' => 0,
            'eof' => true,
            'truncated' => false,
            'next_offset' => null,
            'cache_hit' => false,
        ], $result->structuredContent);
    }

    public function test_it_reads_files_without_a_trailing_newline(): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "one\ntwo");
        $tool = $this->makeTool(defaultLimit: 2, maxLimit: 2, maxResponseBytes: 64);

        $result = $tool->read_file('AGENTS.md', 1, 2);

        self::assertSame("one\ntwo", $result->content[0]->text);
        self::assertSame(2, $result->structuredContent['total_lines']);
        self::assertTrue($result->structuredContent['eof']);
        self::assertNull($result->structuredContent['next_offset']);
    }

    public function test_it_truncates_when_the_requested_limit_exceeds_the_max_limit(): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "one\ntwo\nthree\nfour\nfive\n");
        $tool = $this->makeTool(defaultLimit: 2, maxLimit: 3, maxResponseBytes: 64);

        $result = $tool->read_file('AGENTS.md', 1, 5);

        self::assertSame("one\ntwo\nthree", $result->content[0]->text);
        self::assertTrue($result->structuredContent['truncated']);
        self::assertFalse($result->structuredContent['eof']);
        self::assertSame(5, $result->structuredContent['requested_limit']);
        self::assertSame(4, $result->structuredContent['next_offset']);
    }

    public function test_it_truncates_only_at_line_boundaries_when_the_response_byte_limit_is_hit(): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "12345\n67890\nabcde\n");
        $tool = $this->makeTool(defaultLimit: 3, maxLimit: 3, maxResponseBytes: 12);

        $result = $tool->read_file('AGENTS.md', 1, 3);

        self::assertSame("12345\n67890", $result->content[0]->text);
        self::assertTrue($result->structuredContent['truncated']);
        self::assertSame(2, $result->structuredContent['end_line']);
        self::assertSame(3, $result->structuredContent['next_offset']);
    }

    public function test_it_returns_a_stable_error_when_the_first_line_exceeds_the_response_limit(): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "1234567890123\n");
        $tool = $this->makeTool(defaultLimit: 1, maxLimit: 1, maxResponseBytes: 12);

        $result = $tool->read_file('AGENTS.md', 1, 1);

        $this->assertErrorResult($result, 'RESPONSE_TOO_LARGE', 'AGENTS.md');
    }

    public function test_warm_cache_defaults_to_fast_source_roots(): void
    {
        $this->writeWorkspaceFile('app/Services/VasWarm.php', "<?php\nreturn true;\n");
        $this->writeWorkspaceFile('docs/guide.md', "# Guide\n");
        $tool = $this->makeTool(maxResponseBytes: 128);

        $result = $tool->warm_cache();

        self::assertFalse($result->isError);
        self::assertSame(1, $result->structuredContent['warmed']);
        self::assertSame(1, $result->structuredContent['paths_scanned']);
        self::assertTrue($tool->read_file('app/Services/VasWarm.php', 1, 5)->structuredContent['cache_hit']);
        self::assertFalse($tool->read_file('docs/guide.md', 1, 5)->structuredContent['cache_hit']);
    }

    public function test_warm_cache_can_explicitly_scan_workspace_root(): void
    {
        $this->writeWorkspaceFile('app/Services/VasWarm.php', "<?php\nreturn true;\n");
        $this->writeWorkspaceFile('docs/guide.md', "# Guide\n");
        $tool = $this->makeTool(maxResponseBytes: 128);

        $result = $tool->warm_cache('.', 10);

        self::assertFalse($result->isError);
        self::assertSame(2, $result->structuredContent['warmed']);
        self::assertSame(2, $result->structuredContent['paths_scanned']);
        self::assertTrue($tool->read_file('docs/guide.md', 1, 5)->structuredContent['cache_hit']);
    }

    public function test_warm_cache_counts_binary_files_as_skipped_not_errors(): void
    {
        $this->writeWorkspaceFile('Modules/Vas/Resources/assets/fonts/sample.ttf', "abc\0def");
        $tool = $this->makeTool(maxResponseBytes: 128);

        $result = $tool->warm_cache('Modules', 10);

        self::assertFalse($result->isError);
        self::assertSame(0, $result->structuredContent['warmed']);
        self::assertSame(1, $result->structuredContent['skipped']);
        self::assertSame(0, $result->structuredContent['errors']);
    }

    /**
     * @dataProvider invalidArgumentProvider
     */
    public function test_it_returns_stable_argument_errors(mixed $path, mixed $offset, mixed $limit, ?string $expectedPath): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "one\ntwo\n");
        $tool = $this->makeTool(maxResponseBytes: 64);

        $result = $tool->read_file($path, $offset, $limit);

        $this->assertErrorResult($result, 'INVALID_ARGUMENT', $expectedPath);
    }

    /**
     * @dataProvider pathErrorProvider
     */
    public function test_it_returns_stable_path_and_file_errors(string $path, string $expectedCode): void
    {
        $this->writeWorkspaceFile('AGENTS.md', "one\ntwo\n");
        $tool = $this->makeTool(maxResponseBytes: 64);

        $result = $tool->read_file($path, 1, 1);

        $this->assertErrorResult($result, $expectedCode, $path);
    }

    /**
     * @return iterable<string, array{0: mixed, 1: mixed, 2: mixed, 3: ?string}>
     */
    public static function invalidArgumentProvider(): iterable
    {
        yield 'empty path' => ['', 1, 1, null];
        yield 'offset zero' => ['AGENTS.md', 0, 1, 'AGENTS.md'];
        yield 'offset negative' => ['AGENTS.md', -1, 1, 'AGENTS.md'];
        yield 'limit zero' => ['AGENTS.md', 1, 0, 'AGENTS.md'];
        yield 'limit negative' => ['AGENTS.md', 1, -1, 'AGENTS.md'];
        yield 'offset string' => ['AGENTS.md', '1', 1, 'AGENTS.md'];
        yield 'limit string' => ['AGENTS.md', 1, '2', 'AGENTS.md'];
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function pathErrorProvider(): iterable
    {
        yield 'blocked dotenv' => ['.env', 'PATH_NOT_ALLOWED'];
        yield 'missing file' => ['missing.txt', 'FILE_NOT_FOUND'];
        yield 'directory path' => ['docs/folder', 'NOT_A_FILE'];
    }

    private function makeTool(
        int $defaultLimit = 2,
        int $maxLimit = 3,
        int $maxResponseBytes = 64,
        int $maxFileBytes = 1024,
        int $maxCacheFiles = 8,
        int $maxCacheBytes = 4096,
    ): ReadFileTool {
        $guard = new PathGuard($this->workspaceRoot());
        $cache = new FileCache($maxFileBytes, $maxCacheFiles, $maxCacheBytes);
        $fileDiscovery = new FileDiscovery($guard);

        return new ReadFileTool($guard, $cache, $defaultLimit, $maxLimit, $maxResponseBytes, $fileDiscovery);
    }

    private function assertErrorResult(CallToolResult $result, string $expectedCode, ?string $expectedPath): void
    {
        self::assertTrue($result->isError);
        self::assertSame($expectedCode, $result->structuredContent['code']);
        if ($expectedPath === null) {
            self::assertArrayNotHasKey('path', $result->structuredContent);
        } else {
            self::assertSame($expectedPath, $result->structuredContent['path']);
        }
    }
}
