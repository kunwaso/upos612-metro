<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Support;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

final class CommandRunner
{
    public function __construct(private readonly string $projectRoot)
    {
    }

    /**
     * @param string[] $command
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
    public function run(array $command, int $timeoutSeconds = 120, ?string $cwd = null, ?string $input = null): array
    {
        $process = new Process($command, $cwd ?? $this->projectRoot);
        $process->setTimeout($timeoutSeconds);

        if ($input !== null) {
            $process->setInput($input);
        }

        try {
            $process->run();

            return [
                'command' => $process->getCommandLine(),
                'exit_code' => (int) $process->getExitCode(),
                'success' => $process->isSuccessful(),
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
            ];
        } catch (ProcessFailedException $exception) {
            return [
                'command' => $process->getCommandLine(),
                'exit_code' => (int) ($process->getExitCode() ?? 1),
                'success' => false,
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
                'exception' => $exception->getMessage(),
            ];
        } catch (Throwable $exception) {
            return [
                'command' => implode(' ', $command),
                'exit_code' => 1,
                'success' => false,
                'stdout' => '',
                'stderr' => '',
                'exception' => $exception->getMessage(),
            ];
        }
    }
}