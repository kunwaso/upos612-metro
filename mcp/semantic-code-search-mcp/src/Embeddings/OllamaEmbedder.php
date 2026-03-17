<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Embeddings;

use SemanticCodeSearchMcp\SemanticCodeSearchException;
use SemanticCodeSearchMcp\VectorMath;

final class OllamaEmbedder implements Embedder
{
    /**
     * @var null|callable(string, array<string, mixed>): array{status: int, body: string}
     */
    private $transport;

    /**
     * @param null|callable(string, array<string, mixed>): array{status: int, body: string} $transport
     */
    public function __construct(
        private readonly string $host,
        private readonly string $modelName,
        ?callable $transport = null,
    ) {
        $this->transport = $transport;
    }

    public function model(): string
    {
        return $this->modelName;
    }

    public function embedTexts(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $vectors = $this->tryBatchEmbeddings($texts);
        if ($vectors !== null) {
            return $vectors;
        }

        $embedded = [];
        foreach ($texts as $text) {
            $response = $this->request('/api/embeddings', [
                'model' => $this->modelName,
                'prompt' => $text,
            ]);

            $vector = $response['embedding'] ?? null;
            if (!is_array($vector)) {
                throw new SemanticCodeSearchException('INVALID_RESPONSE', 'Ollama returned an invalid embedding response.');
            }

            $embedded[] = VectorMath::normalize(array_map('floatval', array_values($vector)));
        }

        return $embedded;
    }

    /**
     * @param array<int, string> $texts
     * @return array<int, array<int, float>>|null
     */
    private function tryBatchEmbeddings(array $texts): ?array
    {
        try {
            $response = $this->request('/api/embed', [
                'model' => $this->modelName,
                'input' => array_values($texts),
            ]);
        } catch (SemanticCodeSearchException) {
            return null;
        }

        $vectors = $response['embeddings'] ?? null;
        if (!is_array($vectors) || count($vectors) !== count($texts)) {
            return null;
        }

        $embedded = [];
        foreach ($vectors as $vector) {
            if (!is_array($vector)) {
                return null;
            }

            $embedded[] = VectorMath::normalize(array_map('floatval', array_values($vector)));
        }

        return $embedded;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function request(string $path, array $payload): array
    {
        if ($this->transport !== null) {
            $response = ($this->transport)($path, $payload);
        } else {
            $response = $this->postJson($path, $payload);
        }

        if (($response['status'] ?? 500) >= 400) {
            $body = json_decode($response['body'], true);
            $message = is_array($body) && is_string($body['error'] ?? null)
                ? $body['error']
                : 'Embedding request failed.';

            throw new SemanticCodeSearchException('EMBEDDING_FAILED', $message);
        }

        $decoded = json_decode($response['body'], true);
        if (!is_array($decoded)) {
            throw new SemanticCodeSearchException('INVALID_RESPONSE', 'Ollama returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status: int, body: string}
     */
    private function postJson(string $path, array $payload): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $url = rtrim($this->host, '/').$path;

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Content-Length: '.strlen($body),
                ],
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => 120,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        if ($responseBody === false) {
            throw new SemanticCodeSearchException('EMBEDDING_FAILED', 'Unable to reach Ollama.');
        }

        $status = 200;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                $status = (int) $matches[1];
                break;
            }
        }

        return [
            'status' => $status,
            'body' => $responseBody,
        ];
    }
}
