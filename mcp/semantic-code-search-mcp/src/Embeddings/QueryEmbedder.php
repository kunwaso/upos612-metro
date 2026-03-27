<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Embeddings;

interface QueryEmbedder
{
    /**
     * Embed a user query. Implementations may apply model-specific
     * query instructions before encoding.
     *
     * @return array<int, float>
     */
    public function embedQuery(string $query): array;
}

