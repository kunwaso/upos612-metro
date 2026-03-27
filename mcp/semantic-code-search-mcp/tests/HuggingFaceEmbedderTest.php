<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Tests;

use PHPUnit\Framework\TestCase;
use SemanticCodeSearchMcp\Embeddings\HuggingFaceEmbedder;
use SemanticCodeSearchMcp\SemanticCodeSearchException;

final class HuggingFaceEmbedderTest extends TestCase
{
    public function test_it_embeds_documents_in_batch_and_normalizes_vectors(): void
    {
        $calls = [];
        $embedder = new HuggingFaceEmbedder(
            modelName: 'BAAI/bge-base-en',
            serverRoot: __DIR__.'/..',
            transport: static function (array $payload) use (&$calls): array {
                $calls[] = $payload;

                return [
                    'ok' => true,
                    'embeddings' => [
                        [3.0, 4.0],
                        [0.0, 2.0],
                    ],
                ];
            }
        );

        $vectors = $embedder->embedTexts(['alpha beta', 'gamma']);

        self::assertCount(2, $vectors);
        self::assertSame('embed', $calls[0]['type']);
        self::assertSame('document', $calls[0]['task']);
        self::assertEqualsWithDelta([0.6, 0.8], $vectors[0], 0.0001);
        self::assertEqualsWithDelta([0.0, 1.0], $vectors[1], 0.0001);
    }

    public function test_it_sends_query_task_for_query_embedding(): void
    {
        $calls = [];
        $embedder = new HuggingFaceEmbedder(
            modelName: 'BAAI/bge-base-en',
            serverRoot: __DIR__.'/..',
            transport: static function (array $payload) use (&$calls): array {
                $calls[] = $payload;

                return [
                    'ok' => true,
                    'embeddings' => [[10.0, 0.0]],
                ];
            }
        );

        $vector = $embedder->embedQuery('Where is search_code configured?');

        self::assertSame('query', $calls[0]['task']);
        self::assertEqualsWithDelta([1.0, 0.0], $vector, 0.0001);
    }

    public function test_it_throws_on_invalid_response_shape(): void
    {
        $embedder = new HuggingFaceEmbedder(
            modelName: 'BAAI/bge-base-en',
            serverRoot: __DIR__.'/..',
            transport: static fn (): array => ['ok' => true, 'embeddings' => 'invalid']
        );

        $this->expectException(SemanticCodeSearchException::class);
        $embedder->embedTexts(['alpha']);
    }
}

