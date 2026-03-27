<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp;

use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use SemanticCodeSearchMcp\Embeddings\Embedder;
use SemanticCodeSearchMcp\Embeddings\QueryEmbedder;
use SemanticCodeSearchMcp\Index\Indexer;
use SemanticCodeSearchMcp\Index\IndexRepository;
use Throwable;

final class SemanticSearchTool
{
    public function __construct(
        private readonly PathGuard $pathGuard,
        private readonly Indexer $indexer,
        private readonly IndexRepository $repository,
        private readonly Embedder $embedder,
        private readonly string $workspaceRoot,
    ) {
    }

    public function index_codebase(mixed $workspace_path = null, mixed $force = null): CallToolResult
    {
        try {
            $workspacePath = $this->validateOptionalString($workspace_path, 'workspace_path');
            $force = $this->validateBoolean($force, 'force', false);
            $result = $this->indexer->index($workspacePath, $force);

            return new CallToolResult(
                [new TextContent(sprintf(
                    'Indexed %d files and wrote %d semantic chunks.',
                    $result['files_indexed'],
                    $result['chunks_written']
                ))],
                false,
                $result
            );
        } catch (SemanticCodeSearchException $exception) {
            return $this->errorResult($exception);
        } catch (Throwable) {
            return $this->errorResult(
                new SemanticCodeSearchException('INDEX_FAILED', 'Semantic indexing failed.')
            );
        }
    }

    public function search_code(mixed $query, mixed $limit = null, mixed $path = null): CallToolResult
    {
        try {
            $query = $this->validateRequiredString($query, 'query');
            $limit = $this->validatePositiveInteger($limit, 'limit', 10, 50);
            $path = $this->validateOptionalString($path, 'path');

            $status = $this->repository->status($this->workspaceRoot, $this->embedder->model());
            if (!$status['ready']) {
                throw new SemanticCodeSearchException('INDEX_NOT_READY', 'Semantic index is not ready.');
            }

            if ($status['stale']) {
                throw new SemanticCodeSearchException(
                    'INDEX_STALE',
                    'Semantic index is stale for the current workspace or model.'
                );
            }

            $pathFilter = null;
            $pathIsFile = false;
            if ($path !== null) {
                $resolved = $this->pathGuard->assertSearchPath($path);
                $pathFilter = $this->pathGuard->relativePath($resolved);
                $pathIsFile = is_file($resolved);
            }

            $vector = $this->embedder instanceof QueryEmbedder
                ? $this->embedder->embedQuery($query)
                : ($this->embedder->embedTexts([$query])[0] ?? null);

            if (!is_array($vector) || $vector === []) {
                throw new SemanticCodeSearchException('EMBEDDING_FAILED', 'Unable to embed the search query.');
            }

            $results = $this->repository->search($vector, $limit, $pathFilter, $pathIsFile);

            return new CallToolResult(
                [new TextContent(sprintf('Found %d semantic matches.', count($results)))],
                false,
                ['results' => $results]
            );
        } catch (SemanticCodeSearchException $exception) {
            return $this->errorResult($exception);
        } catch (Throwable) {
            return $this->errorResult(
                new SemanticCodeSearchException('SEARCH_FAILED', 'Semantic search failed.')
            );
        }
    }

    public function index_status(): CallToolResult
    {
        try {
            $status = $this->repository->status($this->workspaceRoot, $this->embedder->model());

            return new CallToolResult(
                [new TextContent($status['ready'] ? 'Semantic index is ready.' : 'Semantic index is not ready.')],
                false,
                $status
            );
        } catch (SemanticCodeSearchException $exception) {
            return $this->errorResult($exception);
        } catch (Throwable) {
            return $this->errorResult(
                new SemanticCodeSearchException('STATUS_FAILED', 'Unable to read semantic index status.')
            );
        }
    }

    private function validateRequiredString(mixed $value, string $field): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new SemanticCodeSearchException('INVALID_ARGUMENT', sprintf('%s is required.', ucfirst($field)));
        }

        return trim($value);
    }

    private function validateOptionalString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw new SemanticCodeSearchException(
                'INVALID_ARGUMENT',
                sprintf('%s must be a non-empty string.', ucfirst($field))
            );
        }

        return trim($value);
    }

    private function validateBoolean(mixed $value, string $field, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        if (!is_bool($value)) {
            throw new SemanticCodeSearchException(
                'INVALID_ARGUMENT',
                sprintf('%s must be a boolean.', ucfirst($field))
            );
        }

        return $value;
    }

    private function validatePositiveInteger(mixed $value, string $field, int $default, int $max): int
    {
        if ($value === null) {
            return $default;
        }

        if (!is_int($value) || $value < 1) {
            throw new SemanticCodeSearchException(
                'INVALID_ARGUMENT',
                sprintf('%s must be an integer greater than or equal to 1.', ucfirst($field))
            );
        }

        return min($value, $max);
    }

    private function errorResult(SemanticCodeSearchException $exception): CallToolResult
    {
        $payload = $exception->toStructuredContent();

        if (isset($payload['path']) && is_string($payload['path'])) {
            $resolved = $this->pathGuard->resolve($payload['path']);
            $payload['path'] = $this->pathGuard->relativePath($resolved);
        }

        return new CallToolResult(
            [new TextContent($payload['message'])],
            true,
            $payload
        );
    }
}
