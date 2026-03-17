<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SchemaService
{
    /**
     * @return array{ok: bool, driver: string, warning?: string}
     */
    public function mysqlSupport(?string $connection = null): array
    {
        $conn = $this->connection($connection);
        $driver = $conn->getDriverName();

        if (strtolower($driver) !== 'mysql') {
            return [
                'ok' => false,
                'driver' => $driver,
                'warning' => 'Schema tools are MySQL-focused. Current driver is not mysql.',
            ];
        }

        return [
            'ok' => true,
            'driver' => $driver,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(?string $connection = null): array
    {
        $conn = $this->connection($connection);
        $dbName = (string) $conn->getDatabaseName();

        $tables = $conn->select(
            'SELECT TABLE_NAME, ENGINE, TABLE_ROWS, TABLE_COLLATION
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME',
            [$dbName]
        );

        $columns = $conn->select(
            'SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME, ORDINAL_POSITION',
            [$dbName]
        );

        $indexes = $conn->select(
            'SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX',
            [$dbName]
        );

        $fks = $conn->select(
            'SELECT kcu.TABLE_NAME, kcu.COLUMN_NAME, kcu.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
             JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
               ON tc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
              AND tc.TABLE_NAME = kcu.TABLE_NAME
              AND tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
             WHERE kcu.CONSTRAINT_SCHEMA = ?
               AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
               AND tc.CONSTRAINT_TYPE = "FOREIGN KEY"
             ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION',
            [$dbName]
        );

        return [
            'database' => $dbName,
            'tables' => $this->normalizeRows($tables),
            'columns' => $this->normalizeRows($columns),
            'indexes' => $this->normalizeRows($indexes),
            'foreign_keys' => $this->normalizeRows($fks),
            'tables_map' => $this->buildTablesMap($tables, $columns, $indexes, $fks),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showCreateTable(string $table, ?string $connection = null): array
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            throw new InvalidArgumentException('Invalid table name.');
        }

        $conn = $this->connection($connection);
        $rows = $conn->select(sprintf('SHOW CREATE TABLE `%s`', $table));

        if (empty($rows)) {
            throw new InvalidArgumentException('Table not found.');
        }

        $row = (array) $rows[0];
        $createSql = '';
        foreach ($row as $key => $value) {
            if (stripos((string) $key, 'create table') !== false) {
                $createSql = (string) $value;
                break;
            }
        }

        if ($createSql === '' && isset($row['Create Table'])) {
            $createSql = (string) $row['Create Table'];
        }

        return [
            'table' => $table,
            'raw' => $row,
            'create_sql' => $createSql,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schemaDiff(string $expectedSchema, string $format, ?string $connection = null): array
    {
        $current = $this->snapshot($connection);
        $expected = $this->parseExpectedSchema($expectedSchema, $format);

        $currentTables = array_keys($current['tables_map']);
        $expectedTables = array_keys($expected['tables']);

        sort($currentTables);
        sort($expectedTables);

        $missingTables = array_values(array_diff($expectedTables, $currentTables));
        $extraTables = array_values(array_diff($currentTables, $expectedTables));

        $columnDiff = [];
        foreach ($expected['tables'] as $table => $tableData) {
            if (!isset($current['tables_map'][$table])) {
                continue;
            }

            $expectedColumns = array_keys($tableData['columns']);
            $currentColumns = array_keys($current['tables_map'][$table]['columns']);

            sort($expectedColumns);
            sort($currentColumns);

            $missingColumns = array_values(array_diff($expectedColumns, $currentColumns));
            $extraColumns = array_values(array_diff($currentColumns, $expectedColumns));

            if (!empty($missingColumns) || !empty($extraColumns)) {
                $columnDiff[$table] = [
                    'missing_columns' => $missingColumns,
                    'extra_columns' => $extraColumns,
                ];
            }
        }

        return [
            'format' => $expected['format'],
            'missing_tables' => $missingTables,
            'extra_tables' => $extraTables,
            'column_diff' => $columnDiff,
            'expected_tables_count' => count($expected['tables']),
            'current_tables_count' => count($current['tables_map']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function explainQuery(string $selectSql, ?string $connection = null): array
    {
        $conn = $this->connection($connection);

        return $this->normalizeRows($conn->select('EXPLAIN '.$selectSql));
    }

    /**
     * @return array<string, mixed>
     */
    public function indexHealth(?string $connection = null, int $minRows = 1000): array
    {
        $snapshot = $this->snapshot($connection);
        $tables = $snapshot['tables_map'];

        $recommendations = [];
        foreach ($tables as $tableName => $table) {
            $rowCount = (int) ($table['meta']['TABLE_ROWS'] ?? 0);
            $indexes = $table['indexes'];
            $columns = array_keys($table['columns']);

            if (!isset($indexes['PRIMARY'])) {
                $recommendations[] = [
                    'table' => $tableName,
                    'severity' => 'high',
                    'issue' => 'missing_primary_key',
                    'message' => 'Table has no PRIMARY KEY index.',
                ];
            }

            foreach ($table['foreign_keys'] as $fk) {
                $fkColumn = (string) ($fk['COLUMN_NAME'] ?? '');
                if ($fkColumn === '') {
                    continue;
                }

                $covered = false;
                foreach ($indexes as $indexColumns) {
                    if (in_array($fkColumn, $indexColumns, true)) {
                        $covered = true;
                        break;
                    }
                }

                if (!$covered) {
                    $recommendations[] = [
                        'table' => $tableName,
                        'severity' => 'medium',
                        'issue' => 'fk_without_index',
                        'column' => $fkColumn,
                        'message' => 'Foreign key column is not indexed.',
                    ];
                }
            }

            if ($rowCount >= $minRows && count($indexes) <= 1 && count($columns) > 4) {
                $recommendations[] = [
                    'table' => $tableName,
                    'severity' => 'low',
                    'issue' => 'few_indexes_on_large_table',
                    'rows' => $rowCount,
                    'message' => 'Large table has limited indexing.',
                ];
            }

            $seenSignatures = [];
            foreach ($indexes as $indexName => $indexColumns) {
                $signature = implode('|', $indexColumns);
                if (isset($seenSignatures[$signature])) {
                    $recommendations[] = [
                        'table' => $tableName,
                        'severity' => 'low',
                        'issue' => 'duplicate_index',
                        'index' => $indexName,
                        'duplicate_of' => $seenSignatures[$signature],
                        'message' => 'Duplicate index signature detected.',
                    ];
                } else {
                    $seenSignatures[$signature] = $indexName;
                }
            }
        }

        return [
            'recommendations' => $recommendations,
            'recommendation_count' => count($recommendations),
        ];
    }

    private function connection(?string $connection): Connection
    {
        if ($connection !== null && $connection !== '') {
            return DB::connection($connection);
        }

        return DB::connection();
    }

    /**
     * @param array<int, object> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(static fn (object $row): array => (array) $row, $rows);
    }

    /**
     * @param array<int, object> $tables
     * @param array<int, object> $columns
     * @param array<int, object> $indexes
     * @param array<int, object> $fks
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildTablesMap(array $tables, array $columns, array $indexes, array $fks): array
    {
        $map = [];
        foreach ($tables as $row) {
            $tableName = (string) $row->TABLE_NAME;
            $map[$tableName] = [
                'meta' => (array) $row,
                'columns' => [],
                'indexes' => [],
                'foreign_keys' => [],
            ];
        }

        foreach ($columns as $row) {
            $tableName = (string) $row->TABLE_NAME;
            $columnName = (string) $row->COLUMN_NAME;
            if (!isset($map[$tableName])) {
                continue;
            }

            $map[$tableName]['columns'][$columnName] = (array) $row;
        }

        foreach ($indexes as $row) {
            $tableName = (string) $row->TABLE_NAME;
            $indexName = (string) $row->INDEX_NAME;
            if (!isset($map[$tableName])) {
                continue;
            }

            if (!isset($map[$tableName]['indexes'][$indexName])) {
                $map[$tableName]['indexes'][$indexName] = [];
            }

            $map[$tableName]['indexes'][$indexName][] = (string) $row->COLUMN_NAME;
        }

        foreach ($fks as $row) {
            $tableName = (string) $row->TABLE_NAME;
            if (!isset($map[$tableName])) {
                continue;
            }

            $map[$tableName]['foreign_keys'][] = (array) $row;
        }

        return $map;
    }

    /**
     * @return array{format: string, tables: array<string, array{columns: array<string, bool>}>}
     */
    private function parseExpectedSchema(string $expectedSchema, string $format): array
    {
        if ($format === 'json' || ($format === 'auto' && $this->looksLikeJson($expectedSchema))) {
            $decoded = json_decode($expectedSchema, true);
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('Invalid expected schema JSON.');
            }

            $tables = [];
            $sourceTables = $decoded['tables'] ?? $decoded;
            if (!is_array($sourceTables)) {
                throw new InvalidArgumentException('Invalid expected schema JSON structure.');
            }

            foreach ($sourceTables as $table => $tableData) {
                if (!is_string($table)) {
                    continue;
                }

                $columns = $tableData['columns'] ?? [];
                $columnMap = [];

                if (is_array($columns)) {
                    foreach ($columns as $column => $value) {
                        if (is_int($column) && is_string($value)) {
                            $columnMap[$value] = true;
                        } elseif (is_string($column)) {
                            $columnMap[$column] = true;
                        }
                    }
                }

                $tables[$table] = ['columns' => $columnMap];
            }

            return ['format' => 'json', 'tables' => $tables];
        }

        return [
            'format' => 'sql',
            'tables' => $this->parseCreateTableSql($expectedSchema),
        ];
    }

    private function looksLikeJson(string $content): bool
    {
        $trimmed = ltrim($content);

        return $trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[');
    }

    /**
     * @return array<string, array{columns: array<string, bool>}>
     */
    private function parseCreateTableSql(string $sql): array
    {
        $tables = [];

        if (preg_match_all('/CREATE\s+TABLE\s+`?([A-Za-z0-9_]+)`?\s*\((.*?)\)\s*(ENGINE|COMMENT|;)/is', $sql, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                $table = $match[1];
                $body = $match[2];

                $columnMap = [];
                $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
                foreach ($lines as $line) {
                    if (preg_match('/^\s*`?([A-Za-z0-9_]+)`?\s+/', trim($line), $columnMatch) === 1) {
                        $name = $columnMatch[1];
                        $upper = strtoupper($name);
                        if (!in_array($upper, ['PRIMARY', 'KEY', 'UNIQUE', 'CONSTRAINT', 'INDEX', 'FOREIGN'], true)) {
                            $columnMap[$name] = true;
                        }
                    }
                }

                $tables[$table] = ['columns' => $columnMap];
            }
        }

        return $tables;
    }
}