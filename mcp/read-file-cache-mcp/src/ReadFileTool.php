<?php

declare(strict_types=1);

namespace ReadFileCacheMcp;

use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Throwable;

final class ReadFileTool
{
    private int $defaultLimit;

    private int $maxLimit;

    private int $maxResponseBytes;

    public function __construct(
        private readonly PathGuard $pathGuard,
        private readonly FileCache $cache,
        int $defaultLimit,
        int $maxLimit,
        int $maxResponseBytes,
        private readonly FileDiscovery $fileDiscovery,
    ) {
        $this->maxLimit = max(1, $maxLimit);
        $this->defaultLimit = max(1, min($defaultLimit, $this->maxLimit));
        $this->maxResponseBytes = max(1, $maxResponseBytes);
    }

    public function read_file(mixed $path, mixed $offset = null, mixed $limit = null): CallToolResult
    {
        try {
            $path = $this->validatePath($path);
            $offset = $this->validatePositiveInteger($offset, $path, 'offset', 1);
            $requestedLimit = $this->validatePositiveInteger($limit, $path, 'limit', $this->defaultLimit);
            $resolved = $this->pathGuard->assertReadableFile($path);

            $entry = $this->cache->get($resolved);
            $lines = $entry['lines'];
            $totalLines = count($lines);

            if ($offset > $totalLines) {
                return $this->successResult(
                    path: $this->pathGuard->relativePath($resolved),
                    requestedOffset: $offset,
                    requestedLimit: $requestedLimit,
                    text: '',
                    startLine: $offset,
                    endLine: $offset - 1,
                    totalLines: $totalLines,
                    truncated: false,
                    cacheHit: $entry['cache_hit'],
                );
            }

            $effectiveLimit = min($requestedLimit, $this->maxLimit);
            $availableLines = $totalLines - $offset + 1;
            $lineLimitTruncated = min($requestedLimit, $availableLines) > $effectiveLimit;
            $slice = array_slice($lines, $offset - 1, $effectiveLimit);

            $slicePayload = $this->buildSlicePayload(
                lines: $slice,
                offset: $offset,
                path: $this->pathGuard->relativePath($resolved),
                truncated: $lineLimitTruncated,
            );

            return $this->successResult(
                path: $this->pathGuard->relativePath($resolved),
                requestedOffset: $offset,
                requestedLimit: $requestedLimit,
                text: $slicePayload['text'],
                startLine: $offset,
                endLine: $slicePayload['end_line'],
                totalLines: $totalLines,
                truncated: $slicePayload['truncated'],
                cacheHit: $entry['cache_hit'],
            );
        } catch (ReadFileException $exception) {
            return $this->errorResult($exception);
        } catch (Throwable $exception) {
            return $this->errorResult(
                new ReadFileException('READ_FAILED', 'Unable to read file.')
            );
        }
    }

    /**
     * Pre-build the persistent disk cache by reading allowed workspace files.
     * Call this once (e.g. at session start) to fill the cache so subsequent read_file calls are faster.
     */
    public function warm_cache(mixed $path = null, mixed $max_files = null): CallToolResult
    {
        try {
            $underDir = $path !== null && is_string($path) ? trim($path) : '';
            $maxFiles = is_int($max_files) && $max_files > 0 ? min($max_files, 50000) : 5000;

            $paths = $this->fileDiscovery->discover($underDir, $maxFiles);
            $warmed = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($paths as $absolutePath) {
                try {
                    $resolved = $this->pathGuard->assertReadableFile($absolutePath);
                    $this->cache->get($resolved);
                    $warmed++;
                } catch (ReadFileException $e) {
                    if ($e->getCode() === 'BINARY_FILE' || $e->getCode() === 'FILE_TOO_LARGE') {
                        $skipped++;
                    } else {
                        $errors++;
                    }
                } catch (Throwable) {
                    $errors++;
                }
            }

            return new CallToolResult(
                [new TextContent(sprintf(
                    'Warmed %d files, skipped %d (binary/too large), %d errors. Disk cache is ready for read_file.',
                    $warmed,
                    $skipped,
                    $errors
                ))],
                false,
                [
                    'warmed' => $warmed,
                    'skipped' => $skipped,
                    'errors' => $errors,
                    'paths_scanned' => count($paths),
                ]
            );
        } catch (Throwable $e) {
            return new CallToolResult(
                [new TextContent('warm_cache failed: '.$e->getMessage())],
                true,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * @param array<int, string> $lines
     *
     * @return array{text: string, end_line: int, truncated: bool}
     */
    private function buildSlicePayload(array $lines, int $offset, string $path, bool $truncated): array
    {
        if ($lines === []) {
            return [
                'text' => '',
                'end_line' => $offset - 1,
                'truncated' => $truncated,
            ];
        }

        $parts = [];
        $usedBytes = 0;
        $endLine = $offset - 1;

        foreach ($lines as $index => $line) {
            $segment = $parts === [] ? $line : "\n".$line;
            $segmentBytes = strlen($segment);

            if ($usedBytes + $segmentBytes > $this->maxResponseBytes) {
                if ($parts === []) {
                    throw new ReadFileException(
                        'RESPONSE_TOO_LARGE',
                        'Requested line exceeds the max response byte limit.',
                        $path
                    );
                }

                $truncated = true;
                break;
            }

            $parts[] = $line;
            $usedBytes += $segmentBytes;
            $endLine = $offset + $index;
        }

        return [
            'text' => implode("\n", $parts),
            'end_line' => $endLine,
            'truncated' => $truncated,
        ];
    }

    private function validatePath(mixed $path): string
    {
        if (!is_string($path) || trim($path) === '') {
            throw new ReadFileException('INVALID_ARGUMENT', 'Path is required.');
        }

        return trim($path);
    }

    private function validatePositiveInteger(mixed $value, string $path, string $field, int $default): int
    {
        if ($value === null) {
            return $default;
        }

        if (!is_int($value) || $value < 1) {
            throw new ReadFileException(
                'INVALID_ARGUMENT',
                sprintf('%s must be an integer greater than or equal to 1.', ucfirst($field)),
                $path
            );
        }

        return $value;
    }

    private function successResult(
        string $path,
        int $requestedOffset,
        int $requestedLimit,
        string $text,
        int $startLine,
        int $endLine,
        int $totalLines,
        bool $truncated,
        bool $cacheHit,
    ): CallToolResult {
        $eof = $endLine >= $totalLines || $totalLines === 0;

        return new CallToolResult(
            [new TextContent($text)],
            false,
            [
                'path' => $path,
                'requested_offset' => $requestedOffset,
                'requested_limit' => $requestedLimit,
                'start_line' => $startLine,
                'end_line' => $endLine,
                'total_lines' => $totalLines,
                'eof' => $eof,
                'truncated' => $truncated,
                'next_offset' => $eof ? null : $endLine + 1,
                'cache_hit' => $cacheHit,
            ]
        );
    }

    private function errorResult(ReadFileException $exception): CallToolResult
    {
        $payload = $exception->toStructuredContent();
        if (isset($payload['path'])) {
            $payload['path'] = $this->pathGuard->relativePath($payload['path']);
        }

        return new CallToolResult(
            [new TextContent($payload['message'])],
            true,
            $payload
        );
    }
}
