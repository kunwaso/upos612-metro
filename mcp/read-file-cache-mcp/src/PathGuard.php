<?php

declare(strict_types=1);

namespace ReadFileCacheMcp;

final class PathGuard
{
    /**
     * @var string[]
     */
    private array $deniedSegments = [
        '.git',
        'vendor',
        'storage',
        'node_modules',
    ];

    /**
     * @var string[]
     */
    private array $deniedExtensions = [
        'pem',
        'key',
        'p12',
        'crt',
    ];

    private string $workspaceRoot;

    private bool $caseInsensitive;

    public function __construct(string $workspaceRoot)
    {
        $resolved = realpath($workspaceRoot);
        if ($resolved === false || !is_dir($resolved)) {
            throw new ReadFileException('WORKSPACE_ROOT_INVALID', 'Workspace root is invalid.');
        }

        $this->workspaceRoot = $this->normalizePath($resolved);
        $this->caseInsensitive = DIRECTORY_SEPARATOR === '\\' || preg_match('/^[A-Za-z]:/', $this->workspaceRoot) === 1;
    }

    public function workspaceRoot(): string
    {
        return $this->workspaceRoot;
    }

    public function resolve(string $path): string
    {
        $path = $this->normalizeInputPath($path);

        $candidate = $this->isAbsolutePath($path)
            ? $this->normalizePath($path)
            : $this->normalizePath($this->workspaceRoot.'/'.$path);

        $existing = realpath($candidate);
        if ($existing !== false) {
            return $this->normalizePath($existing);
        }

        return $candidate;
    }

    public function assertReadableFile(string $path): string
    {
        $originalPath = $this->normalizeInputPath($path);
        $resolved = $this->resolve($originalPath);

        $this->assertWithinWorkspace($resolved, $originalPath);
        $this->assertAllowedPath($resolved, $originalPath);

        if (!file_exists($resolved)) {
            throw new ReadFileException('FILE_NOT_FOUND', 'File not found.', $originalPath);
        }

        $canonical = realpath($resolved);
        if ($canonical === false) {
            throw new ReadFileException('READ_FAILED', 'Unable to resolve file.', $originalPath);
        }

        $canonical = $this->normalizePath($canonical);
        $this->assertWithinWorkspace($canonical, $originalPath);
        $this->assertAllowedPath($canonical, $originalPath);

        if (is_dir($canonical) || !is_file($canonical)) {
            throw new ReadFileException('NOT_A_FILE', 'Path does not point to a regular file.', $originalPath);
        }

        if (!is_readable($canonical)) {
            throw new ReadFileException('READ_FAILED', 'File is not readable.', $originalPath);
        }

        return $canonical;
    }

    public function relativePath(string $path): string
    {
        $resolved = $this->normalizePath($path);
        if (!$this->isWithin($resolved, $this->workspaceRoot)) {
            return $resolved;
        }

        if ($resolved === $this->workspaceRoot) {
            return '.';
        }

        return ltrim(substr($resolved, strlen($this->workspaceRoot)), '/');
    }

    private function normalizeInputPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            throw new ReadFileException('INVALID_ARGUMENT', 'Path is required.');
        }

        return $path;
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));

        $prefix = '';
        if (preg_match('/^[A-Za-z]:/', $path) === 1) {
            $prefix = substr($path, 0, 2);
            $path = substr($path, 2);
        } elseif (str_starts_with($path, '/')) {
            $prefix = '/';
        }

        $segments = preg_split('#/+#', trim($path, '/')) ?: [];
        $normalizedSegments = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if (!empty($normalizedSegments) && end($normalizedSegments) !== '..') {
                    array_pop($normalizedSegments);
                }
                continue;
            }

            $normalizedSegments[] = $segment;
        }

        $normalized = implode('/', $normalizedSegments);

        if ($prefix === '/') {
            return '/'.$normalized;
        }

        if ($prefix !== '') {
            return $prefix.($normalized !== '' ? '/'.$normalized : '');
        }

        return $normalized;
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1 || str_starts_with($path, '/');
    }

    private function isWithin(string $path, string $root): bool
    {
        $normalizedPath = $this->comparisonPath($path);
        $normalizedRoot = rtrim($this->comparisonPath($root), '/');

        return $normalizedPath === $normalizedRoot || str_starts_with($normalizedPath, $normalizedRoot.'/');
    }

    private function comparisonPath(string $path): string
    {
        $normalized = $this->normalizePath($path);

        return $this->caseInsensitive ? strtolower($normalized) : $normalized;
    }

    private function assertWithinWorkspace(string $resolvedPath, string $originalPath): void
    {
        if (!$this->isWithin($resolvedPath, $this->workspaceRoot)) {
            throw new ReadFileException('PATH_NOT_ALLOWED', 'Reading this path is not allowed.', $originalPath);
        }
    }

    private function assertAllowedPath(string $resolvedPath, string $originalPath): void
    {
        if (!$this->isAllowedPath($resolvedPath)) {
            throw new ReadFileException('PATH_NOT_ALLOWED', 'Reading this path is not allowed.', $originalPath);
        }
    }

    /**
     * Whether the resolved path is allowed by segment and extension rules (and within workspace).
     * Used for discovery; does not check file existence or readability.
     */
    public function isAllowedPath(string $resolvedPath): bool
    {
        if (!$this->isWithin($resolvedPath, $this->workspaceRoot)) {
            return false;
        }

        $relative = $this->relativePath($resolvedPath);
        $segments = $relative === '' ? [] : explode('/', strtolower($relative));

        foreach ($segments as $segment) {
            if (in_array($segment, $this->deniedSegments, true)) {
                return false;
            }

            if ($segment === '.env' || str_starts_with($segment, '.env.')) {
                return false;
            }

            if (str_contains($segment, 'secret') || str_contains($segment, 'password')) {
                return false;
            }
        }

        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        if ($extension !== '' && in_array($extension, $this->deniedExtensions, true)) {
            return false;
        }

        return true;
    }

    /**
     * Whether a path should be considered during discovery-based warming.
     * This is slightly stricter than isAllowedPath() so warm_cache skips
     * generated cache trees that are safe but not useful to pre-build.
     */
    public function isDiscoverablePath(string $resolvedPath): bool
    {
        if (!$this->isAllowedPath($resolvedPath)) {
            return false;
        }

        $relative = $this->relativePath($resolvedPath);
        $segments = $relative === '' || $relative === '.' ? [] : explode('/', strtolower($relative));

        foreach ($segments as $segment) {
            if (in_array($segment, ['.cache', '.phpunit.cache'], true)) {
                return false;
            }
        }

        return true;
    }
}
