<?php

declare(strict_types=1);

namespace GrepMcp;

use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class GrepTool
{
    /**
     * @var string[]
     */
    private array $defaultExcludeGlobs = [
        '!.git/**',
        '!**/.git/**',
        '!vendor/**',
        '!**/vendor/**',
        '!storage/**',
        '!**/storage/**',
        '!node_modules/**',
        '!**/node_modules/**',
        '!.env',
        '!.env.*',
        '!**/.env',
        '!**/.env.*',
        '!*.pem',
        '!*.key',
        '!*.p12',
        '!*.crt',
        '!**/*secret*',
        '!**/*secret*/**',
        '!**/*password*',
        '!**/*password*/**',
        '!**/*.pem',
        '!**/*.key',
        '!**/*.p12',
        '!**/*.crt',
    ];

    private int $defaultMaxCount;

    private int $timeoutSeconds;

    public function __construct(
        private readonly PathGuard $pathGuard,
        int $defaultMaxCount,
        int $timeoutSeconds,
    ) {
        $this->defaultMaxCount = min(max(1, $defaultMaxCount), 500);
        $this->timeoutSeconds = max(1, $timeoutSeconds);
    }

    public function grep(
        mixed $pattern,
        mixed $path = null,
        mixed $include_glob = null,
        mixed $exclude_glob = null,
        mixed $max_count = null,
        mixed $fixed_strings = null,
        mixed $ignore_case = null,
        mixed $max_depth = null,
        mixed $smart_case = null,
    ): CallToolResult {
        try {
            $pattern = $this->validatePattern($pattern);
            $path = $this->validateOptionalString($path, 'path');
            $includeGlob = $this->validateOptionalString($include_glob, 'include_glob');
            $excludeGlob = $this->validateOptionalString($exclude_glob, 'exclude_glob');
            $maxCount = $this->validateMaxCount($max_count);
            $fixedStrings = $this->validateBoolean($fixed_strings, 'fixed_strings', false);
            $ignoreCase = $this->validateBoolean($ignore_case, 'ignore_case', false);
            $maxDepth = $this->validateMaxDepth($max_depth);
            $smartCase = $this->validateBoolean($smart_case, 'smart_case', false);
            $effectiveIgnoreCase = $ignoreCase || ($smartCase && preg_match('/[A-Z]/', $pattern) === 0);

            $searchRoot = $path === null ? '.' : $this->pathGuard->relativePath($this->pathGuard->assertSearchPath($path));
            $command = $this->buildCommand(
                $pattern,
                $searchRoot,
                $includeGlob,
                $excludeGlob,
                $fixedStrings,
                $effectiveIgnoreCase,
                $maxCount,
                $maxDepth
            );
            $search = $this->runSearch($command, $maxCount);

            return $this->successResult($search['matches'], $search['truncated']);
        } catch (GrepException $exception) {
            return $this->errorResult($exception);
        } catch (Throwable) {
            return $this->errorResult(
                new GrepException('SEARCH_FAILED', 'Search failed.')
            );
        }
    }

    /**
     * @param string[] $command
     *
     * @return array{matches: array<int, array{file: string, line: int, column: int, match: string}>, truncated: bool}
     */
    private function runSearch(array $command, int $maxCount): array
    {
        $process = new Process($command, $this->pathGuard->workspaceRoot());
        $process->setTimeout($this->timeoutSeconds);

        $buffer = '';
        $matches = [];
        $truncated = false;

        try {
            $process->run(function (string $type, string $chunk) use (&$buffer, &$matches, &$truncated, $maxCount, $process): void {
                if ($type !== Process::OUT || $chunk === '') {
                    return;
                }

                $buffer .= $chunk;

                while (($newlinePosition = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $newlinePosition);
                    $buffer = substr($buffer, $newlinePosition + 1);

                    $this->consumeJsonLine($line, $matches, $maxCount, $truncated);
                    if ($truncated) {
                        $process->stop(0);
                        return;
                    }
                }
            });
        } catch (ProcessTimedOutException) {
            throw new GrepException('SEARCH_TIMEOUT', 'Search timed out.');
        } catch (Throwable $exception) {
            if ($this->looksLikeRipgrepMissing($exception->getMessage())) {
                throw new GrepException('RIPGREP_NOT_AVAILABLE', 'ripgrep (rg) was not found or failed to start.');
            }

            throw new GrepException('SEARCH_FAILED', 'Search failed.');
        }

        if (!$truncated && trim($buffer) !== '') {
            $this->consumeJsonLine($buffer, $matches, $maxCount, $truncated);
        }

        if ($truncated) {
            return [
                'matches' => $matches,
                'truncated' => true,
            ];
        }

        $exitCode = (int) ($process->getExitCode() ?? 1);
        $stderr = trim($process->getErrorOutput());

        if ($exitCode === 0) {
            return [
                'matches' => $matches,
                'truncated' => false,
            ];
        }

        if ($exitCode === 1 && $matches === []) {
            return [
                'matches' => [],
                'truncated' => false,
            ];
        }

        if ($this->looksLikeRipgrepMissing($stderr)) {
            throw new GrepException('RIPGREP_NOT_AVAILABLE', 'ripgrep (rg) was not found or failed to start.');
        }

        throw new GrepException(
            'SEARCH_FAILED',
            $stderr !== '' ? sprintf('ripgrep failed: %s', $stderr) : 'Search failed.'
        );
    }

    /**
     * @param array<int, array{file: string, line: int, column: int, match: string}> $matches
     */
    private function consumeJsonLine(string $line, array &$matches, int $maxCount, bool &$truncated): void
    {
        $line = trim($line);
        if ($line === '') {
            return;
        }

        $decoded = json_decode($line, true);
        if (!is_array($decoded) || ($decoded['type'] ?? null) !== 'match') {
            return;
        }

        $event = $decoded['data'] ?? null;
        if (!is_array($event)) {
            return;
        }

        $pathPayload = $event['path'] ?? null;
        $pathText = is_array($pathPayload) ? ($pathPayload['text'] ?? null) : null;
        if (!is_string($pathText) || !$this->pathGuard->isPathAllowed($pathText)) {
            return;
        }

        $resolvedPath = $this->pathGuard->resolve($pathText);
        $relativePath = $this->pathGuard->relativePath($resolvedPath);

        $lineNumber = $event['line_number'] ?? 1;
        $lines = $event['lines'] ?? [];
        $lineText = is_array($lines) && is_string($lines['text'] ?? null)
            ? rtrim($lines['text'], "\r\n")
            : '';

        $submatches = $event['submatches'] ?? [];
        $column = 1;
        if (is_array($submatches) && isset($submatches[0]) && is_array($submatches[0]) && is_int($submatches[0]['start'] ?? null)) {
            $column = $submatches[0]['start'] + 1;
        }

        $matches[] = [
            'file' => $relativePath,
            'line' => is_int($lineNumber) ? $lineNumber : (int) $lineNumber,
            'column' => $column,
            'match' => $lineText,
        ];

        if (count($matches) >= $maxCount) {
            $truncated = true;
        }
    }

    /**
     * @return string[]
     */
    private function buildCommand(
        string $pattern,
        string $searchRoot,
        ?string $includeGlob,
        ?string $excludeGlob,
        bool $fixedStrings,
        bool $ignoreCase,
        int $maxCount,
        ?int $maxDepth,
    ): array {
        $command = [
            'rg',
            '--json',
            '--line-number',
            '--color',
            'never',
            '--max-count',
            (string) $maxCount,
            '--hidden',
            '-I',
        ];

        if ($fixedStrings) {
            $command[] = '--fixed-strings';
        }

        if ($ignoreCase) {
            $command[] = '-i';
        }

        foreach ($this->defaultExcludeGlobs as $glob) {
            $command[] = '--glob';
            $command[] = $glob;
        }

        if ($includeGlob !== null) {
            $command[] = '--glob';
            $command[] = $includeGlob;
        }

        if ($excludeGlob !== null) {
            $command[] = '--glob';
            $command[] = str_starts_with($excludeGlob, '!') ? $excludeGlob : '!'.$excludeGlob;
        }

        if ($maxDepth !== null) {
            $command[] = '--max-depth';
            $command[] = (string) $maxDepth;
        }

        $command[] = $pattern;
        $command[] = $searchRoot;

        return $command;
    }

    private function validatePattern(mixed $pattern): string
    {
        if (!is_string($pattern) || trim($pattern) === '') {
            throw new GrepException('INVALID_ARGUMENT', 'Pattern is required.');
        }

        return $pattern;
    }

    private function validateOptionalString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw new GrepException(
                'INVALID_ARGUMENT',
                sprintf('%s must be a non-empty string.', $field)
            );
        }

        return trim($value);
    }

    private function validateMaxCount(mixed $value): int
    {
        if ($value === null) {
            return $this->defaultMaxCount;
        }

        if (!is_int($value) || $value < 1) {
            throw new GrepException('INVALID_ARGUMENT', 'max_count must be an integer greater than or equal to 1.');
        }

        return min($value, 500);
    }

    private function validateMaxDepth(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (!is_int($value) || $value < 1) {
            throw new GrepException('INVALID_ARGUMENT', 'max_depth must be an integer greater than or equal to 1.');
        }

        return min($value, 100);
    }

    private function validateBoolean(mixed $value, string $field, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        if (!is_bool($value)) {
            throw new GrepException('INVALID_ARGUMENT', sprintf('%s must be a boolean.', $field));
        }

        return $value;
    }

    /**
     * @param array<int, array{file: string, line: int, column: int, match: string}> $matches
     */
    private function successResult(array $matches, bool $truncated): CallToolResult
    {
        $count = count($matches);
        $message = $count === 0
            ? 'No matches found.'
            : sprintf('Found %d matches%s.', $count, $truncated ? ' (truncated)' : '');

        return new CallToolResult(
            [new TextContent($message)],
            false,
            [
                'matches' => $matches,
                'total_count' => $count,
                'truncated' => $truncated,
            ]
        );
    }

    private function errorResult(GrepException $exception): CallToolResult
    {
        $payload = $exception->toStructuredContent();
        if (isset($payload['path']) && is_string($payload['path'])) {
            $payload['path'] = $this->displayPath($payload['path']);
        }

        return new CallToolResult(
            [new TextContent($payload['message'])],
            true,
            $payload
        );
    }

    private function displayPath(string $path): string
    {
        $resolved = $this->pathGuard->resolve($path);
        if ($this->pathGuard->relativePath($resolved) !== $resolved) {
            return $this->pathGuard->relativePath($resolved);
        }

        return $path;
    }

    private function looksLikeRipgrepMissing(string $message): bool
    {
        $message = strtolower($message);

        return str_contains($message, 'could not be found')
            || str_contains($message, 'cannot find')
            || str_contains($message, 'not found')
            || str_contains($message, 'not recognized')
            || str_contains($message, 'failed to start');
    }
}
