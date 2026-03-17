<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Safety;

final class Mode
{
    public const SAFE = 'SAFE';
    public const PATCH = 'PATCH';

    public static function resolve(?string $mode): string
    {
        $value = strtoupper(trim((string) $mode));

        return match ($value) {
            self::PATCH => self::PATCH,
            self::SAFE, '' => self::SAFE,
            default => self::SAFE,
        };
    }

    public static function isPatch(string $mode): bool
    {
        return strtoupper($mode) === self::PATCH;
    }
}