<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Tests\Support;

trait CreatesTempWorkspace
{
    private string $workspaceRoot;

    protected function createWorkspace(): string
    {
        $root = sys_get_temp_dir().'/semantic-code-search-mcp-'.bin2hex(random_bytes(6));
        mkdir($root, 0777, true);
        $this->workspaceRoot = str_replace('\\', '/', $root);

        return $this->workspaceRoot;
    }

    protected function workspaceRoot(): string
    {
        return $this->workspaceRoot;
    }

    protected function workspacePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        return $this->workspaceRoot().'/'.$path;
    }

    protected function writeWorkspaceFile(string $path, string $contents): string
    {
        $absolute = $this->workspacePath($path);
        $directory = dirname($absolute);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($absolute, $contents);

        return $absolute;
    }

    protected function removeWorkspace(): void
    {
        if (!isset($this->workspaceRoot) || !is_dir($this->workspaceRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->workspaceRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($this->workspaceRoot);
    }
}
