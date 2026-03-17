<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Tests;

use PHPUnit\Framework\TestCase;
use SemanticCodeSearchMcp\Tests\Support\CreatesTempWorkspace;

final class ServerSmokeTest extends TestCase
{
    use CreatesTempWorkspace;

    /** @var resource|null */
    private $process = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    protected function setUp(): void
    {
        $this->createWorkspace();
        $this->writeWorkspaceFile('AGENTS.md', "Semantic codebase guidance lives here.\nPrefer search_code when available.\n");
        $this->writeWorkspaceFile('ai/agent-tools-and-mcp.md', "grep MCP is documented here.\nsemantic queries should use search_code.\n");
        $this->writeWorkspaceFile('.env', "APP_KEY=secret\n");
    }

    protected function tearDown(): void
    {
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }

        $this->removeWorkspace();
    }

    public function test_stdio_server_indexes_and_searches_with_mock_embeddings(): void
    {
        $this->startServer();

        $initialize = $this->rpcRequest(1, 'initialize', [
            'protocolVersion' => '2025-03-26',
            'capabilities' => new \stdClass(),
            'clientInfo' => [
                'name' => 'phpunit',
                'version' => '1.0.0',
            ],
        ]);
        self::assertArrayHasKey('result', $initialize);

        $this->rpcNotify('notifications/initialized');

        $tools = $this->rpcRequest(2, 'tools/list', []);
        self::assertSame(['index_codebase', 'search_code', 'index_status'], array_map(
            static fn (array $tool): string => $tool['name'],
            $tools['result']['tools']
        ));

        $statusBefore = $this->rpcRequest(3, 'tools/call', [
            'name' => 'index_status',
            'arguments' => new \stdClass(),
        ]);
        self::assertFalse($statusBefore['result']['structuredContent']['ready']);

        $index = $this->rpcRequest(4, 'tools/call', [
            'name' => 'index_codebase',
            'arguments' => new \stdClass(),
        ]);
        self::assertFalse($index['result']['isError']);
        self::assertGreaterThanOrEqual(2, $index['result']['structuredContent']['files_scanned']);

        $statusAfter = $this->rpcRequest(5, 'tools/call', [
            'name' => 'index_status',
            'arguments' => new \stdClass(),
        ]);
        self::assertTrue($statusAfter['result']['structuredContent']['ready']);
        self::assertFalse($statusAfter['result']['structuredContent']['stale']);

        $search = $this->rpcRequest(6, 'tools/call', [
            'name' => 'search_code',
            'arguments' => [
                'query' => 'Where is grep MCP documented?',
                'limit' => 3,
            ],
        ]);
        self::assertFalse($search['result']['isError']);
        self::assertNotEmpty($search['result']['structuredContent']['results']);
        self::assertContains(
            $search['result']['structuredContent']['results'][0]['file'],
            ['AGENTS.md', 'ai/agent-tools-and-mcp.md']
        );

        $blocked = $this->rpcRequest(7, 'tools/call', [
            'name' => 'search_code',
            'arguments' => [
                'query' => 'secret',
                'path' => '.env',
            ],
        ]);
        self::assertTrue($blocked['result']['isError']);
        self::assertSame('PATH_NOT_ALLOWED', $blocked['result']['structuredContent']['code']);
    }

    private function startServer(): void
    {
        $packageRoot = dirname(__DIR__);
        $serverPath = $packageRoot.'/bin/server';

        $command = [PHP_BINARY, $serverPath];
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $environment = [
            'MCP_SEMANTIC_WORKSPACE_ROOT' => $this->workspaceRoot(),
            'MCP_SEMANTIC_INDEX_ROOT' => $this->workspacePath('.cache/semantic-code-search-mcp'),
            'MCP_SEMANTIC_OLLAMA_HOST' => 'mock://deterministic',
            'MCP_SEMANTIC_EMBED_MODEL' => 'mock-deterministic',
            'MCP_SEMANTIC_MAX_FILE_BYTES' => '1048576',
            'MCP_SEMANTIC_CHUNK_LINES' => '24',
            'MCP_SEMANTIC_CHUNK_OVERLAP' => '6',
        ];

        $this->process = proc_open($command, $descriptorSpec, $this->pipes, $packageRoot, $environment);
        self::assertIsResource($this->process);

        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);
    }

    /**
     * @param array<string, mixed>|object $params
     * @return array<string, mixed>
     */
    private function rpcRequest(int $id, string $method, array|object $params): array
    {
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ], JSON_THROW_ON_ERROR);

        fwrite($this->pipes[0], $payload.PHP_EOL);

        return $this->readResponse($id);
    }

    private function rpcNotify(string $method): void
    {
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'method' => $method,
        ], JSON_THROW_ON_ERROR);

        fwrite($this->pipes[0], $payload.PHP_EOL);
    }

    /**
     * @return array<string, mixed>
     */
    private function readResponse(int $id): array
    {
        $deadline = microtime(true) + 5;

        while (microtime(true) < $deadline) {
            $line = fgets($this->pipes[1]);
            if ($line === false) {
                usleep(50000);
                continue;
            }

            $decoded = json_decode(trim($line), true, 512, JSON_THROW_ON_ERROR);
            if (($decoded['id'] ?? null) === $id) {
                return $decoded;
            }
        }

        $stderr = stream_get_contents($this->pipes[2]);
        self::fail('Timed out waiting for RPC response. STDERR: '.($stderr === false ? '' : trim($stderr)));
    }
}
