#!/usr/bin/env php
<?php

declare(strict_types=1);

use Mcp\Schema\Content\TextContent;
use ReadFileCacheMcp\DiskCache;
use ReadFileCacheMcp\FileCache;
use ReadFileCacheMcp\FileDiscovery;
use ReadFileCacheMcp\PathGuard as ReadFilePathGuard;
use ReadFileCacheMcp\ReadFileTool;
use SemanticCodeSearchMcp\Embeddings\EmbedderFactory;
use SemanticCodeSearchMcp\Embeddings\QueryEmbedder;
use SemanticCodeSearchMcp\Index\IndexRepository;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');

$repoRoot = realpath(__DIR__ . '/..');
if ($repoRoot === false) {
    fwrite(STDERR, "Unable to resolve repo root.\n");
    exit(1);
}

$gitnexusVersion = '1.4.8';
$requireGitNexusReady = filter_var(getenv('MCP_HEALTH_REQUIRE_GITNEXUS_READY') ?: '0', FILTER_VALIDATE_BOOLEAN);
$requireSemanticReady = filter_var(getenv('MCP_HEALTH_REQUIRE_SEMANTIC_READY') ?: '0', FILTER_VALIDATE_BOOLEAN);

/**
 * @return array{level: string, name: string, summary: string, details: array<int, string>, status?: string}
 */
function makeResult(string $level, string $name, string $summary, array $details = [], ?string $status = null): array
{
    $result = [
        'level' => $level,
        'name' => $name,
        'summary' => $summary,
        'details' => $details,
    ];

    if ($status !== null) {
        $result['status'] = $status;
    }

    return $result;
}

/**
 * @return array{exit_code: int, stdout: string, stderr: string}
 */
function runCommand(array $command, string $cwd): array
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);
    if (!is_resource($process)) {
        return [
            'exit_code' => 1,
            'stdout' => '',
            'stderr' => 'Unable to start process.',
        ];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'exit_code' => proc_close($process),
        'stdout' => trim($stdout),
        'stderr' => trim($stderr),
    ];
}

function relativePath(string $repoRoot, string $path): string
{
    $normalizedRoot = str_replace('\\', '/', $repoRoot);
    $normalizedPath = str_replace('\\', '/', $path);

    if (str_starts_with($normalizedPath, $normalizedRoot . '/')) {
        return substr($normalizedPath, strlen($normalizedRoot) + 1);
    }

    return $normalizedPath;
}

/**
 * @return array<string, mixed>|null
 */
function readJsonFile(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : null;
}

$results = [];
$requiredFailure = false;
$optionalFailure = false;

$laravelServerRoot = $repoRoot . '/mcp/laravel-mysql-mcp';
$laravelMissing = [];
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    $laravelMissing[] = 'PHP 8.1+ is required for MCP servers.';
}
if (!is_file($repoRoot . '/vendor/autoload.php')) {
    $laravelMissing[] = 'Missing root vendor/autoload.php.';
}
if (!is_file($laravelServerRoot . '/vendor/autoload.php')) {
    $laravelMissing[] = 'Missing mcp/laravel-mysql-mcp/vendor/autoload.php.';
}
if (!is_file($repoRoot . '/artisan')) {
    $laravelMissing[] = 'Missing artisan entrypoint.';
}

if ($laravelMissing !== []) {
    $requiredFailure = true;
    $results[] = makeResult(
        'FAIL',
        'laravel_mysql',
        'Required Laravel MCP prerequisites are incomplete.',
        $laravelMissing,
        'MISSING_DEPENDENCIES'
    );
} else {
    $results[] = makeResult(
        'PASS',
        'laravel_mysql',
        'Required Laravel MCP prerequisites are present.',
        [
            'Repo-aware tools should be available once the server is registered in Codex/Cursor.',
            'Verified prerequisites: root vendor, MCP vendor, artisan, and PHP 8.1+.',
        ],
        'READY'
    );
}

$grepServerRoot = $repoRoot . '/mcp/grep-mcp';
$grepMissing = [];
if (!is_file($grepServerRoot . '/vendor/autoload.php')) {
    $grepMissing[] = 'Missing mcp/grep-mcp/vendor/autoload.php.';
}

$rgVersion = runCommand(['rg', '--version'], $repoRoot);
if ($rgVersion['exit_code'] !== 0) {
    $grepMissing[] = $rgVersion['stderr'] !== '' ? $rgVersion['stderr'] : 'ripgrep (rg) is not available on PATH.';
}

if ($grepMissing !== []) {
    $requiredFailure = true;
    $results[] = makeResult(
        'FAIL',
        'grep',
        'Required grep MCP prerequisites are incomplete.',
        $grepMissing,
        'MISSING_DEPENDENCIES'
    );
} else {
    $grepProbe = runCommand(
        ['rg', '-n', '--max-count', '1', '--fixed-strings', 'MCP Servers', 'mcp/README.md'],
        $repoRoot
    );

    if ($grepProbe['exit_code'] !== 0) {
        $requiredFailure = true;
        $results[] = makeResult(
            'FAIL',
            'grep',
            'Path-scoped grep probe failed.',
            [
                $grepProbe['stderr'] !== '' ? $grepProbe['stderr'] : 'rg did not return the expected match.',
                'Health checks should stay path-scoped; do not use broad repo scans for startup probes.',
            ],
            'PROBE_FAILED'
        );
    } else {
        $results[] = makeResult(
            'PASS',
            'grep',
            'Path-scoped grep probe succeeded.',
            [
                'Probe command: rg -n --max-count 1 --fixed-strings "MCP Servers" mcp/README.md',
                $grepProbe['stdout'],
            ],
            'READY'
        );
    }
}

$readFileServerRoot = $repoRoot . '/mcp/read-file-cache-mcp';
$readFileMissing = [];
if (!is_file($readFileServerRoot . '/vendor/autoload.php')) {
    $readFileMissing[] = 'Missing mcp/read-file-cache-mcp/vendor/autoload.php.';
}

if ($readFileMissing !== []) {
    $requiredFailure = true;
    $results[] = makeResult(
        'FAIL',
        'read_file_cache',
        'Required read-file-cache MCP prerequisites are incomplete.',
        $readFileMissing,
        'MISSING_DEPENDENCIES'
    );
} else {
    require_once $readFileServerRoot . '/vendor/autoload.php';

    try {
        $cacheRoot = getenv('MCP_READ_FILE_CACHE_ROOT') ?: ($repoRoot . '/.cache/read-file-cache-mcp');
        $pathGuard = new ReadFilePathGuard($repoRoot);
        $diskCache = new DiskCache($cacheRoot, 2097152, 10000, 134217728);
        $cache = new FileCache(2097152, 128, 33554432, $diskCache);
        $fileDiscovery = new FileDiscovery($pathGuard);
        $tool = new ReadFileTool($pathGuard, $cache, 200, 1000, 262144, $fileDiscovery);
        $probe = $tool->read_file('mcp/README.md', 1, 4);

        $contentText = '';
        foreach ($probe->content as $content) {
            if ($content instanceof TextContent) {
                $contentText = $content->text;
                break;
            }
        }

        $structuredText = $probe->structuredContent['text'] ?? null;
        $cacheDb = $cacheRoot . '/read-file-cache.sqlite';

        if (!is_string($contentText) || trim($contentText) === '') {
            throw new RuntimeException('The probe did not return any text content.');
        }

        if (!is_string($structuredText) || trim($structuredText) === '') {
            throw new RuntimeException('The structured payload does not include returned file text.');
        }

        $results[] = makeResult(
            'PASS',
            'read_file_cache',
            'Small read probe returned file text in both content and structured payload.',
            [
                'Probe file: mcp/README.md',
                'Cache DB: ' . relativePath($repoRoot, $cacheDb) . (is_file($cacheDb) ? ' (present)' : ' (not found yet)'),
                sprintf('Cache hit: %s', ($probe->structuredContent['cache_hit'] ?? false) ? 'yes' : 'no'),
            ],
            'READY'
        );
    } catch (Throwable $exception) {
        $requiredFailure = true;
        $results[] = makeResult(
            'FAIL',
            'read_file_cache',
            'Small read probe failed.',
            [$exception->getMessage()],
            'PROBE_FAILED'
        );
    }
}

$cursorMcpConfigPath = $repoRoot . '/.cursor/mcp.json';
$cursorMcpConfig = readJsonFile($cursorMcpConfigPath);
if ($cursorMcpConfig === null) {
    $results[] = makeResult(
        'WARN',
        'cursor_mcp_config',
        'Project-level Cursor MCP config is missing or unreadable.',
        [
            'Expected file: .cursor/mcp.json',
            'Recommended servers: grep, read_file_cache, gitnexus, semantic_code_search.',
        ],
        'MISSING_CONFIG'
    );
} else {
    $servers = $cursorMcpConfig['mcpServers'] ?? [];
    $details = [];
    $configWarnings = [];

    if (!is_array($servers)) {
        $configWarnings[] = 'The "mcpServers" object is missing or invalid.';
    } else {
        foreach (['grep', 'read_file_cache', 'gitnexus'] as $serverName) {
            if (!array_key_exists($serverName, $servers)) {
                $configWarnings[] = sprintf('Missing "%s" server entry.', $serverName);
            }
        }

        if (!array_key_exists('semantic_code_search', $servers)) {
            $configWarnings[] = 'Missing "semantic_code_search" server entry.';
        }

        $gitnexusArgs = $servers['gitnexus']['args'] ?? [];
        if (!is_array($gitnexusArgs)) {
            $configWarnings[] = 'GitNexus server args are missing or invalid.';
        } else {
            $joinedArgs = implode(' ', array_map(static fn ($value): string => (string) $value, $gitnexusArgs));
            if (str_contains($joinedArgs, 'gitnexus@latest')) {
                $configWarnings[] = sprintf('GitNexus is pinned to @latest; use gitnexus@%s instead.', $gitnexusVersion);
            } elseif (!str_contains($joinedArgs, 'gitnexus@' . $gitnexusVersion)) {
                $details[] = sprintf('GitNexus args detected: %s', $joinedArgs !== '' ? $joinedArgs : '(empty)');
            }
        }
    }

    if ($configWarnings !== []) {
        $results[] = makeResult(
            'WARN',
            'cursor_mcp_config',
            'Project-level Cursor MCP config has drift from the recommended tool stack.',
            array_merge(
                ['Checked file: .cursor/mcp.json'],
                $configWarnings,
                $details
            ),
            'CONFIG_DRIFT'
        );
    } else {
        $results[] = makeResult(
            'PASS',
            'cursor_mcp_config',
            'Project-level Cursor MCP config exposes the recommended repo tool stack.',
            [
                'Checked file: .cursor/mcp.json',
                sprintf('GitNexus pin: gitnexus@%s', $gitnexusVersion),
                'Servers present: grep, read_file_cache, gitnexus, semantic_code_search.',
            ],
            'READY'
        );
    }
}

$auditWebServerRoot = $repoRoot . '/mcp/audit-web-mcp';
$auditWebMissing = [];
if (!is_file($auditWebServerRoot . '/vendor/autoload.php')) {
    $auditWebMissing[] = 'Missing mcp/audit-web-mcp/vendor/autoload.php.';
}
if (!is_file($auditWebServerRoot . '/node_modules/@playwright/test/package.json')) {
    $auditWebMissing[] = 'Missing mcp/audit-web-mcp/node_modules/@playwright/test/package.json.';
}

$nodeVersion = runCommand(['node', '--version'], $repoRoot);
if ($nodeVersion['exit_code'] !== 0) {
    $auditWebMissing[] = $nodeVersion['stderr'] !== '' ? $nodeVersion['stderr'] : 'node is not available on PATH.';
}

if ($auditWebMissing !== []) {
    $results[] = makeResult(
        'WARN',
        'audit_web',
        'Optional audit-web MCP prerequisites are incomplete.',
        array_merge(
            $auditWebMissing,
            ['Run: cd mcp/audit-web-mcp && composer install && npm install && npx playwright install']
        ),
        'MISSING_DEPENDENCIES'
    );
} else {
    $playwrightProbe = runCommand(
        ['node', '-e', 'require("@playwright/test"); process.stdout.write("playwright-ready");'],
        $auditWebServerRoot
    );

    if ($playwrightProbe['exit_code'] !== 0) {
        $results[] = makeResult(
            'WARN',
            'audit_web',
            'Optional audit-web MCP probe could not load Playwright runtime.',
            [
                $playwrightProbe['stderr'] !== '' ? $playwrightProbe['stderr'] : 'Unknown Playwright probe error.',
                'Run from mcp/audit-web-mcp: npm install && npx playwright install',
            ],
            'PLAYWRIGHT_UNAVAILABLE'
        );
    } else {
        $results[] = makeResult(
            'PASS',
            'audit_web',
            'Optional audit-web MCP prerequisites are ready.',
            [
                'Node: ' . ($nodeVersion['stdout'] !== '' ? explode("\n", $nodeVersion['stdout'])[0] : 'available'),
                'Probe: Playwright runtime loaded successfully.',
            ],
            'READY'
        );
    }
}

$semanticServerRoot = $repoRoot.'/mcp/semantic-code-search-mcp';
if (!is_file($semanticServerRoot . '/vendor/autoload.php')) {
    $results[] = makeResult(
        'WARN',
        'semantic_code_search',
        'Optional semantic MCP server dependencies are not installed.',
        ['Run composer install in mcp/semantic-code-search-mcp to enable semantic checks.'],
        'MISSING_SERVER'
    );
} else {
    require_once $semanticServerRoot . '/vendor/autoload.php';

    $workspaceRoot = getenv('MCP_SEMANTIC_WORKSPACE_ROOT') ?: $repoRoot;
    $indexRoot = getenv('MCP_SEMANTIC_INDEX_ROOT') ?: ($repoRoot.'/.cache/semantic-code-search-mcp');
    $model = EmbedderFactory::modelFromEnvironment();
    $indexPath = rtrim(str_replace('\\', '/', $indexRoot), '/').'/semantic-code-search.sqlite';
    $deepSemanticProbe = filter_var(getenv('MCP_HEALTH_DEEP_SEMANTIC_PROBE') ?: '0', FILTER_VALIDATE_BOOLEAN);

    try {
        $repository = new IndexRepository($indexPath);
        $status = $repository->status(str_replace('\\', '/', $workspaceRoot), $model);

        if (!$status['ready']) {
            $results[] = makeResult(
                'WARN',
                'semantic_code_search',
                'Semantic index is not built yet.',
                [
                    'Run: php mcp/semantic-code-search-mcp/bin/index-codebase',
                    'This server is optional; fall back to laravel_mysql + grep + read_file when not indexed.',
                ],
                'NOT_INDEXED'
            );
        } elseif ($status['stale']) {
            $results[] = makeResult(
                'WARN',
                'semantic_code_search',
                'Semantic index is stale for the current workspace or embed model.',
                [
                    'Run: php mcp/semantic-code-search-mcp/bin/index-codebase --force',
                    'Current model: ' . $model,
                ],
                'STALE'
            );
        } else {
            if ($deepSemanticProbe) {
                $embedder = EmbedderFactory::fromEnvironment($semanticServerRoot);
                $query = 'Where is grep MCP documented?';
                $vector = $embedder instanceof QueryEmbedder
                    ? $embedder->embedQuery($query)
                    : ($embedder->embedTexts([$query])[0] ?? []);
                $matches = $repository->search($vector, 1, 'mcp', false);

                if ($matches === []) {
                    throw new RuntimeException('Semantic search probe returned no matches.');
                }

                $results[] = makeResult(
                    'PASS',
                    'semantic_code_search',
                    'Semantic index is ready and a behavior-style probe succeeded.',
                    [
                        'Top match: ' . $matches[0]['file'] . ':' . $matches[0]['start_line'],
                        'Model: ' . $model,
                    ],
                    'READY'
                );
            } else {
                $results[] = makeResult(
                    'PASS',
                    'semantic_code_search',
                    'Semantic index is ready (status probe).',
                    [
                        'Model: ' . $model,
                        'Set MCP_HEALTH_DEEP_SEMANTIC_PROBE=1 for embed/search validation.',
                    ],
                    'READY'
                );
            }
        }
    } catch (Throwable $exception) {
        $results[] = makeResult(
            'WARN',
            'semantic_code_search',
            'Semantic search is optional and the readiness probe could not complete.',
            [$exception->getMessage()],
            'EMBEDDER_UNAVAILABLE'
        );
    }
}

$gitnexusMetaPath = $repoRoot . '/.gitnexus/meta.json';
if (!is_file($gitnexusMetaPath)) {
    $results[] = makeResult(
        'WARN',
            'gitnexus',
            'GitNexus graph metadata not found.',
            [
                'Run: npx gitnexus@' . $gitnexusVersion . ' analyze',
                'Expected metadata file: .gitnexus/meta.json',
            ],
            'MISSING_INDEX'
        );
} else {
    $rawMeta = @file_get_contents($gitnexusMetaPath);
    $meta = is_string($rawMeta) ? json_decode($rawMeta, true) : null;

    if (!is_array($meta)) {
        $results[] = makeResult(
            'WARN',
            'gitnexus',
            'GitNexus metadata exists but could not be parsed.',
            ['Re-run: npx gitnexus@' . $gitnexusVersion . ' analyze'],
            'INVALID_METADATA'
        );
    } else {
        $indexedCommit = (string) ($meta['lastCommit'] ?? '');
        $embeddings = (int) ($meta['stats']['embeddings'] ?? 0);
        $head = runCommand(['git', 'rev-parse', 'HEAD'], $repoRoot);

        if ($head['exit_code'] !== 0 || $head['stdout'] === '') {
            $results[] = makeResult(
                'WARN',
                'gitnexus',
                'Unable to read current git HEAD for staleness check.',
                [$head['stderr'] !== '' ? $head['stderr'] : 'git rev-parse HEAD returned no output.'],
                'HEAD_UNKNOWN'
            );
        } elseif ($indexedCommit === '' || strlen($indexedCommit) < 7) {
            $results[] = makeResult(
                'WARN',
                'gitnexus',
                'GitNexus metadata is missing lastCommit.',
                ['Re-run: npx gitnexus@' . $gitnexusVersion . ' analyze'],
                'INVALID_METADATA'
            );
        } else {
            $currentHead = trim($head['stdout']);
            if ($indexedCommit === $currentHead) {
                $results[] = makeResult(
                    'PASS',
                    'gitnexus',
                    'GitNexus graph is in sync with current HEAD.',
                    [
                        'lastCommit: ' . $indexedCommit,
                        'embeddings: ' . $embeddings,
                    ],
                    'READY'
                );
            } else {
                $behindCount = null;
                $behind = runCommand(['git', 'rev-list', '--count', $indexedCommit . '..' . $currentHead], $repoRoot);
                if ($behind['exit_code'] === 0 && preg_match('/^\d+$/', trim($behind['stdout'])) === 1) {
                    $behindCount = (int) trim($behind['stdout']);
                }

                $details = [
                    'indexed lastCommit: ' . $indexedCommit,
                    'current HEAD: ' . $currentHead,
                    'embeddings: ' . $embeddings,
                    'Run: npx gitnexus@' . $gitnexusVersion . ' analyze' . ($embeddings > 0 ? ' --embeddings' : ''),
                ];

                if (is_int($behindCount)) {
                    $details[] = 'commits behind: ' . $behindCount;
                }

                $results[] = makeResult(
                    'WARN',
                    'gitnexus',
                    'GitNexus graph is stale compared to current HEAD.',
                    $details,
                    'STALE'
                );
            }
        }
    }
}

foreach ($results as $result) {
    if ($result['name'] === 'gitnexus' && $requireGitNexusReady && ($result['status'] ?? '') !== 'READY') {
        $optionalFailure = true;
    }

    if ($result['name'] === 'semantic_code_search' && $requireSemanticReady && ($result['status'] ?? '') !== 'READY') {
        $optionalFailure = true;
    }
}

echo "MCP health check for " . str_replace('\\', '/', $repoRoot) . "\n";
echo str_repeat('=', 72) . "\n";

foreach ($results as $result) {
    $suffix = isset($result['status']) ? ' [' . $result['status'] . ']' : '';
    echo sprintf("[%s] %s%s - %s\n", $result['level'], $result['name'], $suffix, $result['summary']);

    foreach ($result['details'] as $detail) {
        echo "  - {$detail}\n";
    }
}

echo str_repeat('-', 72) . "\n";
$summary = [];
foreach ($results as $result) {
    $summary[] = $result['name'] . '=' . ($result['status'] ?? $result['level']);
}
echo 'Startup status: ' . implode(', ', $summary) . "\n";

if ($requiredFailure) {
    echo "Required MCPs are not fully ready. Fix FAIL entries before relying on startup automation.\n";
} elseif ($optionalFailure) {
    echo "Optional readiness requirements were enforced and did not pass. Refresh the reported indexes, then rerun the health check.\n";
} else {
    echo "Required MCPs are ready. GitNexus and semantic search may still be optional unless your current task requires them.\n";
}

exit(($requiredFailure || $optionalFailure) ? 1 : 0);
