<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Index;

use PDO;
use PDOException;
use SemanticCodeSearchMcp\SemanticCodeSearchException;
use SemanticCodeSearchMcp\VectorMath;

final class IndexRepository
{
    private PDO $pdo;

    public function __construct(private readonly string $databasePath)
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new SemanticCodeSearchException('SQLITE_NOT_AVAILABLE', 'pdo_sqlite is required for semantic indexing.');
        }

        $directory = dirname($databasePath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new SemanticCodeSearchException('INDEX_ROOT_INVALID', 'Unable to create the semantic index directory.');
        }

        try {
            $this->pdo = new PDO('sqlite:'.$databasePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $exception) {
            throw new SemanticCodeSearchException('INDEX_ROOT_INVALID', 'Unable to open the semantic index database.', null, [
                'detail' => $exception->getMessage(),
            ]);
        }

        $this->pdo->exec('PRAGMA foreign_keys = ON;');
        $this->initializeSchema();
    }

    public function indexPath(): string
    {
        return str_replace('\\', '/', $this->databasePath);
    }

    /**
     * @return array{path: string, mtime: int, size: int, content_hash: string}|null
     */
    public function getFile(string $path): ?array
    {
        $statement = $this->pdo->prepare('SELECT path, mtime, size, content_hash FROM files WHERE path = :path');
        $statement->execute(['path' => $path]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return [
            'path' => (string) $row['path'],
            'mtime' => (int) $row['mtime'],
            'size' => (int) $row['size'],
            'content_hash' => (string) $row['content_hash'],
        ];
    }

    public function touchFile(string $path, int $mtime, int $size, string $contentHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE files SET mtime = :mtime, size = :size, content_hash = :content_hash WHERE path = :path'
        );
        $statement->execute([
            'path' => $path,
            'mtime' => $mtime,
            'size' => $size,
            'content_hash' => $contentHash,
        ]);
    }

    /**
     * @param array<int, array{id: string, path: string, start_line: int, end_line: int, content: string}> $chunks
     * @param array<int, array<int, float>> $embeddings
     */
    public function replaceFile(
        string $path,
        int $mtime,
        int $size,
        string $contentHash,
        array $chunks,
        array $embeddings,
    ): int {
        if (count($chunks) !== count($embeddings)) {
            throw new SemanticCodeSearchException('INVALID_VECTOR', 'Chunk and embedding counts do not match.', $path);
        }

        $indexedAt = gmdate(DATE_ATOM);

        $this->pdo->beginTransaction();

        try {
            $deleteChunks = $this->pdo->prepare('DELETE FROM chunks WHERE path = :path');
            $deleteChunks->execute(['path' => $path]);

            $upsertFile = $this->pdo->prepare(
                'INSERT INTO files (path, mtime, size, content_hash, chunk_count, indexed_at)
                 VALUES (:path, :mtime, :size, :content_hash, :chunk_count, :indexed_at)
                 ON CONFLICT(path) DO UPDATE SET
                    mtime = excluded.mtime,
                    size = excluded.size,
                    content_hash = excluded.content_hash,
                    chunk_count = excluded.chunk_count,
                    indexed_at = excluded.indexed_at'
            );
            $upsertFile->execute([
                'path' => $path,
                'mtime' => $mtime,
                'size' => $size,
                'content_hash' => $contentHash,
                'chunk_count' => count($chunks),
                'indexed_at' => $indexedAt,
            ]);

            $insertChunk = $this->pdo->prepare(
                'INSERT INTO chunks (id, path, start_line, end_line, content, embedding)
                 VALUES (:id, :path, :start_line, :end_line, :content, :embedding)'
            );

            foreach ($chunks as $index => $chunk) {
                $insertChunk->bindValue('id', $chunk['id']);
                $insertChunk->bindValue('path', $path);
                $insertChunk->bindValue('start_line', $chunk['start_line'], PDO::PARAM_INT);
                $insertChunk->bindValue('end_line', $chunk['end_line'], PDO::PARAM_INT);
                $insertChunk->bindValue('content', $chunk['content']);
                $insertChunk->bindValue('embedding', VectorMath::encode($embeddings[$index]), PDO::PARAM_LOB);
                $insertChunk->execute();
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return count($chunks);
    }

    public function deleteFile(string $path): void
    {
        $statement = $this->pdo->prepare('DELETE FROM files WHERE path = :path');
        $statement->execute(['path' => $path]);
    }

    /**
     * @param array<int, string> $currentPaths
     */
    public function removeMissingFiles(array $currentPaths, string $scopePath, bool $scopeIsFile): int
    {
        $existingPaths = $this->listIndexedPathsInScope($scopePath, $scopeIsFile);
        $currentLookup = array_fill_keys($currentPaths, true);
        $removed = 0;

        foreach ($existingPaths as $existingPath) {
            if (!isset($currentLookup[$existingPath])) {
                $this->deleteFile($existingPath);
                $removed++;
            }
        }

        return $removed;
    }

    public function updateMetadata(string $workspaceRoot, string $model): void
    {
        $this->setMetadata('workspace_root', $workspaceRoot);
        $this->setMetadata('model', $model);
        $this->setMetadata('last_indexed_at', gmdate(DATE_ATOM));
    }

    /**
     * @return array{
     *   ready: bool,
     *   stale: bool,
     *   workspace_root: ?string,
     *   model: ?string,
     *   last_indexed_at: ?string,
     *   files_indexed: int,
     *   chunks_indexed: int,
     *   index_path: string
     * }
     */
    public function status(string $currentWorkspaceRoot, string $currentModel): array
    {
        $storedWorkspaceRoot = $this->getMetadata('workspace_root');
        $storedModel = $this->getMetadata('model');
        $lastIndexedAt = $this->getMetadata('last_indexed_at');
        $filesIndexed = (int) $this->pdo->query('SELECT COUNT(*) FROM files')->fetchColumn();
        $chunksIndexed = (int) $this->pdo->query('SELECT COUNT(*) FROM chunks')->fetchColumn();

        $ready = $lastIndexedAt !== null;
        $stale = $ready && (
            $storedWorkspaceRoot !== $currentWorkspaceRoot
            || $storedModel !== $currentModel
        );

        return [
            'ready' => $ready,
            'stale' => $stale,
            'workspace_root' => $storedWorkspaceRoot,
            'model' => $storedModel,
            'last_indexed_at' => $lastIndexedAt,
            'files_indexed' => $filesIndexed,
            'chunks_indexed' => $chunksIndexed,
            'index_path' => $this->indexPath(),
        ];
    }

    /**
     * @return array<int, array{file: string, start_line: int, end_line: int, score: float, snippet: string}>
     */
    public function search(array $queryVector, int $limit, ?string $pathFilter = null, bool $pathIsFile = false): array
    {
        $sql = 'SELECT path, start_line, end_line, content, embedding FROM chunks';
        $params = [];

        if ($pathFilter !== null) {
            if ($pathIsFile) {
                $sql .= ' WHERE path = :path';
                $params['path'] = $pathFilter;
            } else {
                $sql .= ' WHERE path = :exact OR path LIKE :prefix';
                $params['exact'] = $pathFilter;
                $params['prefix'] = $pathFilter.'/%';
            }
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        $results = [];
        while ($row = $statement->fetch()) {
            if (!is_array($row)) {
                continue;
            }

            $vector = VectorMath::decode((string) $row['embedding']);
            if ($vector === []) {
                continue;
            }

            $results[] = [
                'file' => (string) $row['path'],
                'start_line' => (int) $row['start_line'],
                'end_line' => (int) $row['end_line'],
                'score' => round(VectorMath::dotProduct($queryVector, $vector), 6),
                'snippet' => $this->makeSnippet((string) $row['content']),
            ];
        }

        usort($results, static function (array $left, array $right): int {
            if ($left['score'] === $right['score']) {
                if ($left['file'] === $right['file']) {
                    return $left['start_line'] <=> $right['start_line'];
                }

                return $left['file'] <=> $right['file'];
            }

            return $right['score'] <=> $left['score'];
        });

        return array_slice($results, 0, $limit);
    }

    private function initializeSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS files (
                path TEXT PRIMARY KEY,
                mtime INTEGER NOT NULL,
                size INTEGER NOT NULL,
                content_hash TEXT NOT NULL,
                chunk_count INTEGER NOT NULL DEFAULT 0,
                indexed_at TEXT NOT NULL
            )'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS chunks (
                id TEXT PRIMARY KEY,
                path TEXT NOT NULL,
                start_line INTEGER NOT NULL,
                end_line INTEGER NOT NULL,
                content TEXT NOT NULL,
                embedding BLOB NOT NULL,
                FOREIGN KEY(path) REFERENCES files(path) ON DELETE CASCADE
            )'
        );

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_chunks_path ON chunks(path)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS metadata (key TEXT PRIMARY KEY, value TEXT NOT NULL)');
    }

    /**
     * @return array<int, string>
     */
    private function listIndexedPathsInScope(string $scopePath, bool $scopeIsFile): array
    {
        if ($scopePath === '.') {
            $statement = $this->pdo->query('SELECT path FROM files ORDER BY path ASC');

            return array_map(
                static fn (array $row): string => (string) $row['path'],
                $statement->fetchAll()
            );
        }

        if ($scopeIsFile) {
            $statement = $this->pdo->prepare('SELECT path FROM files WHERE path = :path ORDER BY path ASC');
            $statement->execute(['path' => $scopePath]);
        } else {
            $statement = $this->pdo->prepare(
                'SELECT path FROM files WHERE path = :exact OR path LIKE :prefix ORDER BY path ASC'
            );
            $statement->execute([
                'exact' => $scopePath,
                'prefix' => $scopePath.'/%',
            ]);
        }

        return array_map(
            static fn (array $row): string => (string) $row['path'],
            $statement->fetchAll()
        );
    }

    private function setMetadata(string $key, string $value): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO metadata (key, value) VALUES (:key, :value)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value'
        );
        $statement->execute([
            'key' => $key,
            'value' => $value,
        ]);
    }

    private function getMetadata(string $key): ?string
    {
        $statement = $this->pdo->prepare('SELECT value FROM metadata WHERE key = :key');
        $statement->execute(['key' => $key]);
        $value = $statement->fetchColumn();

        return is_string($value) ? $value : null;
    }

    private function makeSnippet(string $content): string
    {
        $normalized = trim(str_replace(["\r\n", "\r"], "\n", $content));
        $lines = array_slice(explode("\n", $normalized), 0, 6);
        $snippet = implode("\n", $lines);

        if (strlen($snippet) > 320) {
            $snippet = substr($snippet, 0, 317).'...';
        }

        return $snippet;
    }
}
