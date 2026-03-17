<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Safety;

use InvalidArgumentException;

final class PathGuard
{
    private string $root;

    /**
     * @var string[]
     */
    private array $deniedSegments = [
        '.git',
        'vendor',
        'storage',
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

    public function __construct(string $projectRoot)
    {
        $resolved = realpath($projectRoot);
        if ($resolved === false) {
            throw new InvalidArgumentException('Invalid project root path.');
        }

        $this->root = $this->normalize($resolved);
    }

    public function root(): string
    {
        return $this->root;
    }

    public function resolveInRoot(string $path): string
    {
        if ($path === '') {
            throw new InvalidArgumentException('Path cannot be empty.');
        }

        $candidate = $this->normalize($path);
        if (!$this->isAbsolutePath($candidate)) {
            $candidate = $this->normalize($this->root.'/'.$candidate);
        }

        $existing = realpath($candidate);
        if ($existing !== false) {
            return $this->normalize($existing);
        }

        return $candidate;
    }

    public function isAllowed(string $path): bool
    {
        $resolved = $this->resolveInRoot($path);

        if ($resolved !== $this->root && !str_starts_with($resolved, $this->root.'/')) {
            return false;
        }

        $relative = $resolved === $this->root ? '' : ltrim(substr($resolved, strlen($this->root)), '/');
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

        $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        if ($ext !== '' && in_array($ext, $this->deniedExtensions, true)) {
            return false;
        }

        return true;
    }

    public function assertAllowed(string $path): void
    {
        if (!$this->isAllowed($path)) {
            throw new InvalidArgumentException(sprintf('Path is not allowed: %s', $path));
        }
    }

    private function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return rtrim($path, '/');
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/');
    }
}