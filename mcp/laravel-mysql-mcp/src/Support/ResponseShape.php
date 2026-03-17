<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Support;

use DateTimeImmutable;

final class ResponseShape
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $laravelVersion,
        private readonly string $mode,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, mixed> $warnings
     * @param array<int, mixed> $errors
     * @param array{hit: bool, ttl_sec: int}|null $cache
     *
     * @return array<string, mixed>
     */
    public function make(
        string $tool,
        string $summary,
        array $data = [],
        array $warnings = [],
        array $errors = [],
        ?array $cache = null,
    ): array {
        $meta = [
            'tool' => $tool,
            'timestamp' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            'project_root' => $this->projectRoot,
            'laravel_version' => $this->laravelVersion,
            'php_version' => PHP_VERSION,
            'mode' => $this->mode,
        ];

        if ($cache !== null) {
            $meta['cache'] = [
                'hit' => (bool) ($cache['hit'] ?? false),
                'ttl_sec' => (int) ($cache['ttl_sec'] ?? 0),
            ];
        }

        return [
            'meta' => $meta,
            'summary' => $summary,
            'data' => $data,
            'warnings' => array_values($warnings),
            'errors' => array_values($errors),
        ];
    }
}