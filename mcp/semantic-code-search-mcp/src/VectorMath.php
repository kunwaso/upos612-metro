<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp;

final class VectorMath
{
    /**
     * @param array<int, float|int> $vector
     * @return array<int, float>
     */
    public static function normalize(array $vector): array
    {
        if ($vector === []) {
            return [];
        }

        $sumSquares = 0.0;
        foreach ($vector as $value) {
            $float = (float) $value;
            $sumSquares += $float * $float;
        }

        if ($sumSquares <= 0.0) {
            return array_fill(0, count($vector), 0.0);
        }

        $magnitude = sqrt($sumSquares);

        return array_map(
            static fn ($value): float => (float) $value / $magnitude,
            array_values($vector)
        );
    }

    /**
     * @param array<int, float> $left
     * @param array<int, float> $right
     */
    public static function dotProduct(array $left, array $right): float
    {
        if (count($left) !== count($right)) {
            throw new SemanticCodeSearchException('INVALID_VECTOR', 'Vector dimensions do not match.');
        }

        $score = 0.0;
        foreach ($left as $index => $value) {
            $score += $value * $right[$index];
        }

        return $score;
    }

    /**
     * @param array<int, float> $vector
     */
    public static function encode(array $vector): string
    {
        if ($vector === []) {
            return '';
        }

        return pack('g*', ...$vector);
    }

    /**
     * @return array<int, float>
     */
    public static function decode(string $blob): array
    {
        if ($blob === '') {
            return [];
        }

        $decoded = unpack('g*', $blob);
        if ($decoded === false) {
            throw new SemanticCodeSearchException('INVALID_VECTOR', 'Unable to decode embedding vector.');
        }

        return array_map('floatval', array_values($decoded));
    }
}
