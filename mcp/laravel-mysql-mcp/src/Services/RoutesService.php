<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

final class RoutesService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?string $method = null, ?string $name = null, ?string $path = null, ?string $domain = null): array
    {
        $routes = [];

        /** @var Route $route */
        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            $methods = array_values(array_filter($route->methods(), static fn (string $item): bool => $item !== 'HEAD'));
            if (empty($methods)) {
                $methods = $route->methods();
            }

            $entry = [
                'domain' => $route->domain(),
                'method' => implode('|', $methods),
                'methods' => $methods,
                'uri' => '/'.ltrim($route->uri(), '/'),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $route->gatherMiddleware(),
            ];

            if ($method !== null && $method !== '' && !$this->containsMethod($methods, $method)) {
                continue;
            }

            if ($name !== null && $name !== '' && stripos((string) $entry['name'], $name) === false) {
                continue;
            }

            if ($path !== null && $path !== '' && stripos((string) $entry['uri'], $path) === false) {
                continue;
            }

            if ($domain !== null && $domain !== '' && stripos((string) $entry['domain'], $domain) === false) {
                continue;
            }

            $routes[] = $entry;
        }

        usort($routes, static fn (array $a, array $b): int => strcmp((string) $a['uri'], (string) $b['uri']));

        return $routes;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function details(?string $name = null, ?string $method = null, ?string $path = null): ?array
    {
        $method = $method !== null ? strtoupper($method) : null;
        $path = $path !== null ? '/'.ltrim($path, '/') : null;

        foreach ($this->list() as $route) {
            if ($name !== null && $name !== '' && $route['name'] === $name) {
                return $this->enrichDetails($route);
            }

            if ($method !== null && $path !== null) {
                $methods = $route['methods'];
                $uri = $route['uri'];
                if ($uri === $path && $this->containsMethod($methods, $method)) {
                    return $this->enrichDetails($route);
                }
            }
        }

        return null;
    }

    /**
     * @param string[] $methods
     */
    private function containsMethod(array $methods, string $needle): bool
    {
        $needle = strtoupper($needle);

        foreach ($methods as $method) {
            if (strtoupper($method) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $route
     *
     * @return array<string, mixed>
     */
    private function enrichDetails(array $route): array
    {
        $action = (string) ($route['action'] ?? '');
        $controller = null;
        $controllerMethod = null;

        if ($action !== '' && $action !== 'Closure' && str_contains($action, '@')) {
            [$controller, $controllerMethod] = explode('@', $action, 2);
        }

        return [
            'domain' => $route['domain'],
            'methods' => $route['methods'],
            'uri' => $route['uri'],
            'name' => $route['name'],
            'action' => $action,
            'controller' => $controller,
            'controller_method' => $controllerMethod,
            'middleware' => $route['middleware'],
            'parameters' => $this->extractParameters((string) $route['uri']),
        ];
    }

    /**
     * @return string[]
     */
    private function extractParameters(string $uri): array
    {
        $matches = [];
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);

        return $matches[1] ?? [];
    }
}