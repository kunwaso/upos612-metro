<?php

declare(strict_types=1);

namespace AuditWebMcp;

use Symfony\Component\Process\Process;
use Throwable;

final class CommandRunner
{
    /**
     * @param string[] $command
     * @param array<string, string> $environment
     *
     * @return array{
     *   command: string,
     *   exit_code: int,
     *   success: bool,
     *   stdout: string,
     *   stderr: string,
     *   exception?: string
     * }
     */
    public function run(
        array $command,
        string $workingDirectory,
        int $timeoutSeconds = 120,
        ?string $input = null,
        array $environment = []
    ): array {
        $process = new Process($command, $workingDirectory, $environment === [] ? null : $environment);
        $process->setTimeout(max(1, $timeoutSeconds));

        if ($input !== null) {
            $process->setInput($input);
        }

        try {
            $process->run();

            return [
                'command' => $process->getCommandLine(),
                'exit_code' => (int) ($process->getExitCode() ?? 1),
                'success' => $process->isSuccessful(),
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
            ];
        } catch (Throwable $exception) {
            return [
                'command' => implode(' ', $command),
                'exit_code' => 1,
                'success' => false,
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
                'exception' => $exception->getMessage(),
            ];
        }
    }
}
