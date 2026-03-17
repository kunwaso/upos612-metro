<?php

declare(strict_types=1);

namespace AuditWebMcp;

use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Throwable;

final class AuditWebTool
{
    private readonly ScriptRunner $scriptRunner;

    private readonly RouteLister $routeLister;

    public function __construct(
        private readonly string $workspaceRoot,
        private readonly string $serverRoot,
        private readonly string $nodeBinary,
        private readonly string $phpBinary,
    ) {
        $commandRunner = new CommandRunner();
        $this->scriptRunner = new ScriptRunner($commandRunner, $workspaceRoot, $nodeBinary);
        $this->routeLister = new RouteLister($workspaceRoot, $phpBinary, $commandRunner);
    }

    public function audit_web(
        mixed $url,
        mixed $scope = 'single',
        mixed $pathPrefix = null,
        mixed $storage_state_path = null,
        mixed $login_url = null,
        mixed $login_username = null,
        mixed $login_password = null,
        mixed $steps = null,
        mixed $timeout = null,
        mixed $waitUntil = null,
        mixed $waitAfterLoadMs = null,
    ): CallToolResult {
        try {
            $validated = $this->validateInput(
                $url,
                $scope,
                $pathPrefix,
                $storage_state_path,
                $login_url,
                $login_username,
                $login_password,
                $steps,
                $timeout,
                $waitUntil,
                $waitAfterLoadMs
            );

            if ($validated['scope'] === 'single') {
                return $this->runSingle($validated);
            }

            return $this->runPrefix($validated);
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResult('INVALID_ARGUMENT', $exception->getMessage());
        } catch (Throwable $exception) {
            return $this->errorResult('INTERNAL_ERROR', 'audit_web failed unexpectedly.', [
                'runnerError' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function runSingle(array $validated): CallToolResult
    {
        $scriptPath = $this->serverRoot.'/scripts/run-audit-single.js';
        $payload = $this->scriptPayload($validated);
        $run = $this->scriptRunner->runJsonScript($scriptPath, $payload, $this->runnerTimeoutSeconds($validated['timeout']));

        if ($run['exit_code'] !== 0) {
            $runnerError = $this->extractRunnerError($run['json'], $run['stderr']);
            return $this->errorResult('RUNNER_FAILED', 'Audit runner failed before completion.', [
                'runnerError' => $runnerError,
                'exit_code' => $run['exit_code'],
            ]);
        }

        if (!is_array($run['json'])) {
            return $this->errorResult('INVALID_JSON', 'Audit runner returned invalid JSON.', [
                'stdout' => trim($run['stdout']),
            ]);
        }

        $normalized = $this->normalizeSingleResult($run['json'], $validated['url']);

        $summary = $normalized['auditStatus'] === 'pass'
            ? 'Audit passed.'
            : sprintf('Audit failed with %d finding(s).', count($normalized['findings']));

        return new CallToolResult([new TextContent($summary)], false, $normalized);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function runPrefix(array $validated): CallToolResult
    {
        $routes = $this->routeLister->listByPrefix((string) $validated['pathPrefix']);
        if ($routes === []) {
            $empty = [
                'scope' => 'prefix',
                'baseUrl' => $validated['baseUrl'],
                'pathPrefix' => $validated['pathPrefix'],
                'auditStatus' => 'pass',
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'results' => [],
            ];

            return new CallToolResult([new TextContent('No matching routes for prefix.')], false, $empty);
        }

        $needsAuth = false;
        foreach ($routes as $route) {
            if (($route['requires_auth'] ?? false) === true) {
                $needsAuth = true;
                break;
            }
        }

        $authProvided = $validated['storage_state_path'] !== null || $validated['hasCredentials'];
        if ($needsAuth && !$authProvided) {
            return $this->errorResult(
                'AUTH_REQUIRED',
                'Prefix includes auth-protected routes. Provide storage_state_path or complete login credentials.',
                ['pathPrefix' => $validated['pathPrefix']]
            );
        }

        $urls = [];
        foreach ($routes as $route) {
            $urls[] = $this->joinBaseUrl((string) $validated['baseUrl'], (string) $route['uri']);
        }

        $scriptPath = $this->serverRoot.'/scripts/run-audit-batch.js';
        $payload = $this->scriptPayload($validated);
        $payload['urls'] = $urls;
        $run = $this->scriptRunner->runJsonScript($scriptPath, $payload, $this->runnerTimeoutSeconds($validated['timeout'], count($urls)));

        if ($run['exit_code'] !== 0) {
            $runnerError = $this->extractRunnerError($run['json'], $run['stderr']);
            return $this->errorResult('RUNNER_FAILED', 'Batch audit runner failed before completion.', [
                'runnerError' => $runnerError,
                'exit_code' => $run['exit_code'],
            ]);
        }

        if (!is_array($run['json'])) {
            return $this->errorResult('INVALID_JSON', 'Batch audit runner returned invalid JSON.', [
                'stdout' => trim($run['stdout']),
            ]);
        }

        $results = [];
        $passed = 0;
        $failed = 0;
        foreach ($run['json'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized = $this->normalizeSingleResult($item, (string) ($item['url'] ?? ''));
            $results[] = $normalized;
            if ($normalized['auditStatus'] === 'pass') {
                $passed++;
            } else {
                $failed++;
            }
        }

        $structured = [
            'scope' => 'prefix',
            'baseUrl' => $validated['baseUrl'],
            'pathPrefix' => $validated['pathPrefix'],
            'auditStatus' => $failed > 0 ? 'fail' : 'pass',
            'total' => count($results),
            'passed' => $passed,
            'failed' => $failed,
            'results' => $results,
        ];

        $summary = $failed === 0
            ? sprintf('Prefix audit passed for %d route(s).', count($results))
            : sprintf('Prefix audit failed for %d of %d route(s).', $failed, count($results));

        return new CallToolResult([new TextContent($summary)], false, $structured);
    }

    /**
     * @return array{
     *   url: string,
     *   scope: string,
     *   pathPrefix: string|null,
     *   storage_state_path: string|null,
     *   login_url: string|null,
     *   login_username: string|null,
     *   login_password: string|null,
     *   hasCredentials: bool,
     *   steps: array<int, mixed>|null,
     *   timeout: int,
     *   waitUntil: string,
     *   waitAfterLoadMs: int,
     *   baseUrl: string
     * }
     */
    private function validateInput(
        mixed $url,
        mixed $scope,
        mixed $pathPrefix,
        mixed $storage_state_path,
        mixed $login_url,
        mixed $login_username,
        mixed $login_password,
        mixed $steps,
        mixed $timeout,
        mixed $waitUntil,
        mixed $waitAfterLoadMs,
    ): array {
        if (!is_string($url) || trim($url) === '') {
            throw new \InvalidArgumentException('url is required.');
        }
        $url = trim($url);
        if (!$this->isHttpUrl($url)) {
            throw new \InvalidArgumentException('url must be a valid http(s) URL.');
        }

        $scope = is_string($scope) ? strtolower(trim($scope)) : 'single';
        if ($scope === '') {
            $scope = 'single';
        }
        if (!in_array($scope, ['single', 'prefix'], true)) {
            throw new \InvalidArgumentException('scope must be "single" or "prefix".');
        }

        $pathPrefix = $pathPrefix === null ? null : (is_string($pathPrefix) ? trim($pathPrefix) : null);
        if ($scope === 'prefix' && ($pathPrefix === null || $pathPrefix === '')) {
            throw new \InvalidArgumentException('pathPrefix is required when scope is "prefix".');
        }

        $storageStatePath = null;
        if ($storage_state_path !== null) {
            if (!is_string($storage_state_path) || trim($storage_state_path) === '') {
                throw new \InvalidArgumentException('storage_state_path must be a non-empty string.');
            }
            $storageStatePath = trim($storage_state_path);
        }

        $loginUrl = null;
        if ($login_url !== null) {
            if (!is_string($login_url) || trim($login_url) === '') {
                throw new \InvalidArgumentException('login_url must be a non-empty string.');
            }
            $loginUrl = trim($login_url);
            if (!$this->isHttpUrl($loginUrl)) {
                throw new \InvalidArgumentException('login_url must be a valid http(s) URL.');
            }
        }

        $loginUsername = null;
        if ($login_username !== null) {
            if (!is_string($login_username) || trim($login_username) === '') {
                throw new \InvalidArgumentException('login_username must be a non-empty string.');
            }
            $loginUsername = trim($login_username);
        }

        $loginPassword = null;
        if ($login_password !== null) {
            if (!is_string($login_password) || $login_password === '') {
                throw new \InvalidArgumentException('login_password must be a non-empty string.');
            }
            $loginPassword = $login_password;
        }

        $hasAnyCredentialField = $loginUrl !== null || $loginUsername !== null || $loginPassword !== null;
        if ($hasAnyCredentialField && ($loginUrl === null || $loginUsername === null || $loginPassword === null)) {
            throw new \InvalidArgumentException('login_url, login_username, and login_password must all be provided together.');
        }

        $stepsArray = null;
        if ($steps !== null) {
            if (!is_array($steps)) {
                throw new \InvalidArgumentException('steps must be an array when provided.');
            }
            $stepsArray = $steps;
        }

        $timeoutValue = 90;
        if ($timeout !== null) {
            if (!is_int($timeout) && !is_float($timeout) && !(is_string($timeout) && is_numeric($timeout))) {
                throw new \InvalidArgumentException('timeout must be numeric when provided.');
            }
            $timeoutValue = max(1, (int) $timeout);
        }

        $waitUntilValue = 'networkidle';
        if ($waitUntil !== null) {
            if (!is_string($waitUntil) || trim($waitUntil) === '') {
                throw new \InvalidArgumentException('waitUntil must be a non-empty string when provided.');
            }
            $waitUntilValue = trim($waitUntil);
        }

        $waitAfterLoadValue = 2500;
        if ($waitAfterLoadMs !== null) {
            if (!is_int($waitAfterLoadMs) && !is_float($waitAfterLoadMs) && !(is_string($waitAfterLoadMs) && is_numeric($waitAfterLoadMs))) {
                throw new \InvalidArgumentException('waitAfterLoadMs must be numeric when provided.');
            }
            $waitAfterLoadValue = max(0, (int) $waitAfterLoadMs);
        }

        return [
            'url' => $url,
            'scope' => $scope,
            'pathPrefix' => $pathPrefix,
            'storage_state_path' => $storageStatePath,
            'login_url' => $loginUrl,
            'login_username' => $loginUsername,
            'login_password' => $loginPassword,
            'hasCredentials' => $hasAnyCredentialField,
            'steps' => $stepsArray,
            'timeout' => $timeoutValue,
            'waitUntil' => $waitUntilValue,
            'waitAfterLoadMs' => $waitAfterLoadValue,
            'baseUrl' => $this->extractBaseUrl($url),
        ];
    }

    /**
     * @param array<string, mixed> $validated
     *
     * @return array<string, mixed>
     */
    private function scriptPayload(array $validated): array
    {
        $payload = [
            'url' => $validated['url'],
            'waitUntil' => $validated['waitUntil'],
            'waitAfterLoadMs' => $validated['waitAfterLoadMs'],
            'timeoutMs' => $validated['timeout'] * 1000,
        ];

        if ($validated['storage_state_path'] !== null) {
            $payload['storage_state_path'] = $validated['storage_state_path'];
        }
        if ($validated['login_url'] !== null) {
            $payload['login_url'] = $validated['login_url'];
            $payload['login_username'] = $validated['login_username'];
            $payload['login_password'] = $validated['login_password'];
        }
        if (is_array($validated['steps'])) {
            $payload['steps'] = $validated['steps'];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed>|array<int, mixed>|null $json
     */
    private function extractRunnerError(array|null $json, string $stderr): string
    {
        if (is_array($json) && isset($json['runnerError']) && is_string($json['runnerError'])) {
            return $json['runnerError'];
        }

        $stderr = trim($stderr);
        if ($stderr !== '') {
            return $stderr;
        }

        return 'Runner failed without a specific error message.';
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function normalizeSingleResult(array $raw, string $fallbackUrl): array
    {
        $status = isset($raw['auditStatus']) && is_string($raw['auditStatus'])
            ? strtolower($raw['auditStatus'])
            : 'fail';
        if ($status !== 'pass' && $status !== 'fail') {
            $status = 'fail';
        }

        $findings = [];
        if (isset($raw['findings']) && is_array($raw['findings'])) {
            foreach ($raw['findings'] as $finding) {
                if (!is_array($finding)) {
                    continue;
                }
                $findings[] = $finding;
            }
        }

        $normalized = [
            'auditStatus' => $status,
            'url' => isset($raw['url']) && is_string($raw['url']) && $raw['url'] !== '' ? $raw['url'] : $fallbackUrl,
            'findings' => $findings,
        ];

        if (isset($raw['runnerError']) && is_string($raw['runnerError']) && $raw['runnerError'] !== '') {
            $normalized['runnerError'] = $raw['runnerError'];
        }

        if (isset($raw['savedStorageStatePath']) && is_string($raw['savedStorageStatePath']) && $raw['savedStorageStatePath'] !== '') {
            $normalized['savedStorageStatePath'] = $raw['savedStorageStatePath'];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function errorResult(string $code, string $message, array $extra = []): CallToolResult
    {
        return new CallToolResult(
            [new TextContent($message)],
            true,
            array_merge(['code' => $code, 'message' => $message], $extra)
        );
    }

    private function isHttpUrl(string $value): bool
    {
        $parts = parse_url($value);
        if ($parts === false) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        return isset($parts['host']) && is_string($parts['host']) && $parts['host'] !== '';
    }

    private function extractBaseUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $scheme = (string) ($parts['scheme'] ?? 'http');
        $host = (string) ($parts['host'] ?? '');
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = isset($parts['path']) && is_string($parts['path']) ? rtrim($parts['path'], '/') : '';

        return sprintf('%s://%s%s%s', $scheme, $host, $port, $path);
    }

    private function joinBaseUrl(string $baseUrl, string $uri): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($uri, '/');
    }

    private function runnerTimeoutSeconds(int $timeoutSeconds, int $targetCount = 1): int
    {
        $perTargetBuffer = max(45, $timeoutSeconds);
        return max(60, ($perTargetBuffer * max(1, $targetCount)) + 45);
    }
}
