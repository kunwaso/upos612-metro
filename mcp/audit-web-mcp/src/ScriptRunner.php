<?php

declare(strict_types=1);

namespace AuditWebMcp;

final class ScriptRunner
{
    public function __construct(
        private readonly CommandRunner $commandRunner,
        private readonly string $workspaceRoot,
        private readonly string $nodeBinary,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *   success: bool,
     *   exit_code: int,
     *   stdout: string,
     *   stderr: string,
     *   json: array<string, mixed>|array<int, mixed>|null,
     *   command: string
     * }
     */
    public function runJsonScript(string $scriptPath, array $payload, int $timeoutSeconds = 120): array
    {
        $run = $this->commandRunner->run(
            [$this->nodeBinary, $scriptPath],
            $this->workspaceRoot,
            $timeoutSeconds,
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $json = $this->extractJson($run['stdout']);

        return [
            'success' => (bool) $run['success'],
            'exit_code' => (int) $run['exit_code'],
            'stdout' => (string) $run['stdout'],
            'stderr' => (string) $run['stderr'],
            'json' => $json,
            'command' => (string) $run['command'],
        ];
    }

    /**
     * @return array<string, mixed>|array<int, mixed>|null
     */
    private function extractJson(string $stdout): array|null
    {
        $trimmed = trim($stdout);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $lines = preg_split('/\r\n|\r|\n/', $trimmed) ?: [];
        for ($index = count($lines) - 1; $index >= 0; $index--) {
            $line = trim($lines[$index]);
            if ($line === '') {
                continue;
            }

            $decodedLine = json_decode($line, true);
            if (is_array($decodedLine)) {
                return $decodedLine;
            }
        }

        $objectStart = strpos($trimmed, '{');
        $objectEnd = strrpos($trimmed, '}');
        if ($objectStart !== false && $objectEnd !== false && $objectEnd > $objectStart) {
            $slice = substr($trimmed, $objectStart, $objectEnd - $objectStart + 1);
            $decodedObject = json_decode($slice, true);
            if (is_array($decodedObject)) {
                return $decodedObject;
            }
        }

        $arrayStart = strpos($trimmed, '[');
        $arrayEnd = strrpos($trimmed, ']');
        if ($arrayStart !== false && $arrayEnd !== false && $arrayEnd > $arrayStart) {
            $slice = substr($trimmed, $arrayStart, $arrayEnd - $arrayStart + 1);
            $decodedArray = json_decode($slice, true);
            if (is_array($decodedArray)) {
                return $decodedArray;
            }
        }

        return null;
    }
}
