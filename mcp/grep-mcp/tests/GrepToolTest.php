<?php

declare(strict_types=1);

namespace GrepMcp\Tests;

use GrepMcp\GrepTool;
use GrepMcp\PathGuard;
use GrepMcp\Tests\Support\CreatesTempWorkspace;
use Mcp\Schema\Result\CallToolResult;
use PHPUnit\Framework\TestCase;

final class GrepToolTest extends TestCase
{
    use CreatesTempWorkspace;

    protected function setUp(): void
    {
        $this->ensureRipgrepAvailable();
        $this->createWorkspace();
        $this->writeWorkspaceFile('src/alpha.php', "<?php\nneedle();\n");
        $this->writeWorkspaceFile('src/beta.txt', "needle in text\n");
        $this->writeWorkspaceFile('docs/notes.txt', "plain note\nneedle here\n");
        $this->writeWorkspaceFile('docs/regex.txt', "ab\na+b\n");
        $this->writeWorkspaceFile('vendor/autoload.php', "needle blocked\n");
        $this->writeWorkspaceFile('.env', "needle blocked\n");
    }

    protected function tearDown(): void
    {
        $this->removeWorkspace();
    }

    public function test_it_returns_structured_matches_for_allowed_files_only(): void
    {
        $result = $this->makeTool(defaultMaxCount: 10)->grep('needle');

        self::assertInstanceOf(CallToolResult::class, $result);
        self::assertFalse($result->isError);
        self::assertSame('Found 3 matches.', $result->content[0]->text);
        self::assertSame(3, $result->structuredContent['total_count']);
        self::assertFalse($result->structuredContent['truncated']);
        self::assertSame([
            [
                'file' => 'docs/notes.txt',
                'line' => 2,
                'column' => 1,
                'match' => 'needle here',
            ],
            [
                'file' => 'src/alpha.php',
                'line' => 2,
                'column' => 1,
                'match' => 'needle();',
            ],
            [
                'file' => 'src/beta.txt',
                'line' => 1,
                'column' => 1,
                'match' => 'needle in text',
            ],
        ], $this->sortMatches($result->structuredContent['matches']));
    }

    public function test_it_supports_fixed_string_searches(): void
    {
        $result = $this->makeTool(defaultMaxCount: 10)->grep('a+b', fixed_strings: true);

        self::assertFalse($result->isError);
        self::assertSame([
            [
                'file' => 'docs/regex.txt',
                'line' => 2,
                'column' => 1,
                'match' => 'a+b',
            ],
        ], $result->structuredContent['matches']);
    }

    public function test_it_supports_ignore_case(): void
    {
        $this->writeWorkspaceFile('docs/case.txt', "Needle\nneedle\nNEEDLE\n");

        $caseSensitive = $this->makeTool(defaultMaxCount: 10)->grep('needle', path: 'docs/case.txt');
        self::assertFalse($caseSensitive->isError);
        self::assertSame(1, $caseSensitive->structuredContent['total_count']);
        self::assertSame('needle', $caseSensitive->structuredContent['matches'][0]['match']);

        $caseInsensitive = $this->makeTool(defaultMaxCount: 10)->grep('needle', path: 'docs/case.txt', ignore_case: true);
        self::assertFalse($caseInsensitive->isError);
        self::assertSame(3, $caseInsensitive->structuredContent['total_count']);
        $matches = array_column($caseInsensitive->structuredContent['matches'], 'match');
        self::assertSame(['Needle', 'needle', 'NEEDLE'], $matches);
    }

    public function test_it_supports_smart_case(): void
    {
        $this->writeWorkspaceFile('docs/smart-case.txt', "Needle\nneedle\nNEEDLE\n");

        $lowercasePattern = $this->makeTool(defaultMaxCount: 10)->grep('needle', path: 'docs/smart-case.txt', smart_case: true);
        self::assertFalse($lowercasePattern->isError);
        self::assertSame(3, $lowercasePattern->structuredContent['total_count']);

        $uppercasePattern = $this->makeTool(defaultMaxCount: 10)->grep('Needle', path: 'docs/smart-case.txt', smart_case: true);
        self::assertFalse($uppercasePattern->isError);
        self::assertSame(1, $uppercasePattern->structuredContent['total_count']);
        self::assertSame(['Needle'], array_column($uppercasePattern->structuredContent['matches'], 'match'));

        $forcedIgnoreCase = $this->makeTool(defaultMaxCount: 10)->grep(
            'Needle',
            path: 'docs/smart-case.txt',
            ignore_case: true,
            smart_case: true
        );
        self::assertFalse($forcedIgnoreCase->isError);
        self::assertSame(3, $forcedIgnoreCase->structuredContent['total_count']);
    }

    public function test_it_respects_path_include_and_exclude_filters(): void
    {
        $tool = $this->makeTool(defaultMaxCount: 10);

        $directoryScoped = $tool->grep('needle', path: 'docs');
        self::assertSame([
            [
                'file' => 'docs/notes.txt',
                'line' => 2,
                'column' => 1,
                'match' => 'needle here',
            ],
        ], $directoryScoped->structuredContent['matches']);

        $fileScoped = $tool->grep('needle', path: 'src/alpha.php');
        self::assertSame([
            [
                'file' => 'src/alpha.php',
                'line' => 2,
                'column' => 1,
                'match' => 'needle();',
            ],
        ], $fileScoped->structuredContent['matches']);

        $includeOnlyPhp = $tool->grep('needle', include_glob: '*.php');
        self::assertSame(['src/alpha.php'], array_column($includeOnlyPhp->structuredContent['matches'], 'file'));

        $excludeTxt = $tool->grep('needle', exclude_glob: '*.txt');
        self::assertSame(['src/alpha.php'], array_column($excludeTxt->structuredContent['matches'], 'file'));
    }

    public function test_it_skips_binary_file_matches(): void
    {
        $this->writeWorkspaceFile('docs/plain.txt', "needle text\n");
        $this->writeWorkspaceFile('docs/binary.bin', "needle\0binary\0payload");

        $result = $this->makeTool(defaultMaxCount: 10)->grep('needle', path: 'docs');

        self::assertFalse($result->isError);
        $files = array_column($result->structuredContent['matches'], 'file');
        self::assertContains('docs/plain.txt', $files);
        self::assertNotContains('docs/binary.bin', $files);
    }

    public function test_it_supports_max_depth(): void
    {
        $this->writeWorkspaceFile('depth/root.txt', "needle root\n");
        $this->writeWorkspaceFile('depth/one/child.txt', "needle child\n");
        $this->writeWorkspaceFile('depth/one/two/grand.txt', "needle grand\n");

        $result = $this->makeTool(defaultMaxCount: 10)->grep('needle', path: 'depth', max_depth: 2);

        self::assertFalse($result->isError);
        self::assertSame(
            ['depth/one/child.txt', 'depth/root.txt'],
            array_values(array_unique(array_column($this->sortMatches($result->structuredContent['matches']), 'file')))
        );
    }

    public function test_build_command_includes_max_count_binary_skip_and_optional_max_depth(): void
    {
        $tool = $this->makeTool(defaultMaxCount: 10);
        $method = (new \ReflectionClass($tool))->getMethod('buildCommand');
        $method->setAccessible(true);

        /** @var array<int, string> $command */
        $command = $method->invoke($tool, 'needle', '.', null, null, false, false, 25, null);
        self::assertContains('-I', $command);
        $maxCountIndex = array_search('--max-count', $command, true);
        self::assertNotFalse($maxCountIndex);
        self::assertSame('25', $command[$maxCountIndex + 1]);

        /** @var array<int, string> $withDepth */
        $withDepth = $method->invoke($tool, 'needle', '.', null, null, false, false, 25, 3);
        $maxDepthIndex = array_search('--max-depth', $withDepth, true);
        self::assertNotFalse($maxDepthIndex);
        self::assertSame('3', $withDepth[$maxDepthIndex + 1]);
    }

    public function test_it_returns_a_successful_empty_result_when_no_matches_exist(): void
    {
        $result = $this->makeTool(defaultMaxCount: 10)->grep('not-present');

        self::assertFalse($result->isError);
        self::assertSame('No matches found.', $result->content[0]->text);
        self::assertSame([], $result->structuredContent['matches']);
        self::assertSame(0, $result->structuredContent['total_count']);
        self::assertFalse($result->structuredContent['truncated']);
    }

    public function test_it_marks_results_truncated_at_the_global_max_count(): void
    {
        $this->writeWorkspaceFile('extra/one.txt', "needle one\n");
        $this->writeWorkspaceFile('extra/two.txt', "needle two\n");

        $result = $this->makeTool(defaultMaxCount: 2)->grep('needle');

        self::assertFalse($result->isError);
        self::assertCount(2, $result->structuredContent['matches']);
        self::assertSame(2, $result->structuredContent['total_count']);
        self::assertTrue($result->structuredContent['truncated']);
        self::assertSame('Found 2 matches (truncated).', $result->content[0]->text);
    }

    /**
     * @dataProvider invalidArgumentProvider
     */
    public function test_it_returns_stable_argument_errors(
        mixed $pattern,
        mixed $path,
        mixed $includeGlob,
        mixed $excludeGlob,
        mixed $maxCount,
        mixed $fixedStrings,
        mixed $ignoreCase,
        mixed $maxDepth,
        mixed $smartCase,
        ?string $expectedPath
    ): void {
        $result = $this->makeTool()->grep(
            $pattern,
            $path,
            $includeGlob,
            $excludeGlob,
            $maxCount,
            $fixedStrings,
            $ignoreCase,
            $maxDepth,
            $smartCase
        );

        $this->assertErrorResult($result, 'INVALID_ARGUMENT', $expectedPath);
    }

    /**
     * @dataProvider pathErrorProvider
     */
    public function test_it_returns_stable_path_errors(string $path, string $expectedCode): void
    {
        $result = $this->makeTool()->grep('needle', $path);

        $this->assertErrorResult($result, $expectedCode, $path);
    }

    /**
     * @return iterable<string, array{0: mixed, 1: mixed, 2: mixed, 3: mixed, 4: mixed, 5: mixed, 6: mixed, 7: mixed, 8: mixed, 9: ?string}>
     */
    public static function invalidArgumentProvider(): iterable
    {
        yield 'empty pattern' => ['', null, null, null, null, null, null, null, null, null];
        yield 'empty path' => ['needle', '', null, null, null, null, null, null, null, null];
        yield 'empty include glob' => ['needle', null, '', null, null, null, null, null, null, null];
        yield 'empty exclude glob' => ['needle', null, null, '', null, null, null, null, null, null];
        yield 'invalid max count' => ['needle', null, null, null, '2', null, null, null, null, null];
        yield 'invalid fixed strings' => ['needle', null, null, null, null, 'yes', null, null, null, null];
        yield 'invalid ignore_case' => ['needle', null, null, null, null, null, 'yes', null, null, null];
        yield 'invalid max_depth type' => ['needle', null, null, null, null, null, null, '2', null, null];
        yield 'invalid max_depth value' => ['needle', null, null, null, null, null, null, 0, null, null];
        yield 'invalid smart_case' => ['needle', null, null, null, null, null, null, null, 'yes', null];
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function pathErrorProvider(): iterable
    {
        yield 'blocked dotenv path' => ['.env', 'PATH_NOT_ALLOWED'];
        yield 'missing path' => ['missing.txt', 'SEARCH_PATH_NOT_FOUND'];
        yield 'outside path' => ['../outside.txt', 'PATH_NOT_ALLOWED'];
    }

    private function makeTool(int $defaultMaxCount = 3, int $timeoutSeconds = 10): GrepTool
    {
        return new GrepTool(new PathGuard($this->workspaceRoot()), $defaultMaxCount, $timeoutSeconds);
    }

    /**
     * @param array<int, array{file: string, line: int, column: int, match: string}> $matches
     *
     * @return array<int, array{file: string, line: int, column: int, match: string}>
     */
    private function sortMatches(array $matches): array
    {
        usort($matches, static function (array $left, array $right): int {
            return [$left['file'], $left['line'], $left['column']] <=> [$right['file'], $right['line'], $right['column']];
        });

        return $matches;
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

    private function ensureRipgrepAvailable(): void
    {
        $command = DIRECTORY_SEPARATOR === '\\' ? ['where', 'rg'] : ['which', 'rg'];
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            sys_get_temp_dir()
        );

        if (!is_resource($process)) {
            self::markTestSkipped('Unable to verify ripgrep availability.');
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            self::markTestSkipped('ripgrep (rg) is not available on PATH.');
        }
    }
}
