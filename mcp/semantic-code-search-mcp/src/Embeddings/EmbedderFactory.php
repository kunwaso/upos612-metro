<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Embeddings;

use RuntimeException;

final class EmbedderFactory
{
    public static function modelFromEnvironment(): string
    {
        return (string) (getenv('MCP_SEMANTIC_EMBED_MODEL') ?: 'BAAI/bge-base-en');
    }

    public static function fromEnvironment(string $serverRoot): Embedder
    {
        $model = self::modelFromEnvironment();
        $backend = strtolower(trim((string) (getenv('MCP_SEMANTIC_EMBED_BACKEND') ?: 'huggingface')));

        if ($backend === 'mock' || $backend === 'deterministic' || str_starts_with($model, 'mock-')) {
            return new DeterministicEmbedder($model);
        }

        if (!in_array($backend, ['huggingface', 'hf'], true)) {
            throw new RuntimeException(sprintf(
                'MCP_SEMANTIC_EMBED_BACKEND must be one of: huggingface, hf, deterministic, mock. Received: %s',
                $backend
            ));
        }

        return new HuggingFaceEmbedder(
            modelName: $model,
            serverRoot: $serverRoot,
            pythonBinary: (string) (getenv('MCP_SEMANTIC_PYTHON_BIN') ?: 'python'),
            batchSize: self::readPositiveInt('MCP_SEMANTIC_HF_BATCH_SIZE', 24),
            maxLength: self::readPositiveInt('MCP_SEMANTIC_HF_MAX_LENGTH', 512),
            device: (string) (getenv('MCP_SEMANTIC_HF_DEVICE') ?: 'auto'),
            normalizeEmbeddings: self::readBoolean('MCP_SEMANTIC_HF_NORMALIZE', true),
            localFilesOnly: self::readBoolean('MCP_SEMANTIC_HF_LOCAL_FILES_ONLY', true),
            queryInstruction: (string) (
                getenv('MCP_SEMANTIC_HF_QUERY_INSTRUCTION')
                ?: 'Represent this sentence for searching relevant passages: '
            ),
            timeoutSeconds: self::readPositiveInt('MCP_SEMANTIC_HF_TIMEOUT_SECONDS', 300)
        );
    }

    private static function readPositiveInt(string $name, int $default): int
    {
        $raw = getenv($name);
        if ($raw === false || $raw === '') {
            return $default;
        }

        $value = filter_var($raw, FILTER_VALIDATE_INT);
        if ($value === false || $value < 1) {
            throw new RuntimeException(sprintf('%s must be a positive integer.', $name));
        }

        return $value;
    }

    private static function readBoolean(string $name, bool $default): bool
    {
        $raw = getenv($name);
        if ($raw === false || $raw === '') {
            return $default;
        }

        $value = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            throw new RuntimeException(sprintf('%s must be a boolean value.', $name));
        }

        return $value;
    }
}

