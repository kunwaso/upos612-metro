<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Index;

use SemanticCodeSearchMcp\Embeddings\Embedder;
use SemanticCodeSearchMcp\SemanticCodeSearchException;

final class Indexer
{
    public function __construct(
        private readonly FileDiscovery $discovery,
        private readonly Chunker $chunker,
        private readonly IndexRepository $repository,
        private readonly Embedder $embedder,
        private readonly string $workspaceRoot,
    ) {
    }

    /**
     * @return array{
     *   files_scanned: int,
     *   files_indexed: int,
     *   files_removed: int,
     *   chunks_written: int,
     *   model: string,
     *   index_path: string
     * }
     */
    public function index(?string $workspacePath = null, bool $force = false): array
    {
        $discovery = $this->discovery->discover($workspacePath);
        $scope = $discovery['scope'];
        $files = $discovery['files'];

        $filesRemoved = $this->repository->removeMissingFiles(
            array_map(static fn (array $file): string => $file['path'], $files),
            $scope['path'],
            $scope['is_file']
        );

        $filesIndexed = 0;
        $chunksWritten = 0;

        foreach ($files as $file) {
            $existing = $this->repository->getFile($file['path']);
            if (
                !$force
                && $existing !== null
                && $existing['mtime'] === $file['mtime']
                && $existing['size'] === $file['size']
            ) {
                continue;
            }

            try {
                $content = $this->discovery->readTextFile($file);
            } catch (SemanticCodeSearchException $exception) {
                if (in_array($exception->errorCode(), ['BINARY_FILE', 'FILE_TOO_LARGE', 'READ_FAILED'], true)) {
                    $this->repository->deleteFile($file['path']);
                    continue;
                }

                throw $exception;
            }

            $contentHash = sha1($content);
            if (
                !$force
                && $existing !== null
                && $existing['content_hash'] === $contentHash
            ) {
                $this->repository->touchFile($file['path'], $file['mtime'], $file['size'], $contentHash);
                continue;
            }

            $chunks = $this->chunker->chunk($file['path'], $content);
            [$chunks, $embeddings] = $this->embedChunks($file['path'], $chunks);

            $chunksWritten += $this->repository->replaceFile(
                $file['path'],
                $file['mtime'],
                $file['size'],
                $contentHash,
                $chunks,
                $embeddings
            );
            $filesIndexed++;
        }

        $this->repository->updateMetadata($this->workspaceRoot, $this->embedder->model());

        return [
            'files_scanned' => count($files),
            'files_indexed' => $filesIndexed,
            'files_removed' => $filesRemoved,
            'chunks_written' => $chunksWritten,
            'model' => $this->embedder->model(),
            'index_path' => $this->repository->indexPath(),
        ];
    }

    /**
     * @param array<int, array{id: string, path: string, start_line: int, end_line: int, content: string}> $chunks
     * @return array{
     *   0: array<int, array{id: string, path: string, start_line: int, end_line: int, content: string}>,
     *   1: array<int, array<int, float>>
     * }
     */
    private function embedChunks(string $path, array $chunks): array
    {
        if ($chunks === []) {
            return [[], []];
        }

        try {
            return [$chunks, $this->embedder->embedTexts(array_column($chunks, 'content'))];
        } catch (SemanticCodeSearchException $exception) {
            if (! $this->isContextLengthOverflow($exception)) {
                throw $exception;
            }
        }

        $keptChunks = [];
        $embeddings = [];

        foreach ($chunks as $chunk) {
            try {
                $vector = $this->embedder->embedTexts([$chunk['content']])[0] ?? null;
            } catch (SemanticCodeSearchException $chunkException) {
                if ($this->isContextLengthOverflow($chunkException)) {
                    continue;
                }

                throw $chunkException;
            }

            if (! is_array($vector)) {
                continue;
            }

            $keptChunks[] = $chunk;
            $embeddings[] = $vector;
        }

        if ($keptChunks === []) {
            return [[], []];
        }

        return [$keptChunks, $embeddings];
    }

    private function isContextLengthOverflow(SemanticCodeSearchException $exception): bool
    {
        if ($exception->errorCode() !== 'EMBEDDING_FAILED') {
            return false;
        }

        return str_contains(strtolower($exception->getMessage()), 'context length');
    }
}
