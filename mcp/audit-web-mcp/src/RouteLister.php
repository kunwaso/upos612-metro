<?php

declare(strict_types=1);

namespace AuditWebMcp;

use RuntimeException;
use Throwable;

final class RouteLister
{
    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $bootstrapCache = null;

    public function __construct(
        private readonly string $workspaceRoot,
        private readonly string $phpBinary,
        private readonly CommandRunner $commandRunner,
    ) {
    }

    /**
     * @param array<string, string> $seedValues
     *
     * @return array<int, array{
     *   uri: string,
     *   methods: array<int, string>,
     *   middleware: array<int, string>,
     *   requires_auth: bool
     * }>
     */
    public function listByPrefix(string $pathPrefix, array $seedValues = []): array
    {
        $routes = [];
        try {
            $routes = $this->listViaArtisanJson();
        } catch (Throwable) {
            $routes = $this->listViaBootstrap();
        }

        $normalizedPrefix = $this->normalizePrefix($pathPrefix);
        $output = [];

        foreach ($routes as $route) {
            $uri = $this->normalizeUri((string) ($route['uri'] ?? '/'));
            $methods = $this->extractMethods($route['method'] ?? $route['methods'] ?? null);
            if ($methods === []) {
                continue;
            }

            $middleware = $this->extractMiddleware($route['middleware'] ?? null);
            if (!$this->isWebRoute($middleware)) {
                continue;
            }

            if (!$this->matchesPrefix($uri, $normalizedPrefix)) {
                continue;
            }

            if ($this->hasRequiredParams($uri, $seedValues)) {
                continue;
            }

            $output[] = [
                'uri' => $uri,
                'methods' => $methods,
                'middleware' => $middleware,
                'requires_auth' => $this->requiresAuth($middleware),
            ];
        }

        usort(
            $output,
            static fn (array $left, array $right): int => strcmp((string) $left['uri'], (string) $right['uri'])
        );

        return $output;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listViaArtisanJson(): array
    {
        $run = $this->commandRunner->run(
            [
                $this->phpBinary,
                '-d',
                'error_reporting=24575',
                '-d',
                'display_errors=0',
                'artisan',
                'route:list',
                '--json',
            ],
            $this->workspaceRoot,
            180
        );

        if (!$run['success']) {
            throw new RuntimeException('route:list --json failed.');
        }

        $decoded = json_decode(trim((string) $run['stdout']), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('route:list --json output was not valid JSON.');
        }

        return $decoded;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listViaBootstrap(): array
    {
        if ($this->bootstrapCache !== null) {
            return $this->bootstrapCache;
        }

        $autoloadPath = $this->workspaceRoot.'/vendor/autoload.php';
        $bootstrapPath = $this->workspaceRoot.'/bootstrap/app.php';

        if (!is_file($autoloadPath) || !is_file($bootstrapPath)) {
            throw new RuntimeException('Cannot bootstrap Laravel application for route fallback.');
        }

        $previousErrorReporting = error_reporting();
        $previousDisplayErrors = ini_get('display_errors');
        error_reporting($previousErrorReporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        ini_set('display_errors', '0');

        try {
            /** @noinspection PhpIncludeInspection */
            require_once $autoloadPath;

            /** @var \Illuminate\Foundation\Application $app */
            $app = require $bootstrapPath;
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
        } finally {
            error_reporting($previousErrorReporting);
            if ($previousDisplayErrors !== false) {
                ini_set('display_errors', (string) $previousDisplayErrors);
            }
        }

        $routes = \Illuminate\Support\Facades\Route::getRoutes()->getRoutes();
        $output = [];

        foreach ($routes as $route) {
            $methods = array_values(array_filter($route->methods(), static fn (string $method): bool => $method !== 'HEAD'));
            if ($methods === []) {
                $methods = $route->methods();
            }

            $output[] = [
                'uri' => $route->uri(),
                'methods' => $methods,
                'middleware' => $route->gatherMiddleware(),
            ];
        }

        $this->bootstrapCache = $output;

        return $output;
    }

    private function normalizePrefix(string $prefix): string
    {
        $trimmed = trim($prefix);
        if ($trimmed === '') {
            return '';
        }

        return trim($trimmed, '/');
    }

    private function normalizeUri(string $uri): string
    {
        $trimmed = trim($uri);
        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        return '/'.ltrim($trimmed, '/');
    }

    /**
     * @param mixed $value
     *
     * @return array<int, string>
     */
    private function extractMethods(mixed $value): array
    {
        $methods = [];

        if (is_string($value)) {
            $methods = preg_split('/[|,]/', $value) ?: [];
        } elseif (is_array($value)) {
            $methods = $value;
        }

        $normalized = [];
        foreach ($methods as $method) {
            if (!is_string($method)) {
                continue;
            }

            $method = strtoupper(trim($method));
            if ($method !== 'GET' && $method !== 'HEAD') {
                continue;
            }

            if (!in_array($method, $normalized, true)) {
                $normalized[] = $method;
            }
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     *
     * @return array<int, string>
     */
    private function extractMiddleware(mixed $value): array
    {
        $middleware = [];
        if (is_string($value)) {
            $middleware = preg_split('/[|,]/', $value) ?: [];
        } elseif (is_array($value)) {
            $middleware = $value;
        }

        $normalized = [];
        foreach ($middleware as $item) {
            if (!is_string($item)) {
                continue;
            }

            $trimmed = trim($item);
            if ($trimmed === '') {
                continue;
            }

            $normalized[] = $trimmed;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<int, string> $middleware
     */
    private function isWebRoute(array $middleware): bool
    {
        if ($middleware === []) {
            return true;
        }

        $hasApi = false;
        $hasWeb = false;
        foreach ($middleware as $item) {
            if ($item === 'web' || str_starts_with($item, 'web:')) {
                $hasWeb = true;
            }
            if ($item === 'api' || str_starts_with($item, 'api:')) {
                $hasApi = true;
            }
        }

        if ($hasWeb) {
            return true;
        }

        return !$hasApi;
    }

    private function matchesPrefix(string $uri, string $pathPrefix): bool
    {
        if ($pathPrefix === '') {
            return true;
        }

        $normalizedPrefix = '/'.ltrim($pathPrefix, '/');
        if ($uri === $normalizedPrefix) {
            return true;
        }

        return str_starts_with($uri, $normalizedPrefix.'/');
    }

    /**
     * @param array<string, string> $seedValues
     */
    private function hasRequiredParams(string $uri, array $seedValues): bool
    {
        if (!str_contains($uri, '{')) {
            return false;
        }

        preg_match_all('/\{([^}]+)\}/', $uri, $matches);
        $parameters = $matches[1] ?? [];
        foreach ($parameters as $parameter) {
            if (!is_string($parameter) || $parameter === '') {
                continue;
            }

            $name = rtrim($parameter, '?');
            $isOptional = str_ends_with($parameter, '?');
            if (!$isOptional && !array_key_exists($name, $seedValues)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $middleware
     */
    private function requiresAuth(array $middleware): bool
    {
        foreach ($middleware as $item) {
            if ($item === 'auth' || str_starts_with($item, 'auth:')) {
                return true;
            }
        }

        return false;
    }
}
