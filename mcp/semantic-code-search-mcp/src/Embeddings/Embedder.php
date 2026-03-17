<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Embeddings;

interface Embedder
{
    public function model(): string;

    /**
     * @param array<int, string> $texts
     * @return array<int, array<int, float>>
     */
    public function embedTexts(array $texts): array;
}
