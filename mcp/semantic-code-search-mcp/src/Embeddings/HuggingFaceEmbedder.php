<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Embeddings;

use SemanticCodeSearchMcp\SemanticCodeSearchException;
use SemanticCodeSearchMcp\VectorMath;

final class HuggingFaceEmbedder implements Embedder, QueryEmbedder
{
    /**
     * @var resource|null
     */
    private $process = null;

    /**
     * @var array<int, resource>|null
     */
    private ?array $pipes = null;

    private readonly string $workerScript;

    private bool $shutdownRegistered = false;

    /**
     * @var null|callable(array<string, mixed>): array<string, mixed>
     */
    private $transport;

    /**
     * @param null|callable(array<string, mixed>): array<string, mixed> $transport
     */
    public function __construct(
        private readonly string $modelName,
        string $serverRoot,
        private readonly string $pythonBinary = 'python',
        private readonly int $batchSize = 24,
        private readonly int $maxLength = 512,
        private readonly string $device = 'auto',
        private readonly bool $normalizeEmbeddings = true,
        private readonly bool $localFilesOnly = true,
        private readonly string $queryInstruction = 'Represent this sentence for searching relevant passages: ',
        private readonly int $timeoutSeconds = 300,
        ?callable $transport = null,
    ) {
        if ($this->batchSize < 1) {
            throw new SemanticCodeSearchException('INVALID_ARGUMENT', 'Batch size must be a positive integer.');
        }

        if ($this->maxLength < 1) {
            throw new SemanticCodeSearchException('INVALID_ARGUMENT', 'Max length must be a positive integer.');
        }

        if ($this->timeoutSeconds < 1) {
            throw new SemanticCodeSearchException('INVALID_ARGUMENT', 'Timeout must be a positive integer.');
        }

        $normalizedRoot = rtrim(str_replace('\\', '/', $serverRoot), '/');
        $this->workerScript = $normalizedRoot.'/scripts/hf_embed_worker.py';
        $this->transport = $transport;
    }

    public function __destruct()
    {
        $this->shutdownWorker();
    }

    public function model(): string
    {
        return $this->modelName;
    }

    public function embedTexts(array $texts): array
    {
        return $this->embed($texts, false);
    }

    public function embedQuery(string $query): array
    {
        $vectors = $this->embed([$query], true);
        $vector = $vectors[0] ?? null;

        if (!is_array($vector) || $vector === []) {
            throw new SemanticCodeSearchException('EMBEDDING_FAILED', 'Unable to embed the search query.');
        }

        return $vector;
    }

    /**
     * @param array<int, string> $texts
     * @return array<int, array<int, float>>
     */
    private function embed(array $texts, bool $isQuery): array
    {
        if ($texts === []) {
            return [];
        }

        foreach ($texts as $text) {
            if (!is_string($text)) {
                throw new SemanticCodeSearchException('INVALID_ARGUMENT', 'All embedding inputs must be strings.');
            }
        }

        $response = $this->request([
            'type' => 'embed',
            'task' => $isQuery ? 'query' : 'document',
            'texts' => array_values($texts),
        ]);

        $vectors = $response['embeddings'] ?? null;
        if (!is_array($vectors) || count($vectors) !== count($texts)) {
            throw new SemanticCodeSearchException(
                'INVALID_RESPONSE',
                'Local embedding worker returned an invalid vector payload.'
            );
        }

        $embedded = [];
        foreach ($vectors as $vector) {
            if (!is_array($vector)) {
                throw new SemanticCodeSearchException(
                    'INVALID_RESPONSE',
                    'Local embedding worker returned a non-array vector.'
                );
            }

            $floatVector = array_map('floatval', array_values($vector));
            $embedded[] = $this->normalizeEmbeddings ? VectorMath::normalize($floatVector) : $floatVector;
        }

        return $embedded;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function request(array $payload): array
    {
        if ($this->transport !== null) {
            $response = ($this->transport)($payload);
        } else {
            $this->ensureWorker();

            if ($this->pipes === null || !isset($this->pipes[0])) {
                throw new SemanticCodeSearchException('EMBEDDING_FAILED', 'Embedding worker is not available.');
            }

            $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
            if (@fwrite($this->pipes[0], $encoded.PHP_EOL) === false) {
                throw new SemanticCodeSearchException('EMBEDDING_FAILED', 'Unable to send request to embedding worker.');
            }
            fflush($this->pipes[0]);

            $line = $this->readWorkerLine();
            $response = json_decode($line, true);
        }

        if (!is_array($response)) {
            throw new SemanticCodeSearchException('INVALID_RESPONSE', 'Embedding worker returned invalid JSON.');
        }

        $ok = $response['ok'] ?? false;
        if ($ok !== true) {
            $message = $response['error'] ?? 'Embedding request failed.';
            throw new SemanticCodeSearchException('EMBEDDING_FAILED', (string) $message);
        }

        return $response;
    }

    private function ensureWorker(): void
    {
        if (is_resource($this->process)) {
            return;
        }

        if (!is_file($this->workerScript)) {
            throw new SemanticCodeSearchException('EMBEDDING_FAILED', 'Missing Hugging Face embedding worker script.');
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $environment = $this->buildWorkerEnvironment([
            'MCP_SEMANTIC_EMBED_MODEL' => $this->modelName,
            'MCP_SEMANTIC_HF_BATCH_SIZE' => (string) $this->batchSize,
            'MCP_SEMANTIC_HF_MAX_LENGTH' => (string) $this->maxLength,
            'MCP_SEMANTIC_HF_DEVICE' => $this->device,
            'MCP_SEMANTIC_HF_NORMALIZE' => $this->normalizeEmbeddings ? '1' : '0',
            'MCP_SEMANTIC_HF_LOCAL_FILES_ONLY' => $this->localFilesOnly ? '1' : '0',
            'MCP_SEMANTIC_HF_QUERY_INSTRUCTION' => $this->queryInstruction,
            'PYTHONUNBUFFERED' => '1',
        ]);

        $options = DIRECTORY_SEPARATOR === '\\' ? ['bypass_shell' => true] : [];
        $command = [$this->pythonBinary, $this->workerScript];

        $process = proc_open($command, $descriptorSpec, $pipes, dirname($this->workerScript), $environment, $options);
        if (!is_resource($process)) {
            throw new SemanticCodeSearchException('EMBEDDING_FAILED', 'Unable to start the local embedding worker.');
        }

        $this->process = $process;
        $this->pipes = $pipes;

        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);

        $readyLine = $this->readWorkerLine();
        $ready = json_decode($readyLine, true);
        if (!is_array($ready)) {
            $message = 'Embedding worker failed to initialize.';
            if ($readyLine !== '') {
                $message .= ' Output: '.substr($readyLine, 0, 240);
            }
            $this->shutdownWorker();
            throw new SemanticCodeSearchException('EMBEDDING_FAILED', $message);
        }

        if (($ready['ok'] ?? false) !== true) {
            $message = (string) ($ready['error'] ?? 'Embedding worker failed to initialize.');
            $this->shutdownWorker();
            throw new SemanticCodeSearchException('EMBEDDING_FAILED', $message);
        }

        if (!$this->shutdownRegistered) {
            $this->shutdownRegistered = true;
            register_shutdown_function(function (): void {
                $this->shutdownWorker();
            });
        }
    }

    private function readWorkerLine(): string
    {
        if ($this->pipes === null || !isset($this->pipes[1])) {
            throw new SemanticCodeSearchException('EMBEDDING_FAILED', 'Embedding worker output stream is unavailable.');
        }

        $deadline = microtime(true) + $this->timeoutSeconds;
        while (microtime(true) < $deadline) {
            $remaining = max(0.0, $deadline - microtime(true));
            $seconds = (int) $remaining;
            $microseconds = (int) (($remaining - $seconds) * 1_000_000);
            $read = [$this->pipes[1]];
            $write = [];
            $except = [];

            $selected = @stream_select($read, $write, $except, $seconds, $microseconds);
            if ($selected === false) {
                break;
            }

            if ($selected === 0) {
                continue;
            }

            $line = fgets($this->pipes[1]);
            if ($line === false) {
                usleep(10_000);
                continue;
            }

            $trimmed = trim($line);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        $stderr = '';
        if ($this->pipes !== null && isset($this->pipes[2]) && is_resource($this->pipes[2])) {
            $stderr = trim((string) stream_get_contents($this->pipes[2]));
        }

        throw new SemanticCodeSearchException(
            'EMBEDDING_FAILED',
            $stderr !== '' ? 'Embedding worker timeout. '.$stderr : 'Embedding worker timeout.'
        );
    }

    /**
     * @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function buildWorkerEnvironment(array $overrides): array
    {
        $baseEnvironment = getenv();
        if (!is_array($baseEnvironment)) {
            $baseEnvironment = [];
        }

        foreach ($_ENV as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }
            $baseEnvironment[$key] = (string) $value;
        }

        foreach (['PATH', 'PATHEXT', 'SystemRoot', 'ComSpec', 'TEMP', 'TMP', 'USERPROFILE', 'APPDATA', 'LOCALAPPDATA'] as $key) {
            if (isset($baseEnvironment[$key]) && $baseEnvironment[$key] !== '') {
                continue;
            }

            $serverValue = $_SERVER[$key] ?? null;
            if (is_scalar($serverValue) && (string) $serverValue !== '') {
                $baseEnvironment[$key] = (string) $serverValue;
            }
        }

        foreach ($overrides as $key => $value) {
            $baseEnvironment[$key] = $value;
        }

        return $baseEnvironment;
    }

    private function shutdownWorker(): void
    {
        if ($this->transport !== null) {
            return;
        }

        if ($this->pipes !== null && isset($this->pipes[0]) && is_resource($this->pipes[0])) {
            @fwrite($this->pipes[0], json_encode(['type' => 'shutdown'], JSON_THROW_ON_ERROR).PHP_EOL);
            @fflush($this->pipes[0]);
        }

        if ($this->pipes !== null) {
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
        }

        if (is_resource($this->process)) {
            $status = proc_get_status($this->process);
            if (($status['running'] ?? false) === true) {
                @proc_terminate($this->process);
            }

            @proc_close($this->process);
        }

        $this->pipes = null;
        $this->process = null;
    }
}
