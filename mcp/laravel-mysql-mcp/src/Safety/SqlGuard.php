<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Safety;

use InvalidArgumentException;

final class SqlGuard
{
    public static function assertExplainableSelect(string $sql): string
    {
        $normalized = trim($sql);
        if ($normalized === '') {
            throw new InvalidArgumentException('SQL cannot be empty.');
        }

        $withoutTrailingSemicolon = rtrim($normalized, " \t\n\r\0\x0B;");
        if (str_contains($withoutTrailingSemicolon, ';')) {
            throw new InvalidArgumentException('Only a single SQL statement is allowed.');
        }

        $leading = ltrim($withoutTrailingSemicolon);
        $upper = strtoupper($leading);
        if (!(str_starts_with($upper, 'SELECT ') || str_starts_with($upper, 'WITH '))) {
            throw new InvalidArgumentException('Only SELECT/CTE queries are allowed for EXPLAIN.');
        }

        $blocked = [
            ' INSERT ',
            ' UPDATE ',
            ' DELETE ',
            ' DROP ',
            ' ALTER ',
            ' CREATE ',
            ' TRUNCATE ',
            ' REPLACE ',
            ' GRANT ',
            ' REVOKE ',
            ' MERGE ',
        ];

        $probe = ' '.preg_replace('/\s+/', ' ', strtoupper($withoutTrailingSemicolon)).' ';
        foreach ($blocked as $token) {
            if (str_contains($probe, $token)) {
                throw new InvalidArgumentException('Blocked SQL keyword detected for EXPLAIN.');
            }
        }

        return $withoutTrailingSemicolon;
    }
}