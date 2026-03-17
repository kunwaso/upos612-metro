<?php

declare(strict_types=1);

namespace GrepMcp\Tests;

use GrepMcp\Tests\Support\CreatesTempWorkspace;
use PHPUnit\Framework\TestCase;

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

    public function test_stdio_server_lists_the_tool_and_blocks_disallowed_paths(): void
    {
        $this->ensureRipgrepAvailable();
        $this->createWorkspace();
        $this->writeWorkspaceFile('docs/notes.txt', "needle here\n");
        $this->writeWorkspaceFile('.env', "needle blocked\n");

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
        self::assertSame(['grep'], array_map(
            static fn (array $tool): string => $tool['name'],
            $tools['result']['tools']
        ));
        self::assertSame(1, $tools['result']['tools'][0]['inputSchema']['properties']['max_count']['minimum']);
        self::assertSame(1, $tools['result']['tools'][0]['inputSchema']['properties']['max_depth']['minimum']);
        self::assertSame(100, $tools['result']['tools'][0]['inputSchema']['properties']['max_depth']['maximum']);
        self::assertSame(
            'Required regex or literal pattern to search for.',
            $tools['result']['tools'][0]['inputSchema']['properties']['pattern']['description']
        );
        self::assertSame(
            'Enable smart case: when pattern has no uppercase letters, search case-insensitively.',
            $tools['result']['tools'][0]['inputSchema']['properties']['smart_case']['description']
        );

        $allowedSearch = $this->rpcRequest(3, 'tools/call', [
            'name' => 'grep',
            'arguments' => [
                'pattern' => 'needle',
            ],
        ]);
        self::assertFalse($allowedSearch['result']['isError']);
        self::assertSame('Found 1 matches.', $allowedSearch['result']['content'][0]['text']);
        self::assertSame([
            'matches' => [
                [
                    'file' => 'docs/notes.txt',
                    'line' => 1,
                    'column' => 1,
                    'match' => 'needle here',
                ],
            ],
            'total_count' => 1,
            'truncated' => false,
        ], $allowedSearch['result']['structuredContent']);

        $blockedSearch = $this->rpcRequest(4, 'tools/call', [
            'name' => 'grep',
            'arguments' => [
                'pattern' => 'needle',
                'path' => '.env',
            ],
        ]);
        self::assertTrue($blockedSearch['result']['isError']);
        self::assertSame('PATH_NOT_ALLOWED', $blockedSearch['result']['structuredContent']['code']);
        self::assertSame('.env', $blockedSearch['result']['structuredContent']['path']);
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

        $baseEnvironment = getenv();
        if (!is_array($baseEnvironment)) {
            $baseEnvironment = [];
        }

        $environment = array_merge($baseEnvironment, [
            'MCP_GREP_WORKSPACE_ROOT' => $this->workspaceRoot(),
            'MCP_GREP_MAX_COUNT' => '5',
            'MCP_GREP_TIMEOUT_SECONDS' => '10',
        ]);

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

    private function ensureRipgrepAvailable(): void
    {
        $command = DIRECTORY_SEPARATOR === '\\' ? ['where', 'rg'] : ['which', 'rg'];
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            sys_get_temp_dir()
        );

        if (!is_resource($process)) {
            self::markTestSkipped('Unable to verify ripgrep availability.');
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            self::markTestSkipped('ripgrep (rg) is not available on PATH.');
        }
    }
}
