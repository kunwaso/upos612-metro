<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Support;

final class OutputLimiter
{
    /**
     * @return array{content: string, truncated: bool, original_bytes: int}
     */
    public function limitString(string $content, int $maxBytes): array
    {
        $maxBytes = max(1, $maxBytes);
        $bytes = strlen($content);

        if ($bytes <= $maxBytes) {
            return [
                'content' => $content,
                'truncated' => false,
                'original_bytes' => $bytes,
            ];
        }

        return [
            'content' => substr($content, 0, $maxBytes),
            'truncated' => true,
            'original_bytes' => $bytes,
        ];
    }

    /**
     * @return string[]
     */
    public function extractPhpMethodSignatures(string $source, int $max = 200): array
    {
        $matches = [];
        preg_match_all('/^\s*(public|protected|private)\s+function\s+([A-Za-z0-9_]+)\s*\(([^)]*)\)/m', $source, $matches, PREG_SET_ORDER);

        $signatures = [];
        foreach ($matches as $match) {
            $signatures[] = sprintf('%s function %s(%s)', $match[1], $match[2], trim($match[3]));
            if (count($signatures) >= $max) {
                break;
            }
        }

        return $signatures;
    }

    /**
     * @return array{full_source?: string, signatures?: string[], bytes: int, truncated: bool}
     */
    public function sourcePayload(string $source, int $maxBytes): array
    {
        $limited = $this->limitString($source, $maxBytes);

        if (!$limited['truncated']) {
            return [
                'full_source' => $limited['content'],
                'bytes' => $limited['original_bytes'],
                'truncated' => false,
            ];
        }

        return [
            'signatures' => $this->extractPhpMethodSignatures($source),
            'bytes' => $limited['original_bytes'],
            'truncated' => true,
        ];
    }
}