<?php

declare(strict_types=1);

namespace ReadFileCacheMcp\Tests;

use PHPUnit\Framework\TestCase;
use ReadFileCacheMcp\Tests\Support\CreatesTempWorkspace;

final class ServerSmokeTest extends TestCase
{
    use CreatesTempWorkspace;

    /** @var resource|null */
    private $process = null;

    /** @var array<int, resource> */
    private array $pipes = [];

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

    public function test_stdio_server_reads_and_blocks_expected_paths(): void
    {
        $this->createWorkspace();
        $this->writeWorkspaceFile('AGENTS.md', "l1\nl2\nl3\nl4\nl5\n");
        $this->writeWorkspaceFile('.env', "APP_KEY=secret\n");
        $this->writeWorkspaceFile('vendor/autoload.php', "<?php\n");

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
        $toolNames = array_map(static fn (array $tool): string => $tool['name'], $tools['result']['tools']);
        self::assertContains('read_file', $toolNames);
        self::assertContains('warm_cache', $toolNames);
        self::assertSame(1, $tools['result']['tools'][0]['inputSchema']['properties']['offset']['minimum']);
        self::assertSame(1, $tools['result']['tools'][0]['inputSchema']['properties']['limit']['minimum']);
        self::assertSame(
            'Relative path from workspace root, or absolute path inside workspace.',
            $tools['result']['tools'][0]['inputSchema']['properties']['path']['description']
        );

        $defaultLimitSlice = $this->rpcRequest(3, 'tools/call', [
            'name' => 'read_file',
            'arguments' => [
                'path' => 'AGENTS.md',
                'offset' => 2,
            ],
        ]);
        self::assertSame("l2\nl3", $defaultLimitSlice['result']['content'][0]['text']);
        self::assertSame([
            'text' => "l2\nl3",
            'path' => 'AGENTS.md',
            'requested_offset' => 2,
            'requested_limit' => 2,
            'start_line' => 2,
            'end_line' => 3,
            'total_lines' => 5,
            'eof' => false,
            'truncated' => false,
            'next_offset' => 4,
            'cache_hit' => false,
        ], $defaultLimitSlice['result']['structuredContent']);

        $lineCapped = $this->rpcRequest(4, 'tools/call', [
            'name' => 'read_file',
            'arguments' => [
                'path' => 'AGENTS.md',
                'offset' => 1,
                'limit' => 5,
            ],
        ]);
        self::assertSame("l1\nl2\nl3", $lineCapped['result']['content'][0]['text']);
        self::assertTrue($lineCapped['result']['structuredContent']['truncated']);
        self::assertSame(5, $lineCapped['result']['structuredContent']['requested_limit']);
        self::assertSame(4, $lineCapped['result']['structuredContent']['next_offset']);

        $blockedEnv = $this->rpcRequest(5, 'tools/call', [
            'name' => 'read_file',
            'arguments' => [
                'path' => '.env',
            ],
        ]);
        self::assertTrue($blockedEnv['result']['isError']);
        self::assertSame('PATH_NOT_ALLOWED', $blockedEnv['result']['structuredContent']['code']);

        $blockedVendor = $this->rpcRequest(6, 'tools/call', [
            'name' => 'read_file',
            'arguments' => [
                'path' => 'vendor/autoload.php',
            ],
        ]);
        self::assertTrue($blockedVendor['result']['isError']);
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
            'MCP_READ_FILE_WORKSPACE_ROOT' => $this->workspaceRoot(),
            'MCP_READ_FILE_CACHE_ROOT' => $this->workspaceRoot().'/.cache/read-file-cache-mcp',
            'MCP_READ_FILE_DEFAULT_LIMIT' => '2',
            'MCP_READ_FILE_MAX_LIMIT' => '3',
            'MCP_READ_FILE_MAX_RESPONSE_BYTES' => '262144',
            'MCP_READ_FILE_MAX_FILE_BYTES' => '1048576',
            'MCP_READ_FILE_MAX_CACHE_FILES' => '16',
            'MCP_READ_FILE_MAX_CACHE_BYTES' => '1048576',
        ];

        $this->process = proc_open($command, $descriptorSpec, $this->pipes, $packageRoot, $environment);
        self::assertIsResource($this->process);

        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function rpcRequest(int $id, string $method, array $params): array
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
