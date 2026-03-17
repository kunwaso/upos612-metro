<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Embeddings;

use SemanticCodeSearchMcp\VectorMath;

final class DeterministicEmbedder implements Embedder
{
    private int $dimensions;

    public function __construct(
        private readonly string $modelName = 'mock-deterministic',
        int $dimensions = 48,
    ) {
        $this->dimensions = max(8, $dimensions);
    }

    public function model(): string
    {
        return $this->modelName;
    }

    public function embedTexts(array $texts): array
    {
        $vectors = [];

        foreach ($texts as $text) {
            $vector = array_fill(0, $this->dimensions, 0.0);
            $tokens = $this->tokenize($text);

            foreach ($tokens as $token) {
                $hash = crc32($token);
                $primary = $hash % $this->dimensions;
                $secondary = (($hash >> 8) & 0x7fffffff) % $this->dimensions;

                $vector[$primary] += 1.0;
                $vector[$secondary] += 0.5;
            }

            $vectors[] = VectorMath::normalize($vector);
        }

        return $vectors;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $text = strtolower($text);
        $tokens = preg_split('/[^a-z0-9_]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens !== []) {
            return $tokens;
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($characters, static fn (string $character): bool => trim($character) !== ''));
    }
}
