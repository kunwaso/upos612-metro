<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Tests;

use PHPUnit\Framework\TestCase;
use SemanticCodeSearchMcp\Embeddings\OllamaEmbedder;
use SemanticCodeSearchMcp\SemanticCodeSearchException;

final class OllamaEmbedderTest extends TestCase
{
    public function test_it_uses_the_batch_endpoint_when_available(): void
    {
        $calls = [];
        $embedder = new OllamaEmbedder('http://127.0.0.1:11434', 'nomic-embed-text', static function (string $path, array $payload) use (&$calls): array {
            $calls[] = [$path, $payload];

            return [
                'status' => 200,
                'body' => json_encode([
                    'embeddings' => [
                        [3.0, 4.0],
                        [0.0, 2.0],
                    ],
                ], JSON_THROW_ON_ERROR),
            ];
        });

        $vectors = $embedder->embedTexts(['alpha beta', 'gamma']);

        self::assertCount(2, $vectors);
        self::assertSame('/api/embed', $calls[0][0]);
        self::assertEqualsWithDelta([0.6, 0.8], $vectors[0], 0.0001);
        self::assertEqualsWithDelta([0.0, 1.0], $vectors[1], 0.0001);
    }

    public function test_it_falls_back_to_legacy_endpoint_when_batch_is_unavailable(): void
    {
        $calls = [];
        $embedder = new OllamaEmbedder('http://127.0.0.1:11434', 'nomic-embed-text', static function (string $path, array $payload) use (&$calls): array {
            $calls[] = [$path, $payload];

            if ($path === '/api/embed') {
                return [
                    'status' => 404,
                    'body' => json_encode(['error' => 'missing'], JSON_THROW_ON_ERROR),
                ];
            }

            return [
                'status' => 200,
                'body' => json_encode(['embedding' => [1.0, 1.0]], JSON_THROW_ON_ERROR),
            ];
        });

        $vectors = $embedder->embedTexts(['alpha', 'beta']);

        self::assertCount(3, $calls);
        self::assertSame('/api/embed', $calls[0][0]);
        self::assertSame('/api/embeddings', $calls[1][0]);
        self::assertEqualsWithDelta([0.707106, 0.707106], $vectors[0], 0.0001);
        self::assertEqualsWithDelta([0.707106, 0.707106], $vectors[1], 0.0001);
    }

    public function test_it_throws_when_the_response_shape_is_invalid(): void
    {
        $embedder = new OllamaEmbedder('http://127.0.0.1:11434', 'nomic-embed-text', static function (): array {
            return [
                'status' => 200,
                'body' => json_encode(['no_embedding' => true], JSON_THROW_ON_ERROR),
            ];
        });

        $this->expectException(SemanticCodeSearchException::class);
        $embedder->embedTexts(['alpha']);
    }
}
