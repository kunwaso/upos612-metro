<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Tools;

use LaravelMysqlMcp\Cache\InMemoryTtlCache;
use LaravelMysqlMcp\Safety\EnvAllowlist;
use LaravelMysqlMcp\Safety\Mode;
use LaravelMysqlMcp\Safety\PathGuard;
use LaravelMysqlMcp\Safety\SqlGuard;
use LaravelMysqlMcp\Services\MigrationsService;
use LaravelMysqlMcp\Services\ProjectMapService;
use LaravelMysqlMcp\Services\RoutesService;
use LaravelMysqlMcp\Services\SchemaService;
use LaravelMysqlMcp\Support\CommandRunner;
use LaravelMysqlMcp\Support\OutputLimiter;
use LaravelMysqlMcp\Support\ResponseShape;
use ReflectionClass;
use Throwable;

final class LaravelTools
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $mode,
        private readonly PathGuard $pathGuard,
        private readonly ResponseShape $response,
        private readonly InMemoryTtlCache $cache,
        private readonly OutputLimiter $outputLimiter,
        private readonly CommandRunner $runner,
        private readonly ProjectMapService $projectMapService,
        private readonly RoutesService $routesService,
        private readonly MigrationsService $migrationsService,
        private readonly SchemaService $schemaService,
        private readonly int $maxOutputBytes,
        private readonly int $maxPatchLines,
        private readonly int $ttlRoutes,
        private readonly int $ttlSchema,
        private readonly int $ttlProject,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function project_map(bool $include_files = false, int $max_files = 200): array
    {
        try {
            $cacheKey = sprintf('project_map:%d:%d', $include_files ? 1 : 0, $max_files);
            $cached = $this->cache->remember($cacheKey, $this->ttlProject, fn (): array => $this->projectMapService->buildMap($include_files, $max_files));

            return $this->success('project_map', 'Project map generated.', $cached['value'], cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->error('project_map', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function routes_list(?string $method = null, ?string $name = null, ?string $path = null, ?string $domain = null): array
    {
        try {
            $cacheKey = sprintf('routes_list:%s:%s:%s:%s', $method ?? '', $name ?? '', $path ?? '', $domain ?? '');
            $cached = $this->cache->remember($cacheKey, $this->ttlRoutes, fn (): array => $this->routesService->list($method, $name, $path, $domain));

            return $this->success('routes_list', sprintf('Found %d routes.', count($cached['value'])), ['routes' => $cached['value']], cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->error('routes_list', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function route_details(?string $name = null, ?string $method = null, ?string $path = null): array
    {
        try {
            $details = $this->routesService->details($name, $method, $path);
            if ($details === null) {
                return $this->error('route_details', 'Route not found.');
            }

            return $this->success('route_details', 'Route details loaded.', ['route' => $details]);
        } catch (Throwable $exception) {
            return $this->error('route_details', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function controller_source(string $class, int $max_bytes = 204800): array
    {
        try {
            $file = $this->resolveClassFile($class);
            if ($file === null) {
                return $this->error('controller_source', 'Controller class file not found.');
            }

            $this->pathGuard->assertAllowed($file);
            $content = file_get_contents($file);
            if ($content === false) {
                return $this->error('controller_source', 'Unable to read source file.');
            }

            $payload = $this->outputLimiter->sourcePayload($content, min(max(1, $max_bytes), $this->maxOutputBytes));

            $summary = $payload['truncated']
                ? 'Source exceeded max bytes; returning signatures only.'
                : 'Controller source loaded.';

            return $this->success('controller_source', $summary, [
                'class' => $class,
                'file' => $this->relativePath($file),
                'payload' => $payload,
            ]);
        } catch (Throwable $exception) {
            return $this->error('controller_source', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function controller_methods(string $class): array
    {
        try {
            $resolvedClass = $this->resolveClassName($class);
            if ($resolvedClass === null || !class_exists($resolvedClass)) {
                return $this->error('controller_methods', 'Controller class could not be resolved.');
            }

            $reflection = new ReflectionClass($resolvedClass);
            $methods = [];
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                    continue;
                }

                $parameters = [];
                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->hasType() ? (string) $parameter->getType() : null;
                    $parameters[] = [
                        'name' => $parameter->getName(),
                        'type' => $type,
                        'required' => !$parameter->isOptional(),
                        'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                    ];
                }

                $methods[] = [
                    'name' => $method->getName(),
                    'parameters' => $parameters,
                    'return_type' => $method->hasReturnType() ? (string) $method->getReturnType() : null,
                    'start_line' => $method->getStartLine(),
                    'end_line' => $method->getEndLine(),
                ];
            }

            return $this->success('controller_methods', sprintf('Found %d public methods.', count($methods)), [
                'class' => $resolvedClass,
                'methods' => $methods,
            ]);
        } catch (Throwable $exception) {
            return $this->error('controller_methods', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function find_symbols(string $query, string $type = 'any', int $limit = 50, int $context_lines = 2): array
    {
        try {
            $query = trim($query);
            if ($query === '') {
                return $this->error('find_symbols', 'Query cannot be empty.');
            }

            $limit = max(1, min($limit, 500));
            $context_lines = max(0, min($context_lines, 10));

            $searchText = match (strtolower($type)) {
                'class' => 'class '.$query,
                'method' => 'function '.$query,
                default => $query,
            };

            $command = [
                'rg',
                '--line-number',
                '--column',
                '--no-heading',
                '--color',
                'never',
                '--fixed-strings',
                '--glob',
                '!vendor/**',
                '--glob',
                '!storage/**',
                '--glob',
                '!mcp/laravel-mysql-mcp/vendor/**',
                '--max-count',
                (string) $limit,
                $searchText,
                '.',
            ];

            $run = $this->runner->run($command, 120, $this->projectRoot);
            if (!$run['success'] && trim($run['stdout']) === '') {
                return $this->success('find_symbols', 'No symbol matches found.', [
                    'matches' => [],
                    'exit_code' => $run['exit_code'],
                ]);
            }

            $lines = preg_split('/\r\n|\r|\n/', trim($run['stdout'])) ?: [];
            $matches = [];
            foreach ($lines as $line) {
                if ($line === '') {
                    continue;
                }

                if (preg_match('/^(.+?):(\d+):(\d+):(.*)$/', $line, $match) !== 1) {
                    continue;
                }

                $relative = str_replace('\\', '/', $match[1]);
                $file = $this->pathGuard->resolveInRoot($relative);
                if (!$this->pathGuard->isAllowed($file) || !is_file($file)) {
                    continue;
                }

                $lineNumber = (int) $match[2];
                $matches[] = [
                    'file' => $this->relativePath($file),
                    'line' => $lineNumber,
                    'column' => (int) $match[3],
                    'match' => trim($match[4]),
                    'context' => $this->readContext($file, $lineNumber, $context_lines),
                ];

                if (count($matches) >= $limit) {
                    break;
                }
            }

            return $this->success('find_symbols', sprintf('Found %d symbol matches.', count($matches)), [
                'query' => $query,
                'type' => $type,
                'matches' => $matches,
            ]);
        } catch (Throwable $exception) {
            return $this->error('find_symbols', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function migrations_status(bool $pending_only = false, ?string $database = null): array
    {
        try {
            $rows = $this->migrationsService->status($pending_only, $database, 'all');

            return $this->success('migrations_status', sprintf('Loaded %d migration rows.', count($rows)), [
                'rows' => $rows,
            ]);
        } catch (Throwable $exception) {
            return $this->error('migrations_status', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function migrations_list_files(string $path_scope = 'core'): array
    {
        try {
            $scope = strtolower($path_scope) === 'all' ? 'all' : 'core';
            $rows = $this->migrationsService->listMigrationFiles($scope);

            return $this->success('migrations_list_files', sprintf('Loaded %d migration files.', count($rows)), [
                'path_scope' => $scope,
                'files' => array_map(fn (array $row): array => [
                    'migration' => $row['migration'],
                    'class' => $row['class'],
                    'path' => $this->relativePath((string) $row['path']),
                    'scope' => $row['scope'],
                    'module' => $row['module'],
                    'timestamp' => $row['timestamp'],
                ], $rows),
            ]);
        } catch (Throwable $exception) {
            return $this->error('migrations_list_files', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function migration_show(string $migration, int $max_bytes = 204800): array
    {
        try {
            $found = $this->migrationsService->findMigration($migration, 'all');
            if ($found === null) {
                return $this->error('migration_show', 'Migration file not found.');
            }

            $path = (string) $found['path'];
            $this->pathGuard->assertAllowed($path);

            $content = file_get_contents($path);
            if ($content === false) {
                return $this->error('migration_show', 'Failed to read migration file.');
            }

            $limited = $this->outputLimiter->limitString($content, min(max(1, $max_bytes), $this->maxOutputBytes));

            return $this->success('migration_show', $limited['truncated'] ? 'Migration file truncated to max bytes.' : 'Migration file loaded.', [
                'migration' => $found['migration'],
                'class' => $found['class'],
                'path' => $this->relativePath($path),
                'source' => $limited['content'],
                'bytes' => $limited['original_bytes'],
                'truncated' => $limited['truncated'],
            ]);
        } catch (Throwable $exception) {
            return $this->error('migration_show', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema_snapshot(?string $connection = null): array
    {
        try {
            $cacheKey = 'schema_snapshot:'.($connection ?? 'default');
            $cached = $this->cache->remember($cacheKey, $this->ttlSchema, function () use ($connection): array {
                $support = $this->schemaService->mysqlSupport($connection);

                return [
                    'support' => $support,
                    'snapshot' => ($support['ok'] ?? false) ? $this->schemaService->snapshot($connection) : [],
                ];
            });

            $warnings = [];
            if (($cached['value']['support']['ok'] ?? false) !== true) {
                $warnings[] = $cached['value']['support']['warning'] ?? 'Non-MySQL connection detected.';
            }

            return $this->success('schema_snapshot', 'Schema snapshot loaded.', $cached['value'], $warnings, cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->error('schema_snapshot', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function show_create_table(string $table, ?string $connection = null): array
    {
        try {
            $support = $this->schemaService->mysqlSupport($connection);
            $warnings = [];
            if (($support['ok'] ?? false) !== true) {
                $warnings[] = $support['warning'] ?? 'Non-MySQL connection detected.';

                return $this->success('show_create_table', 'SHOW CREATE TABLE skipped for non-MySQL connection.', [
                    'table' => $table,
                    'support' => $support,
                ], $warnings);
            }

            $data = $this->schemaService->showCreateTable($table, $connection);

            return $this->success('show_create_table', 'SHOW CREATE TABLE completed.', $data, $warnings);
        } catch (Throwable $exception) {
            return $this->error('show_create_table', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema_diff(string $expected_schema_path, string $format = 'auto'): array
    {
        try {
            $this->pathGuard->assertAllowed($expected_schema_path);
            $resolved = $this->pathGuard->resolveInRoot($expected_schema_path);
            if (!is_file($resolved)) {
                return $this->error('schema_diff', 'Expected schema path does not exist.');
            }

            $support = $this->schemaService->mysqlSupport(null);
            if (($support['ok'] ?? false) !== true) {
                return $this->success('schema_diff', 'Schema diff skipped for non-MySQL connection.', [
                    'support' => $support,
                    'expected_schema_path' => $this->relativePath($resolved),
                ], [$support['warning'] ?? 'Non-MySQL connection detected.']);
            }

            $content = file_get_contents($resolved);
            if ($content === false) {
                return $this->error('schema_diff', 'Unable to read expected schema file.');
            }

            $data = $this->schemaService->schemaDiff($content, $format);
            $data['expected_schema_path'] = $this->relativePath($resolved);

            return $this->success('schema_diff', 'Schema diff completed.', $data);
        } catch (Throwable $exception) {
            return $this->error('schema_diff', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function explain_query(string $sql, ?string $connection = null): array
    {
        try {
            $select = SqlGuard::assertExplainableSelect($sql);
            $support = $this->schemaService->mysqlSupport($connection);
            if (($support['ok'] ?? false) !== true) {
                return $this->success('explain_query', 'EXPLAIN skipped for non-MySQL connection.', [
                    'sql' => $select,
                    'support' => $support,
                    'rows' => [],
                ], [$support['warning'] ?? 'Non-MySQL connection detected.']);
            }

            $rows = $this->schemaService->explainQuery($select, $connection);

            $warnings = [];
            if (stripos($select, ' limit ') === false) {
                $warnings[] = 'Query has no LIMIT clause; inspect plan for full scans.';
            }

            return $this->success('explain_query', 'EXPLAIN executed.', [
                'sql' => $select,
                'rows' => $rows,
            ], $warnings);
        } catch (Throwable $exception) {
            return $this->error('explain_query', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function index_health(?string $connection = null, int $min_rows = 1000): array
    {
        try {
            $support = $this->schemaService->mysqlSupport($connection);
            if (($support['ok'] ?? false) !== true) {
                return $this->success('index_health', 'Index health skipped for non-MySQL connection.', [
                    'support' => $support,
                    'recommendations' => [],
                ], [$support['warning'] ?? 'Non-MySQL connection detected.']);
            }

            $data = $this->schemaService->indexHealth($connection, max(1, $min_rows));

            return $this->success('index_health', 'Index health analysis completed.', $data);
        } catch (Throwable $exception) {
            return $this->error('index_health', $exception->getMessage());
        }
    }

    /**
     * @param array<int, string>|null $keys
     *
     * @return array<string, mixed>
     */
    public function config_snapshot(?array $keys = null): array
    {
        try {
            $data = EnvAllowlist::sanitizeConfig($keys ?? []);

            return $this->success('config_snapshot', sprintf('Returned %d config values.', count($data)), [
                'config' => $data,
            ]);
        } catch (Throwable $exception) {
            return $this->error('config_snapshot', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function container_bindings(?string $prefix = null, int $limit = 200): array
    {
        try {
            $limit = max(1, min($limit, 1000));
            $bindings = app()->getBindings();

            $rows = [];
            foreach ($bindings as $abstract => $binding) {
                if ($prefix !== null && $prefix !== '' && stripos((string) $abstract, $prefix) !== 0) {
                    continue;
                }

                $concrete = $binding['concrete'] ?? null;
                $rows[] = [
                    'abstract' => (string) $abstract,
                    'shared' => (bool) ($binding['shared'] ?? false),
                    'concrete' => is_string($concrete) ? $concrete : (is_object($concrete) ? get_class($concrete) : gettype($concrete)),
                ];

                if (count($rows) >= $limit) {
                    break;
                }
            }

            return $this->success('container_bindings', sprintf('Returned %d container bindings.', count($rows)), [
                'bindings' => $rows,
            ]);
        } catch (Throwable $exception) {
            return $this->error('container_bindings', $exception->getMessage());
        }
    }

    /**
     * @param array<int, string>|null $paths
     *
     * @return array<string, mixed>
     */
    public function run_phpstan(?array $paths = null, ?string $level = null, ?string $memory_limit = null): array
    {
        try {
            $binary = $this->findExecutable([
                $this->projectRoot.'/vendor/bin/phpstan',
                $this->projectRoot.'/vendor/bin/phpstan.bat',
            ]);

            if ($binary === null) {
                return $this->success('run_phpstan', 'phpstan executable not found.', [
                    'installed' => false,
                ], ['Install phpstan/larastan in project dependencies to enable this tool.']);
            }

            $command = $this->buildPhpScriptCommand($binary);
            $command[] = 'analyse';
            $command[] = '--no-progress';

            if ($level !== null && $level !== '') {
                $command[] = '--level='.$level;
            }

            if ($memory_limit !== null && $memory_limit !== '') {
                $command[] = '--memory-limit='.$memory_limit;
            }

            if (is_array($paths)) {
                foreach ($paths as $path) {
                    $command[] = $path;
                }
            }

            $result = $this->runner->run($command, 600, $this->projectRoot);

            return $this->success('run_phpstan', $result['success'] ? 'phpstan completed successfully.' : 'phpstan reported issues.', [
                'installed' => true,
                'result' => $result,
            ], $result['success'] ? [] : ['phpstan exited with non-zero status.']);
        } catch (Throwable $exception) {
            return $this->error('run_phpstan', $exception->getMessage());
        }
    }

    /**
     * @param array<int, string>|null $paths
     *
     * @return array<string, mixed>
     */
    public function run_pint(?array $paths = null): array
    {
        try {
            $binary = $this->findExecutable([
                $this->projectRoot.'/vendor/bin/pint',
                $this->projectRoot.'/vendor/bin/pint.bat',
            ]);

            if ($binary === null) {
                return $this->success('run_pint', 'pint executable not found.', [
                    'installed' => false,
                ], ['Install laravel/pint in project dependencies to enable this tool.']);
            }

            $command = $this->buildPhpScriptCommand($binary);
            $command[] = '--test';

            if (is_array($paths)) {
                foreach ($paths as $path) {
                    $command[] = $path;
                }
            }

            $result = $this->runner->run($command, 600, $this->projectRoot);

            return $this->success('run_pint', $result['success'] ? 'pint check passed.' : 'pint check failed.', [
                'installed' => true,
                'result' => $result,
            ], $result['success'] ? [] : ['pint exited with non-zero status.']);
        } catch (Throwable $exception) {
            return $this->error('run_pint', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function run_tests(?string $filter = null, ?string $suite = null, ?string $testsuite = null, ?string $path = null): array
    {
        try {
            $pest = $this->findExecutable([
                $this->projectRoot.'/vendor/bin/pest',
                $this->projectRoot.'/vendor/bin/pest.bat',
            ]);
            $phpunit = $this->findExecutable([
                $this->projectRoot.'/vendor/bin/phpunit',
                $this->projectRoot.'/vendor/bin/phpunit.bat',
            ]);

            $binary = $pest ?? $phpunit;
            if ($binary === null) {
                return $this->error('run_tests', 'No supported test binary found (pest/phpunit).');
            }

            $command = $this->buildPhpScriptCommand($binary);

            if ($filter !== null && $filter !== '') {
                $command[] = '--filter';
                $command[] = $filter;
            }

            if ($suite !== null && $suite !== '') {
                $command[] = '--suite';
                $command[] = $suite;
            }

            if ($testsuite !== null && $testsuite !== '') {
                $command[] = '--testsuite';
                $command[] = $testsuite;
            }

            if ($path !== null && $path !== '') {
                $command[] = $path;
            }

            $result = $this->runner->run($command, 1200, $this->projectRoot);

            return $this->success('run_tests', $result['success'] ? 'Tests passed.' : 'Tests failed.', [
                'runner' => $pest !== null ? 'pest' : 'phpunit',
                'result' => $result,
            ], $result['success'] ? [] : ['Test command exited with non-zero status.']);
        } catch (Throwable $exception) {
            return $this->error('run_tests', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function apply_patch(string $patch, bool $check_only = false): array
    {
        try {
            if (!Mode::isPatch($this->mode)) {
                return $this->error('apply_patch', 'apply_patch is disabled in SAFE mode.');
            }

            $patch = trim($patch);
            if ($patch === '') {
                return $this->error('apply_patch', 'Patch payload cannot be empty.');
            }

            $analysis = $this->analyzePatch($patch);
            if ($analysis['changed_lines'] > $this->maxPatchLines) {
                return $this->error('apply_patch', sprintf('Patch exceeds maximum changed lines (%d).', $this->maxPatchLines), [
                    'changed_lines' => $analysis['changed_lines'],
                    'max_patch_lines' => $this->maxPatchLines,
                ]);
            }

            foreach ($analysis['files'] as $file) {
                if (!$this->pathGuard->isAllowed($this->projectRoot.'/'.$file)) {
                    return $this->error('apply_patch', sprintf('Patch touches a forbidden path: %s', $file));
                }
            }

            $check = $this->runner->run(['git', 'apply', '--check', '--whitespace=nowarn', '-'], 120, $this->projectRoot, $patch);
            if (!$check['success']) {
                return $this->error('apply_patch', 'Patch validation failed.', [
                    'result' => $check,
                ]);
            }

            if ($check_only) {
                return $this->success('apply_patch', 'Patch validation succeeded (check_only=true).', [
                    'check_only' => true,
                    'files' => $analysis['files'],
                    'changed_lines' => $analysis['changed_lines'],
                    'result' => $check,
                ]);
            }

            $apply = $this->runner->run(['git', 'apply', '--whitespace=nowarn', '-'], 120, $this->projectRoot, $patch);
            if (!$apply['success']) {
                return $this->error('apply_patch', 'Patch apply failed.', [
                    'result' => $apply,
                ]);
            }

            return $this->success('apply_patch', 'Patch applied successfully.', [
                'check_only' => false,
                'files' => $analysis['files'],
                'changed_lines' => $analysis['changed_lines'],
                'result' => $apply,
            ]);
        } catch (Throwable $exception) {
            return $this->error('apply_patch', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function git_status(bool $include_untracked = true): array
    {
        try {
            $command = ['git', 'status', '--porcelain'];
            if (!$include_untracked) {
                $command[] = '--untracked-files=no';
            }

            $result = $this->runner->run($command, 60, $this->projectRoot);
            if (!$result['success']) {
                return $this->error('git_status', 'git status failed.', ['result' => $result]);
            }

            $rows = [];
            $lines = preg_split('/\r\n|\r|\n/', trim($result['stdout'])) ?: [];
            foreach ($lines as $line) {
                if ($line === '') {
                    continue;
                }

                $rows[] = [
                    'status' => substr($line, 0, 2),
                    'path' => trim(substr($line, 3)),
                ];
            }

            return $this->success('git_status', empty($rows) ? 'Working tree is clean.' : sprintf('Working tree has %d entries.', count($rows)), [
                'entries' => $rows,
                'clean' => empty($rows),
            ]);
        } catch (Throwable $exception) {
            return $this->error('git_status', $exception->getMessage());
        }
    }

    /**
     * @param array<int, string>|null $keys
     *
     * @return array<string, mixed>
     */
    public function list_env(?array $keys = null): array
    {
        try {
            $data = EnvAllowlist::sanitizeEnv($keys ?? []);

            return $this->success('list_env', sprintf('Returned %d env values.', count($data)), [
                'env' => $data,
            ]);
        } catch (Throwable $exception) {
            return $this->error('list_env', $exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, mixed> $warnings
     * @param array{hit: bool, ttl_sec: int}|null $cache
     *
     * @return array<string, mixed>
     */
    private function success(string $tool, string $summary, array $data = [], array $warnings = [], ?array $cache = null): array
    {
        return $this->response->make($tool, $summary, $data, $warnings, [], $cache);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function error(string $tool, string $message, array $data = []): array
    {
        return $this->response->make($tool, $message, $data, [], [$message]);
    }

    private function resolveClassName(string $class): ?string
    {
        $class = trim($class);
        if ($class === '') {
            return null;
        }

        if (class_exists($class)) {
            return $class;
        }

        $candidates = [];
        $normalized = ltrim($class, '\\');
        $candidates[] = $normalized;

        if (!str_contains($normalized, '\\')) {
            $candidates[] = 'App\\Http\\Controllers\\'.$normalized;
            $candidates[] = 'App\\'.$normalized;
        }

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveClassFile(string $class): ?string
    {
        $resolvedClass = $this->resolveClassName($class);
        if ($resolvedClass !== null) {
            $reflection = new ReflectionClass($resolvedClass);
            $file = $reflection->getFileName();

            return $file !== false ? str_replace('\\', '/', $file) : null;
        }

        $basename = basename(str_replace('\\', '/', $class));
        if (!str_ends_with($basename, '.php')) {
            $basename .= '.php';
        }

        $searchRoots = [
            $this->projectRoot.'/app',
            $this->projectRoot.'/Modules',
        ];

        foreach ($searchRoots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                    continue;
                }

                if ($file->getFilename() === $basename) {
                    return str_replace('\\', '/', $file->getPathname());
                }
            }
        }

        return null;
    }

    private function relativePath(string $absolutePath): string
    {
        $normalized = str_replace('\\', '/', $absolutePath);
        $root = str_replace('\\', '/', $this->projectRoot);

        if ($normalized === $root) {
            return '.';
        }

        if (str_starts_with($normalized, $root.'/')) {
            return substr($normalized, strlen($root) + 1);
        }

        return $normalized;
    }

    /**
     * @return string[]
     */
    private function readContext(string $file, int $lineNumber, int $contextLines): array
    {
        if ($contextLines <= 0 || !is_file($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $start = max(1, $lineNumber - $contextLines);
        $end = min(count($lines), $lineNumber + $contextLines);

        $context = [];
        for ($line = $start; $line <= $end; $line++) {
            $context[] = sprintf('%d:%s', $line, $lines[$line - 1]);
        }

        return $context;
    }

    /**
     * @param string[] $candidates
     */
    private function findExecutable(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return str_replace('\\', '/', $candidate);
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function buildPhpScriptCommand(string $binary): array
    {
        if (str_ends_with(strtolower($binary), '.bat')) {
            return [$binary];
        }

        return [PHP_BINARY, $binary];
    }

    /**
     * @return array{files: array<int, string>, changed_lines: int}
     */
    private function analyzePatch(string $patch): array
    {
        $files = [];
        $changedLines = 0;

        $lines = preg_split('/\r\n|\r|\n/', $patch) ?: [];
        foreach ($lines as $line) {
            if (preg_match('/^diff --git a\/(.+?) b\/(.+)$/', $line, $match) === 1) {
                $path = trim($match[2]);
                if ($path !== '/dev/null' && !in_array($path, $files, true)) {
                    $files[] = $path;
                }
                continue;
            }

            if (str_starts_with($line, '+++ ') || str_starts_with($line, '--- ') || str_starts_with($line, '@@')) {
                continue;
            }

            if (str_starts_with($line, '+') || str_starts_with($line, '-')) {
                $changedLines++;
            }
        }

        sort($files);

        return [
            'files' => $files,
            'changed_lines' => $changedLines,
        ];
    }
}
