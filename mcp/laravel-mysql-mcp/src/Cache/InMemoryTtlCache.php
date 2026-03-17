<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Cache;

final class InMemoryTtlCache
{
    /**
     * @var array<string, array{expires_at: float, value: mixed}>
     */
    private array $items = [];

    /**
     * @param callable(): mixed $resolver
     *
     * @return array{value: mixed, cache: array{hit: bool, ttl_sec: int}}
     */
    public function remember(string $key, int $ttlSeconds, callable $resolver): array
    {
        $now = microtime(true);

        if (isset($this->items[$key])) {
            $entry = $this->items[$key];
            if ($entry['expires_at'] > $now) {
                return [
                    'value' => $entry['value'],
                    'cache' => [
                        'hit' => true,
                        'ttl_sec' => max(1, (int) floor($entry['expires_at'] - $now)),
                    ],
                ];
            }
        }

        $value = $resolver();
        $this->items[$key] = [
            'value' => $value,
            'expires_at' => $now + max(1, $ttlSeconds),
        ];

        return [
            'value' => $value,
            'cache' => [
                'hit' => false,
                'ttl_sec' => max(1, $ttlSeconds),
            ],
        ];
    }
}