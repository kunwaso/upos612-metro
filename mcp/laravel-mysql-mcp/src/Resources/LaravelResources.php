<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Resources;

use LaravelMysqlMcp\Cache\InMemoryTtlCache;
use LaravelMysqlMcp\Services\MigrationsService;
use LaravelMysqlMcp\Services\ProjectMapService;
use LaravelMysqlMcp\Services\RoutesService;
use LaravelMysqlMcp\Services\SchemaService;
use LaravelMysqlMcp\Support\ResponseShape;
use Throwable;

final class LaravelResources
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly InMemoryTtlCache $cache,
        private readonly ResponseShape $response,
        private readonly ProjectMapService $projectMapService,
        private readonly RoutesService $routesService,
        private readonly MigrationsService $migrationsService,
        private readonly SchemaService $schemaService,
        private readonly int $ttlRoutes,
        private readonly int $ttlSchema,
        private readonly int $ttlProject,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function project_map(string $uri = ''): array
    {
        try {
            $cached = $this->cache->remember('resource:project_map', $this->ttlProject, fn (): array => $this->projectMapService->buildMap(false, 200));

            return $this->response->make('resource://project/map', 'Project map resource loaded.', $cached['value'], cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->response->make('resource://project/map', $exception->getMessage(), [], [], [$exception->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function routes(string $uri = ''): array
    {
        try {
            $cached = $this->cache->remember('resource:routes', $this->ttlRoutes, fn (): array => $this->routesService->list());

            return $this->response->make('resource://routes', sprintf('Loaded %d routes.', count($cached['value'])), [
                'routes' => $cached['value'],
            ], cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->response->make('resource://routes', $exception->getMessage(), [], [], [$exception->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema_snapshot(string $uri = ''): array
    {
        try {
            $cached = $this->cache->remember('resource:schema_snapshot', $this->ttlSchema, function (): array {
                $support = $this->schemaService->mysqlSupport(null);

                return [
                    'support' => $support,
                    'snapshot' => ($support['ok'] ?? false) ? $this->schemaService->snapshot(null) : [],
                ];
            });

            $warnings = [];
            if (($cached['value']['support']['ok'] ?? false) !== true) {
                $warnings[] = $cached['value']['support']['warning'] ?? 'Non-MySQL driver detected.';
            }

            return $this->response->make('resource://schema/snapshot', 'Schema snapshot resource loaded.', $cached['value'], $warnings, [], $cached['cache']);
        } catch (Throwable $exception) {
            return $this->response->make('resource://schema/snapshot', $exception->getMessage(), [], [], [$exception->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function migrations_status(string $uri = ''): array
    {
        try {
            $cached = $this->cache->remember('resource:migrations_status', $this->ttlRoutes, fn (): array => $this->migrationsService->status(false, null, 'all'));

            return $this->response->make('resource://migrations/status', sprintf('Loaded %d migration rows.', count($cached['value'])), [
                'rows' => $cached['value'],
            ], cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->response->make('resource://migrations/status', $exception->getMessage(), [], [], [$exception->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function composer(string $uri = ''): array
    {
        try {
            $cached = $this->cache->remember('resource:composer', 120, function (): array {
                $composerPath = $this->projectRoot.'/composer.json';
                $content = is_file($composerPath) ? file_get_contents($composerPath) : false;
                $json = is_string($content) ? json_decode($content, true) : [];

                if (!is_array($json)) {
                    $json = [];
                }

                return [
                    'name' => $json['name'] ?? null,
                    'description' => $json['description'] ?? null,
                    'php_requirement' => $json['require']['php'] ?? null,
                    'laravel_requirement' => $json['require']['laravel/framework'] ?? null,
                    'dependencies' => $json['require'] ?? [],
                ];
            });

            return $this->response->make('resource://composer', 'Composer resource loaded.', $cached['value'], cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->response->make('resource://composer', $exception->getMessage(), [], [], [$exception->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function conventions(string $uri = ''): array
    {
        try {
            $cached = $this->cache->remember('resource:conventions', 120, function (): array {
                $paths = [
                    $this->projectRoot.'/AGENTS.md',
                    $this->projectRoot.'/ai/laravel-conventions.md',
                    $this->projectRoot.'/ai/security-and-auth.md',
                ];

                $documents = [];
                foreach ($paths as $path) {
                    if (!is_file($path)) {
                        continue;
                    }

                    $content = file_get_contents($path);
                    if ($content === false) {
                        continue;
                    }

                    $documents[] = [
                        'path' => str_replace('\\', '/', substr($path, strlen($this->projectRoot) + 1)),
                        'excerpt' => substr($content, 0, 4000),
                    ];
                }

                return [
                    'documents' => $documents,
                ];
            });

            return $this->response->make('resource://conventions', 'Conventions resource loaded.', $cached['value'], cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->response->make('resource://conventions', $exception->getMessage(), [], [], [$exception->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function examples_golden(string $uri = ''): array
    {
        try {
            $cached = $this->cache->remember('resource:examples_golden', 120, function (): array {
                $routes = $this->routesService->list();
                $examples = array_slice($routes, 0, 5);

                return [
                    'example_routes' => $examples,
                    'example_paths' => [
                        'app/Http/Controllers',
                        'app/Utils',
                        'routes/web.php',
                        'resources/views',
                    ],
                ];
            });

            return $this->response->make('resource://examples/golden', 'Golden examples resource loaded.', $cached['value'], cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->response->make('resource://examples/golden', $exception->getMessage(), [], [], [$exception->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function prompts_catalog(string $uri = ''): array
    {
        try {
            $cached = $this->cache->remember('resource:prompts_catalog', 120, fn (): array => [
                'prompts' => [
                    'optimize_controller' => [
                        'description' => 'Inspect routes/controller/schema and propose minimal safe optimization diffs.',
                    ],
                    'migration_safety_check' => [
                        'description' => 'Assess migration risks, pending/running state, and rollout safety checks.',
                    ],
                    'perf_tuning_sql' => [
                        'description' => 'Analyze SQL plans and index strategy with explain/index health inputs.',
                    ],
                ],
            ]);

            return $this->response->make('resource://prompts/catalog', 'Prompts catalog resource loaded.', $cached['value'], cache: $cached['cache']);
        } catch (Throwable $exception) {
            return $this->response->make('resource://prompts/catalog', $exception->getMessage(), [], [], [$exception->getMessage()]);
        }
    }
}
