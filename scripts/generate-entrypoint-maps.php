#!/usr/bin/env php
<?php

declare(strict_types=1);

const LAST_REVIEWED_TOKEN = '__LAST_REVIEWED__';

date_default_timezone_set('Asia/Bangkok');
error_reporting(E_ALL);
ini_set('display_errors', '1');

exit(main($argv));

function main(array $argv): int
{
    $checkOnly = in_array('--check', $argv, true);

    $repoRoot = realpath(__DIR__ . '/..');
    if ($repoRoot === false) {
        fwrite(STDERR, "Unable to resolve repo root.\n");

        return 1;
    }

    $entrypointsDir = $repoRoot . '/ai/entrypoints';
    $today = (new DateTimeImmutable('now'))->format('Y-m-d');

    $inventory = buildInventory($repoRoot);
    $documents = buildDocuments($repoRoot, $inventory);
    $expectedModuleDocs = array_values(array_filter(
        array_keys($documents),
        static fn (string $name): bool => str_starts_with($name, 'module-')
    ));

    [$written, $unchanged, $removed, $issues] = syncDocuments(
        $entrypointsDir,
        $documents,
        $expectedModuleDocs,
        $today,
        $checkOnly
    );

    if ($checkOnly) {
        if ($issues === []) {
            fwrite(STDOUT, "Entry maps are up to date.\n");

            return 0;
        }

        fwrite(STDOUT, "Entry maps need regeneration:\n");
        foreach ($issues as $issue) {
            fwrite(STDOUT, '- ' . $issue . "\n");
        }

        return 1;
    }

    fwrite(STDOUT, "Generated ai/entrypoints artifacts.\n");
    fwrite(STDOUT, 'Written: ' . count($written) . "\n");
    fwrite(STDOUT, 'Unchanged: ' . count($unchanged) . "\n");
    fwrite(STDOUT, 'Removed stale module docs: ' . count($removed) . "\n");

    if ($written !== []) {
        fwrite(STDOUT, "Updated files:\n");
        foreach ($written as $name) {
            fwrite(STDOUT, '- ' . $name . "\n");
        }
    }

    if ($removed !== []) {
        fwrite(STDOUT, "Removed files:\n");
        foreach ($removed as $name) {
            fwrite(STDOUT, '- ' . $name . "\n");
        }
    }

    return 0;
}

/**
 * @return array{
 *   enabled_modules: array<int, string>,
 *   local_modules: array<int, string>,
 *   controllers: array<int, string>,
 *   utils: array<int, string>,
 *   modules: array<string, array{
 *     name: string,
 *     readme: string|null,
 *     route_web: string|null,
 *     route_api: string|null,
 *     route_web_empty: bool,
 *     route_api_empty: bool,
 *     route_prefixes: array<int, string>,
 *     controllers_root: string,
 *     controller_entries: array<int, array{
 *       type: string,
 *       name: string,
 *       path: string,
 *       children: array<int, array{type: string, name: string, path: string}>
 *     }>,
 *     views_root: string,
 *     view_dirs: array<int, string>
 *   }>
 * }
 */
function buildInventory(string $repoRoot): array
{
    $enabledModules = loadEnabledModules($repoRoot . '/modules_statuses.json');
    $localModules = listDirectories($repoRoot . '/Modules');
    $controllers = listFiles($repoRoot . '/app/Http/Controllers', '*.php');
    $utils = listFiles($repoRoot . '/app/Utils', '*Util.php');

    $modules = [];
    foreach ($localModules as $module) {
        $modules[$module] = collectModuleInventory($repoRoot, $module);
    }

    return [
        'enabled_modules' => $enabledModules,
        'local_modules' => $localModules,
        'controllers' => $controllers,
        'utils' => $utils,
        'modules' => $modules,
    ];
}

/**
 * @return array<int, string>
 */
function loadEnabledModules(string $jsonPath): array
{
    if (!is_file($jsonPath)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($jsonPath), true);
    if (!is_array($decoded)) {
        return [];
    }

    $enabled = [];
    foreach ($decoded as $name => $value) {
        if ($value === true && is_string($name)) {
            $enabled[] = $name;
        }
    }

    sort($enabled, SORT_NATURAL | SORT_FLAG_CASE);

    return $enabled;
}

/**
 * @return array<int, string>
 */
function listDirectories(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $items = [];
    foreach (scandir($directory) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
            continue;
        }

        $path = $directory . '/' . $entry;
        if (is_dir($path)) {
            $items[] = $entry;
        }
    }

    sort($items, SORT_NATURAL | SORT_FLAG_CASE);

    return $items;
}

/**
 * @return array<int, string>
 */
function listFiles(string $directory, string $pattern): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $files = [];
    $glob = glob($directory . '/' . $pattern) ?: [];
    foreach ($glob as $path) {
        if (is_file($path)) {
            $files[] = basename($path);
        }
    }

    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return $files;
}

/**
 * @return array{
 *   name: string,
 *   readme: string|null,
 *   route_web: string|null,
 *   route_api: string|null,
 *   route_web_empty: bool,
 *   route_api_empty: bool,
 *   route_prefixes: array<int, string>,
 *   controllers_root: string,
 *   controller_entries: array<int, array{
 *     type: string,
 *     name: string,
 *     path: string,
 *     children: array<int, array{type: string, name: string, path: string}>
 *   }>,
 *   views_root: string,
 *   view_dirs: array<int, string>
 * }
 */
function collectModuleInventory(string $repoRoot, string $module): array
{
    $moduleRoot = $repoRoot . '/Modules/' . $module;
    $webPath = 'Modules/' . $module . '/Routes/web.php';
    $apiPath = 'Modules/' . $module . '/Routes/api.php';
    $controllersRoot = 'Modules/' . $module . '/Http/Controllers';
    $viewsRoot = 'Modules/' . $module . '/Resources/views';

    $routeWebAbsolute = $repoRoot . '/' . $webPath;
    $routeApiAbsolute = $repoRoot . '/' . $apiPath;

    return [
        'name' => $module,
        'readme' => detectModuleReadme($repoRoot, $moduleRoot),
        'route_web' => is_file($routeWebAbsolute) ? $webPath : null,
        'route_api' => is_file($routeApiAbsolute) ? $apiPath : null,
        'route_web_empty' => is_file($routeWebAbsolute) ? trim((string) file_get_contents($routeWebAbsolute)) === '' : false,
        'route_api_empty' => is_file($routeApiAbsolute) ? trim((string) file_get_contents($routeApiAbsolute)) === '' : false,
        'route_prefixes' => array_values(array_unique(array_merge(
            extractRoutePrefixes($routeWebAbsolute),
            extractRoutePrefixes($routeApiAbsolute)
        ))),
        'controllers_root' => $controllersRoot,
        'controller_entries' => collectTreeEntries($repoRoot, $repoRoot . '/' . $controllersRoot),
        'views_root' => $viewsRoot,
        'view_dirs' => listDirectories($repoRoot . '/' . $viewsRoot),
    ];
}

function detectModuleReadme(string $repoRoot, string $moduleRoot): ?string
{
    $candidates = glob($moduleRoot . '/README*') ?: [];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return toRepoRelative($repoRoot, $candidate);
        }
    }

    return null;
}

/**
 * @return array<int, string>
 */
function extractRoutePrefixes(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $content = (string) file_get_contents($path);
    if ($content === '') {
        return [];
    }

    $matches = [];
    preg_match_all("/(?:->|Route::)prefix\\(\\s*'([^']+)'\\s*\\)/", $content, $matches);

    $prefixes = [];
    foreach ($matches[1] ?? [] as $prefix) {
        if (is_string($prefix) && $prefix !== '') {
            $prefixes[] = $prefix;
        }
    }

    $prefixes = array_values(array_unique($prefixes));
    sort($prefixes, SORT_NATURAL | SORT_FLAG_CASE);

    return $prefixes;
}

/**
 * @return array<int, array{
 *   type: string,
 *   name: string,
 *   path: string,
 *   children: array<int, array{type: string, name: string, path: string}>
 * }>
 */
function collectTreeEntries(string $repoRoot, string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $entries = [];
    foreach (scandir($directory) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
            continue;
        }

        $absolute = $directory . '/' . $entry;
        if (is_dir($absolute)) {
            $children = [];
            foreach (scandir($absolute) ?: [] as $child) {
                if ($child === '.' || $child === '..' || str_starts_with($child, '.')) {
                    continue;
                }

                $childAbsolute = $absolute . '/' . $child;
                $children[] = [
                    'type' => is_dir($childAbsolute) ? 'dir' : 'file',
                    'name' => is_dir($childAbsolute) ? $child . '/' : $child,
                    'path' => toRepoRelative($repoRoot, $childAbsolute),
                ];
            }

            usort($children, 'compareEntryRows');

            $entries[] = [
                'type' => 'dir',
                'name' => $entry . '/',
                'path' => toRepoRelative($repoRoot, $absolute),
                'children' => $children,
            ];
            continue;
        }

        $entries[] = [
            'type' => 'file',
            'name' => $entry,
            'path' => toRepoRelative($repoRoot, $absolute),
            'children' => [],
        ];
    }

    usort($entries, 'compareEntryRows');

    return $entries;
}

/**
 * @param array{name: string, type: string} $left
 * @param array{name: string, type: string} $right
 */
function compareEntryRows(array $left, array $right): int
{
    if ($left['type'] !== $right['type']) {
        return $left['type'] === 'dir' ? -1 : 1;
    }

    return strnatcasecmp($left['name'], $right['name']);
}

function toRepoRelative(string $repoRoot, string $absolutePath): string
{
    $normalizedRoot = str_replace('\\', '/', realpath($repoRoot) ?: $repoRoot);
    $normalizedPath = str_replace('\\', '/', realpath($absolutePath) ?: $absolutePath);

    if (str_starts_with($normalizedPath, $normalizedRoot . '/')) {
        return substr($normalizedPath, strlen($normalizedRoot) + 1);
    }

    return ltrim($normalizedPath, '/');
}

/**
 * @param array{
 *   enabled_modules: array<int, string>,
 *   local_modules: array<int, string>,
 *   controllers: array<int, string>,
 *   utils: array<int, string>,
 *   modules: array<string, array<string, mixed>>
 * } $inventory
 * @return array<string, string>
 */
function buildDocuments(string $repoRoot, array $inventory): array
{
    $moduleMeta = moduleMetadata();
    $documents = [];

    $documents['README.md'] = buildReadmeDocument();
    $documents['_TEMPLATE.md'] = buildTemplateDocument();
    $documents['core-http-entry.md'] = buildCoreHttpEntryDocument();
    $documents['core-http-controllers.md'] = buildCoreHttpControllersDocument($inventory['controllers']);
    $documents['core-utils-index.md'] = buildCoreUtilsIndexDocument($inventory['utils']);

    foreach ($inventory['local_modules'] as $module) {
        /** @var array<string, mixed> $moduleInventory */
        $moduleInventory = $inventory['modules'][$module];
        $documents['module-' . $module . '.md'] = buildModuleDocument(
            $module,
            $moduleInventory,
            $moduleMeta[$module] ?? []
        );
    }

    $documents['INDEX.md'] = buildIndexDocument(
        $inventory['enabled_modules'],
        $inventory['local_modules'],
        $inventory['modules'],
        $moduleMeta
    );

    return $documents;
}

function buildReadmeDocument(): string
{
    $lines = [
        generatedBanner(),
        '# Repo Entry Maps',
        '',
        '`ai/entrypoints/` is the checkout-aware starting point for agents when the correct route, controller, module, or view is not obvious from the user request.',
        '',
        '## INDEX-first workflow',
        '',
        '1. Open [INDEX.md](./INDEX.md).',
        '2. Pick one root or module map that matches the task.',
        '3. Read only the verified repo paths listed in that map first.',
        '4. Widen with grep, route files, or `php artisan route:list` only if the map does not answer the question.',
        '',
        'This keeps discovery narrow without replacing repo-aware tools.',
        '',
        '## Generator',
        '',
        '- Refresh all generated maps: `composer entrypoints:generate` or `php scripts/generate-entrypoint-maps.php`',
        '- Check for drift without writing: `composer entrypoints:check` or `php scripts/generate-entrypoint-maps.php --check`',
        '- The generator rewrites root maps, module maps, and the INDEX from the live checkout structure.',
        '',
        '## Naming',
        '',
        '- `core-<area>.md` for root application shards such as [core-http-entry.md](./core-http-entry.md).',
        '- `module-<PascalCase>.md` for folders under `Modules/`.',
        '- Keep file names matched to the real checkout paths so [INDEX.md](./INDEX.md) can link safely.',
        '',
        '## Required map contents',
        '',
        'Each module map should verify and list:',
        '',
        '- `Routes/web.php`',
        '- `Routes/api.php` when it exists',
        '- `Http/Controllers` files or subfolders',
        '- `Resources/views` top-level directories',
        '- relevant search keywords',
        '- links to existing `ai/*.md` or module `README.md` files when they add real context',
        '- a `Last reviewed` date',
        '',
        'Root maps follow the same rule: link only to files or directories that exist in this checkout.',
        '',
        '## Coverage policy',
        '',
        '- Every local folder under `Modules/` gets a `module-<Name>.md`.',
        '- [INDEX.md](./INDEX.md) includes one row per enabled module in [modules_statuses.json](../../modules_statuses.json) plus any local module folder that is not present in that JSON file.',
        '- If a module is enabled in JSON but missing on disk, `Map` must be `—` and `Notes` must say `not in checkout`.',
        '- Do not add dead map links or placeholder file links.',
        '',
        '## Maintenance rule',
        '',
        '- Re-run the generator in the same PR when route wiring, controller ownership, primary views, or module entry structure changes.',
        '- Keep `composer entrypoints:check` available for CI or pre-commit enforcement.',
        '',
        '## Fallbacks when a map is too thin',
        '',
        '- Read ' . repoLink('routes/web.php') . ', ' . repoLink('routes/api.php') . ', or the module route files directly.',
        '- Use grep for exact controller, route-name, selector, or translation-key lookups.',
        '- Use the guidance in ' . aiLink('agent-tools-and-mcp.md') . ' when the task needs route discovery, schema truth, or caller impact beyond these maps.',
        '',
        '## Related docs',
        '',
        '- [INDEX.md](./INDEX.md)',
        '- [_TEMPLATE.md](./_TEMPLATE.md)',
        '- ' . aiLink('agent-tools-and-mcp.md'),
        '- ' . repoRelativeLink('AGENTS.md'),
        '',
        '## Last reviewed',
        '',
        '- ' . LAST_REVIEWED_TOKEN,
    ];

    return implode("\n", $lines) . "\n";
}

function buildTemplateDocument(): string
{
    $lines = [
        generatedBanner(),
        '# Entry Map Template',
        '',
        'Use this structure for new root or module maps. Replace the placeholder text with verified checkout paths only.',
        '',
        '## Purpose',
        '',
        '- State what area this map narrows and when an agent should open it.',
        '',
        '## Verified paths',
        '',
        '### Routes',
        '',
        '- `Routes/web.php`',
        '- `Routes/api.php` if present',
        '',
        '### Controllers',
        '',
        '- `Http/Controllers/`',
        '- top-level controller files or subfolders that matter for first-pass discovery',
        '',
        '### Views',
        '',
        '- `Resources/views/`',
        '- top-level directories under `Resources/views/`',
        '',
        '### Assets / JS',
        '',
        '- Add only when there is a real entry asset, layout script include, or source file that helps first-pass discovery.',
        '',
        '## Search keywords',
        '',
        '- Add route prefixes, feature nouns, controller names, or view-area keywords that help grep.',
        '',
        '## Related docs',
        '',
        '- Link only to existing `ai/*.md` files or module `README.md` files that add non-duplicative context.',
        '',
        '## Last reviewed',
        '',
        '- ' . LAST_REVIEWED_TOKEN,
    ];

    return implode("\n", $lines) . "\n";
}

function buildCoreHttpEntryDocument(): string
{
    $lines = [
        generatedBanner(),
        '# Core HTTP Entry',
        '',
        'Use this map when the task is in the root Laravel app rather than a `Modules/*` package.',
        '',
        '## Purpose',
        '',
        '- Narrow first-pass reads for root routes, top-level controllers, and shared HTTP entry surfaces.',
        '- Open [core-http-controllers.md](./core-http-controllers.md) next when the task is clearly controller-owned.',
        '',
        '## Verified paths',
        '',
        '### Routes',
        '',
        '- ' . repoLink('routes/web.php') . ' — main root web routes; includes ' . repoLink('routes/install_r.php'),
        '- ' . repoLink('routes/api.php') . ' — root API routes',
        '- ' . repoLink('routes/install_r.php') . ' — install/bootstrap route file pulled into `web.php`',
        '- ' . repoLink('routes/channels.php') . ' — broadcast authorization hooks',
        '',
        '### Controllers',
        '',
        '- ' . repoLink('app/Http/Controllers/'),
        '- ' . repoLink('app/Http/Controllers/Auth/'),
        '- ' . repoLink('app/Http/Controllers/Install/'),
        '- ' . repoLink('app/Http/Controllers/Restaurant/'),
        '',
        '### Related root indexes',
        '',
        '- [core-http-controllers.md](./core-http-controllers.md)',
        '- [core-utils-index.md](./core-utils-index.md)',
        '',
        '## Search keywords',
        '',
        '- `routes/web.php`',
        '- `routes/api.php`',
        '- `install_r.php`',
        '- `App\\Http\\Controllers`',
        '- `home`',
        '- `sell`',
        '- `products`',
        '- `contacts`',
        '- `quotes`',
        '- `reports`',
        '',
        '## Related docs',
        '',
        '- ' . aiLink('laravel-conventions.md'),
        '- ' . aiLink('security-and-auth.md'),
        '- ' . aiLink('agent-tools-and-mcp.md'),
        '',
        '## Last reviewed',
        '',
        '- ' . LAST_REVIEWED_TOKEN,
    ];

    return implode("\n", $lines) . "\n";
}

/**
 * @param array<int, string> $controllers
 */
function buildCoreHttpControllersDocument(array $controllers): string
{
    $lines = [
        generatedBanner(),
        '# Core HTTP Controllers',
        '',
        'Top-level root controller index for `app/Http/Controllers/*.php`.',
        '',
        '## Purpose',
        '',
        '- Give agents a fast, verified map of the root controller surface.',
        '- Use the grep hint column to jump from a controller name to the owning route declarations quickly.',
        '',
        '| Controller | Purpose | Grep / route hint |',
        '|---|---|---|',
    ];

    foreach ($controllers as $controller) {
        $className = basename($controller, '.php');
        $lines[] = '| ' . repoLink('app/Http/Controllers/' . $controller, $controller)
            . ' | ' . controllerPurpose($controller)
            . ' | `' . controllerHint($className) . '` |';
    }

    $lines = array_merge($lines, [
        '',
        '## Related docs',
        '',
        '- [core-http-entry.md](./core-http-entry.md)',
        '- [core-utils-index.md](./core-utils-index.md)',
        '- ' . aiLink('laravel-conventions.md'),
        '',
        '## Last reviewed',
        '',
        '- ' . LAST_REVIEWED_TOKEN,
    ]);

    return implode("\n", $lines) . "\n";
}

/**
 * @param array<int, string> $utils
 */
function buildCoreUtilsIndexDocument(array $utils): string
{
    $lines = [
        generatedBanner(),
        '# Core Utils Index',
        '',
        'Shared root utility index for `app/Utils/*Util.php`.',
        '',
        '| Util | Purpose | Grep hint |',
        '|---|---|---|',
    ];

    foreach ($utils as $util) {
        $stem = basename($util, '.php');
        $lines[] = '| ' . repoLink('app/Utils/' . $util, $util)
            . ' | ' . utilPurpose($util)
            . ' | `rg -n \'' . $stem . '|' . preg_replace('/Util$/', '', $stem) . '\' app routes Modules` |';
    }

    $lines = array_merge($lines, [
        '',
        '## Related docs',
        '',
        '- [core-http-entry.md](./core-http-entry.md)',
        '- ' . aiLink('laravel-conventions.md'),
        '- ' . aiLink('database-map.md'),
        '',
        '## Last reviewed',
        '',
        '- ' . LAST_REVIEWED_TOKEN,
    ]);

    return implode("\n", $lines) . "\n";
}

/**
 * @param array<int, string> $enabledModules
 * @param array<int, string> $localModules
 * @param array<string, array<string, mixed>> $moduleInventories
 * @param array<string, array<string, mixed>> $moduleMeta
 */
function buildIndexDocument(
    array $enabledModules,
    array $localModules,
    array $moduleInventories,
    array $moduleMeta
): string {
    $lines = [
        generatedBanner(),
        '# Entry Map Index',
        '',
        'Start here when the correct repo area is unclear.',
        '',
        '| Trigger | Map | Notes |',
        '|---|---|---|',
        '| `Core (root)`, root routes, `app/Http/Controllers`, `app/Utils` | [core-http-entry.md](./core-http-entry.md) | Deeper root maps: [core-http-controllers.md](./core-http-controllers.md), [core-utils-index.md](./core-utils-index.md) |',
    ];

    foreach ($localModules as $module) {
        $meta = $moduleMeta[$module] ?? [];
        $inventory = $moduleInventories[$module];
        $trigger = $meta['index_trigger'] ?? ('`' . $module . '`');
        $note = $meta['index_note'] ?? genericLocalIndexNote($inventory);
        $lines[] = '| ' . $trigger . ' | [module-' . $module . '.md](./module-' . $module . '.md) | ' . $note . ' |';
    }

    $localLookup = array_flip($localModules);
    foreach ($enabledModules as $module) {
        if (isset($localLookup[$module])) {
            continue;
        }

        $lines[] = '| `' . $module . '` | — | not in checkout |';
    }

    foreach ($localModules as $module) {
        if (in_array($module, $enabledModules, true)) {
            continue;
        }

        $lines[] = '| `' . $module . '` | [module-' . $module . '.md](./module-' . $module . '.md) | present locally; not listed in `modules_statuses.json` |';
    }

    $lines = array_merge($lines, [
        '',
        '## Last reviewed',
        '',
        '- ' . LAST_REVIEWED_TOKEN,
    ]);

    return implode("\n", $lines) . "\n";
}

/**
 * @param array<string, mixed> $inventory
 * @param array<string, mixed> $meta
 */
function buildModuleDocument(string $module, array $inventory, array $meta): string
{
    $lines = [
        generatedBanner(),
        '# ' . $module,
        '',
        $meta['purpose'] ?? ('Entry map for the ' . $module . ' module.'),
        '',
        '## Verified paths',
        '',
        '### Routes',
        '',
    ];

    $lines[] = renderRouteLine(
        $inventory['route_web'],
        $meta['web_summary'] ?? genericRouteSummary('web', $inventory['route_prefixes'], (bool) $inventory['route_web_empty'])
    );
    $lines[] = renderRouteLine(
        $inventory['route_api'],
        $meta['api_summary'] ?? genericRouteSummary('api', $inventory['route_prefixes'], (bool) $inventory['route_api_empty']),
        'Modules/' . $module . '/Routes/api.php'
    );

    $lines[] = '';
    $lines[] = '### Controllers';
    $lines[] = '';
    $lines[] = '- ' . repoLink($inventory['controllers_root']);

    foreach ($inventory['controller_entries'] as $entry) {
        $description = $meta['controller_descriptions'][$entry['name']] ?? null;
        $lines[] = renderPathBullet($entry['path'], $entry['name'], $description);

        foreach ($entry['children'] as $child) {
            $childDescription = $meta['controller_descriptions'][$child['name']] ?? null;
            $lines[] = renderPathBullet($child['path'], $child['name'], $childDescription);
        }
    }

    $lines[] = '';
    $lines[] = '### Views';
    $lines[] = '';
    $lines[] = '- ' . repoLink($inventory['views_root']);

    foreach ($inventory['view_dirs'] as $viewDir) {
        $lines[] = '- ' . repoLink($inventory['views_root'] . '/' . $viewDir, $viewDir . '/');
    }

    foreach ($meta['extra_sections'] ?? [] as $section) {
        $lines[] = '';
        $lines[] = ($section['level'] ?? '###') . ' ' . $section['heading'];
        $lines[] = '';

        foreach ($section['bullets'] as $bullet) {
            $lines[] = '- ' . $bullet;
        }
    }

    $assetPaths = array_values(array_filter(
        $meta['asset_paths'] ?? [],
        static fn (string $path): bool => is_string($path) && $path !== ''
    ));
    if ($assetPaths !== []) {
        $lines[] = '';
        $lines[] = '### Assets / JS';
        $lines[] = '';
        foreach ($assetPaths as $assetPath) {
            $lines[] = '- ' . repoLink($assetPath);
        }
    }

    $keywords = buildModuleKeywords($module, $inventory['route_prefixes'], $meta['keywords'] ?? []);
    $lines[] = '';
    $lines[] = '## Search keywords';
    $lines[] = '';
    foreach ($keywords as $keyword) {
        $lines[] = '- `' . $keyword . '`';
    }

    $relatedDocs = buildRelatedDocs($inventory, $meta);
    $lines[] = '';
    $lines[] = '## Related docs';
    $lines[] = '';
    foreach ($relatedDocs as $doc) {
        $lines[] = '- ' . $doc;
    }

    $lines[] = '';
    $lines[] = '## Last reviewed';
    $lines[] = '';
    $lines[] = '- ' . LAST_REVIEWED_TOKEN;

    return implode("\n", $lines) . "\n";
}

function generatedBanner(): string
{
    return '<!-- Generated by scripts/generate-entrypoint-maps.php. -->';
}

function repoLink(string $repoRelativePath, ?string $label = null): string
{
    $normalizedPath = str_replace('\\', '/', $repoRelativePath);
    $display = $label ?? $normalizedPath;

    return '[' . $display . '](../../' . $normalizedPath . ')';
}

function repoRelativeLink(string $repoRelativePath, ?string $label = null): string
{
    $normalizedPath = str_replace('\\', '/', $repoRelativePath);
    $display = $label ?? $normalizedPath;

    return '[' . $display . '](../../' . $normalizedPath . ')';
}

function aiLink(string $filename): string
{
    return '[ai/' . $filename . '](../' . $filename . ')';
}

function renderRouteLine(?string $repoPath, string $summary, ?string $missingDisplayPath = null): string
{
    if ($repoPath === null) {
        return '- `' . ($missingDisplayPath ?? 'missing route file') . '` is not present in this checkout';
    }

    return '- ' . repoLink($repoPath) . ' — ' . $summary;
}

function renderPathBullet(string $repoPath, string $label, ?string $description = null): string
{
    $line = '- ' . repoLink($repoPath, $label);
    if ($description !== null && $description !== '') {
        $line .= ' — ' . $description;
    }

    return $line;
}

/**
 * @param array<int, string> $routePrefixes
 */
function genericRouteSummary(string $type, array $routePrefixes, bool $isEmpty): string
{
    if ($isEmpty) {
        return 'present but empty placeholder in this checkout';
    }

    if ($routePrefixes !== []) {
        $prefixes = array_map(
            static fn (string $prefix): string => '`' . $prefix . '`',
            array_slice($routePrefixes, 0, 5)
        );

        return 'verified module ' . $type . ' routes using prefixes ' . implode(', ', $prefixes);
    }

    return 'verified module ' . $type . ' routes';
}

/**
 * @param array<int, string> $routePrefixes
 * @param array<int, string> $metaKeywords
 * @return array<int, string>
 */
function buildModuleKeywords(string $module, array $routePrefixes, array $metaKeywords): array
{
    $keywords = array_merge([$module], $metaKeywords, $routePrefixes);
    $keywords = array_values(array_filter(array_map('trim', $keywords), static fn (string $value): bool => $value !== ''));
    $keywords = array_values(array_unique($keywords));

    return $keywords;
}

/**
 * @param array<string, mixed> $inventory
 * @param array<string, mixed> $meta
 * @return array<int, string>
 */
function buildRelatedDocs(array $inventory, array $meta): array
{
    $docs = $meta['related_docs'] ?? [
        aiLink('laravel-conventions.md'),
        aiLink('security-and-auth.md'),
    ];

    if (is_string($inventory['readme']) && $inventory['readme'] !== '') {
        $readmeLink = repoLink($inventory['readme']);
        if (!in_array($readmeLink, $docs, true)) {
            $docs[] = $readmeLink;
        }
    }

    return array_values(array_unique($docs));
}

/**
 * @param array<string, mixed> $inventory
 */
function genericLocalIndexNote(array $inventory): string
{
    if ($inventory['route_api'] === null) {
        return 'Local module folder present; no `Routes/api.php` file in this checkout';
    }

    if ((bool) $inventory['route_api_empty']) {
        return 'Local module folder present; `Routes/api.php` exists but is empty in this checkout';
    }

    if (is_string($inventory['readme']) && $inventory['readme'] !== '') {
        return 'Local module folder present; module README exists';
    }

    return 'Local module folder present';
}

function controllerPurpose(string $controllerFile): string
{
    $purposeMap = [
        'AccountController.php' => 'Accounting account management.',
        'AccountReportsController.php' => 'Accounting reports and summaries.',
        'AccountTypeController.php' => 'Account type management.',
        'BackUpController.php' => 'Backup creation and restore utilities.',
        'BarcodeController.php' => 'Barcode generation and printing flows.',
        'BrandController.php' => 'Brand management.',
        'BusinessController.php' => 'Business registration, settings, and configuration test actions.',
        'BusinessLocationController.php' => 'Business location management.',
        'CalendarController.php' => 'Calendar views and schedule CRUD.',
        'CashRegisterController.php' => 'Cash register open, close, and reporting flows.',
        'CombinedPurchaseReturnController.php' => 'Combined purchase return workflows.',
        'ContactController.php' => 'Customer and supplier CRUD, ledgers, and contact feeds.',
        'Controller.php' => 'Base app controller class used by other controllers.',
        'CustomerGroupController.php' => 'Customer group management.',
        'DashboardConfiguratorController.php' => 'Dashboard configuration and widget settings.',
        'DiscountController.php' => 'Discount management.',
        'DocumentAndNoteController.php' => 'Document and note attachment flows.',
        'ExpenseCategoryController.php' => 'Expense category management.',
        'ExpenseController.php' => 'Expense CRUD and expense reports.',
        'GlobalSearchController.php' => 'Global search endpoints across entities.',
        'GroupTaxController.php' => 'Group tax configuration.',
        'HomeController.php' => 'Dashboard and authenticated home screens.',
        'ImportOpeningStockController.php' => 'Opening stock import workflows.',
        'ImportProductsController.php' => 'Product import workflows.',
        'ImportSalesController.php' => 'Sales import workflows.',
        'InvoiceLayoutController.php' => 'Invoice layout management.',
        'InvoiceSchemeController.php' => 'Invoice numbering scheme management.',
        'LabelsController.php' => 'Label generation and printing.',
        'LedgerDiscountController.php' => 'Ledger discount maintenance.',
        'LocationSettingsController.php' => 'Location-specific settings.',
        'ManageUserController.php' => 'Admin user management and sign-in-as-user flows.',
        'MyFatoorahController.php' => 'MyFatoorah payment integration callbacks and redirects.',
        'NotificationController.php' => 'Notification listing and read/update flows.',
        'NotificationTemplateController.php' => 'Notification template management.',
        'OpeningStockController.php' => 'Opening stock CRUD flows.',
        'PesaPalController.php' => 'PesaPal payment integration callbacks and redirects.',
        'PrinterController.php' => 'Printer management.',
        'ProductController.php' => 'Product catalog CRUD, bulk actions, and detail flows.',
        'ProductQuoteController.php' => 'Quote creation entry points tied to products.',
        'ProductSalesOrderController.php' => 'Product-specific sales order entry points.',
        'PublicQuoteController.php' => 'Public quote viewing flows.',
        'PurchaseController.php' => 'Purchase transaction workflows.',
        'PurchaseOrderController.php' => 'Purchase order workflows.',
        'PurchaseRequisitionController.php' => 'Purchase requisition workflows.',
        'PurchaseReturnController.php' => 'Purchase return workflows.',
        'ReportController.php' => 'Business reporting endpoints.',
        'RoleController.php' => 'Role and permission management.',
        'SalesCommissionAgentController.php' => 'Sales commission agent management.',
        'SalesOrderController.php' => 'Sales order workflows.',
        'SellController.php' => 'Sales transaction workflows and sell screens.',
        'SellingPriceGroupController.php' => 'Selling price group management.',
        'SellPosController.php' => 'POS selling, invoices, and payment confirmation flows.',
        'SellReturnController.php' => 'Sell return workflows.',
        'StockAdjustmentController.php' => 'Stock adjustment workflows.',
        'StockTransferController.php' => 'Stock transfer workflows.',
        'TaxonomyController.php' => 'Taxonomy maintenance.',
        'TaxRateController.php' => 'Tax rate management.',
        'TransactionPaymentController.php' => 'Transaction payment actions.',
        'TypesOfServiceController.php' => 'Types of service management.',
        'UnifiedQuoteController.php' => 'Unified quote hub and quote search entry points.',
        'UnitController.php' => 'Unit management.',
        'UserController.php' => 'User profile and account updates.',
        'VariationTemplateController.php' => 'Variation template management.',
        'WarrantyController.php' => 'Warranty management.',
    ];

    if (isset($purposeMap[$controllerFile])) {
        return $purposeMap[$controllerFile];
    }

    $base = basename($controllerFile, '.php');
    $humanized = preg_replace('/([a-z])([A-Z])/', '$1 $2', preg_replace('/Controller$/', '', $base) ?? $base);

    return 'Handles ' . strtolower((string) $humanized) . ' flows.';
}

function controllerHint(string $className): string
{
    if ($className === 'Controller') {
        return 'Not directly routed; search subclasses under app/Http/Controllers and Modules/*/Http/Controllers.';
    }

    if ($className === 'MyFatoorahController') {
        return "rg -n 'MyFatoorah|MyFatoorahController' routes app/Providers";
    }

    if ($className === 'PesaPalController') {
        return "rg -n 'PesaPal|PesaPalController' routes app/Providers";
    }

    return "rg -n '" . $className . "' routes app/Providers";
}

function utilPurpose(string $utilFile): string
{
    $purposeMap = [
        'AccountTransactionUtil.php' => 'Accounting transaction helper logic.',
        'BusinessUtil.php' => 'Business settings, context, and tenant-scoped helper logic.',
        'CalendarEventUtil.php' => 'Calendar event scheduling helpers.',
        'CashRegisterUtil.php' => 'Cash register open/close/session helpers.',
        'ContactFeedUtil.php' => 'Contact feed aggregation, provider sync, and enrichment helpers.',
        'ContactUtil.php' => 'Contact CRUD helper logic and related business rules.',
        'GlobalSearchUtil.php' => 'Shared global-search query helpers.',
        'HomeMetronicDashboardUtil.php' => 'Dashboard metric and widget data helpers.',
        'InstallUtil.php' => 'Installation and setup helper logic.',
        'ModuleUtil.php' => 'Cross-module helper methods shared by enabled modules.',
        'NotificationUtil.php' => 'Notification dispatch and formatting helpers.',
        'NumberFormatUtil.php' => 'Number formatting and locale helper logic.',
        'ProductActivityLogUtil.php' => 'Product detail activity logging helpers.',
        'ProductCostingUtil.php' => 'Product costing and margin helper logic.',
        'ProductUtil.php' => 'Product CRUD, pricing, variation, and stock helper logic.',
        'QuoteUtil.php' => 'Quote creation and update helper logic.',
        'RestaurantUtil.php' => 'Restaurant/table-service helper logic.',
        'SalesOrderEditUtil.php' => 'Sales-order edit and mutation helper logic.',
        'TaxUtil.php' => 'Tax calculation and normalization helpers.',
        'TransactionUtil.php' => 'Transaction-level shared business helpers.',
        'UnifiedQuoteListUtil.php' => 'Shared quote listing and hub query helpers.',
        'Util.php' => 'General shared business helpers and formatting/parsing utilities.',
    ];

    if (isset($purposeMap[$utilFile])) {
        return $purposeMap[$utilFile];
    }

    $base = basename($utilFile, '.php');
    $humanized = preg_replace('/([a-z])([A-Z])/', '$1 $2', preg_replace('/Util$/', '', $base) ?? $base);

    return 'Shared helper logic for ' . strtolower((string) $humanized) . '.';
}

/**
 * @return array<string, array<string, mixed>>
 */
function moduleMetadata(): array
{
    return [
        'Aichat' => [
            'purpose' => 'Tenant-scoped AI chat entry map for web chat, Telegram ingress, shared conversation links, and quote-wizard flows.',
            'web_summary' => 'admin chat routes under `/aichat/chat/*`, Telegram webhook ingress under `/aichat/telegram/webhook/{webhookKey}`, and signed shared conversations under `/aichat/chat/shared/{conversation}`',
            'api_summary' => 'auth API placeholder route for `/aichat`',
            'index_trigger' => '`Aichat`, chat drawer, Telegram, quote wizard',
            'index_note' => 'Local module folder present; admin chat plus shared chat routes',
            'keywords' => ['aichat', 'chat', 'telegram', 'quote-wizard', 'conversations', 'shared'],
            'related_docs' => [
                aiLink('aichat-authz-baseline.md'),
                repoLink('Modules/Aichat/README.md'),
                aiLink('security-and-auth.md'),
                aiLink('product-copilot-patterns.md'),
            ],
        ],
        'Cms' => [
            'purpose' => 'Storefront and CMS-admin entry map for the root shopping pages, blogs, public contact pages, and `/cms/*` admin settings.',
            'web_summary' => 'storefront routes for `/`, `/shop/*`, backward-compatible `/c/*` redirects, and admin routes under `/cms/*`',
            'api_summary' => 'auth API placeholder route for `/cms`',
            'index_trigger' => '`Cms`, storefront, `shop/*`, blog, contact-us',
            'index_note' => 'Deepest storefront map in this folder',
            'controller_descriptions' => [
                'CmsController.php' => 'storefront, blogs, static product pages',
                'CmsPageController.php' => 'CMS page CRUD and `shop/page/{page}`',
            ],
            'keywords' => ['cms.home', 'shop/', 'cms-page', 'site-details', 'decor-store', 'contact-us', 'blog'],
            'related_docs' => [
                aiLink('ui-components.md'),
                aiLink('laravel-conventions.md'),
                aiLink('security-and-auth.md'),
            ],
            'extra_sections' => [
                [
                    'heading' => 'Route clusters worth reading first',
                    'level' => '###',
                    'bullets' => [
                        'Storefront home and page rendering: `/`, `/shop/page/{page}`',
                        'Blog pages: `/shop/blogs`, `/shop/blog/{slug}-{id}`',
                        'Contact and about pages: `/shop/contact-us`, `/shop/about-us`',
                        'Decor-store page set: `/shop/catalog`, `/shop/collections`, `/shop/product`, `/shop/cart`, `/shop/checkout`, `/shop/account`, `/shop/wishlist`, `/shop/faq`',
                        'Product pages: `/shop/products/bao-bi-cuon`, `/shop/products/hop-thung-carton`, `/shop/products/day-dai`, `/shop/products/air-silicagel`, `/shop/products/sanphamkhac`',
                        'Admin install and CMS maintenance: `/cms/install`, `cms-page` resource routes, `site-details` resource routes',
                    ],
                ],
                [
                    'heading' => 'Storefront layout chain',
                    'level' => '###',
                    'bullets' => [
                        repoLink('Modules/Cms/Resources/views/frontend/layouts/app.blade.php', 'frontend/layouts/app.blade.php'),
                        repoLink('Modules/Cms/Resources/views/frontend/layouts/header.blade.php', 'frontend/layouts/header.blade.php'),
                        repoLink('Modules/Cms/Resources/views/frontend/layouts/top.blade.php', 'frontend/layouts/top.blade.php'),
                        repoLink('Modules/Cms/Resources/views/frontend/layouts/navbar.blade.php', 'frontend/layouts/navbar.blade.php'),
                        repoLink('Modules/Cms/Resources/views/frontend/layouts/footer.blade.php', 'frontend/layouts/footer.blade.php'),
                    ],
                ],
                [
                    'heading' => 'Reference HTML and JS anchors',
                    'level' => '###',
                    'bullets' => [
                        repoLink('Modules/Cms/Resources/html/', 'Modules/Cms/Resources/html/'),
                        repoLink('Modules/Cms/Resources/html/demo-decor-store.html', 'demo-decor-store.html'),
                        repoLink('Modules/Cms/Resources/views/frontend/layouts/app.blade.php', 'frontend/layouts/app.blade.php') . ' loads the storefront asset stack and includes the chat-widget script partial',
                        repoLink('Modules/Cms/Resources/views/components/chat_widget/js/chat_widget-style1.blade.php', 'components/chat_widget/js/chat_widget-style1.blade.php'),
                    ],
                ],
                [
                    'heading' => 'Verified notes',
                    'level' => '##',
                    'bullets' => [
                        'These controller-returned view names do not have matching Blade files in this checkout, so keep them as notes instead of adding dead links: `cms::frontend.pages.custom_view`, `cms::create`, `cms::show`, `cms::edit`.',
                    ],
                ],
            ],
        ],
        'Essentials' => [
            'purpose' => 'Entry map for the Essentials and HRM module surfaces under `/essentials/*` and `/hrm/*`.',
            'web_summary' => 'dashboards, documents, todos, reminders, messaging, knowledge base, transcripts, and HRM routes',
            'api_summary' => 'present but empty placeholder in this checkout',
            'index_trigger' => '`Essentials`, HRM, attendance, payroll, leave, todo',
            'index_note' => 'Local module folder present; `Routes/api.php` exists but is empty in this checkout',
            'keywords' => ['essentials', 'hrm', 'attendance', 'payroll', 'leave', 'todo', 'knowledge-base', 'transcripts'],
            'related_docs' => [
                aiLink('laravel-conventions.md'),
                aiLink('security-and-auth.md'),
                aiLink('ui-components.md'),
            ],
        ],
        'Mailbox' => [
            'purpose' => 'Entry map for the admin mailbox module, including inbox views, account setup, OAuth callback wiring, and compose flows.',
            'web_summary' => '`/mailbox/*` inbox, accounts, compose, and install routes',
            'api_summary' => 'auth API placeholder route for `/mailbox`',
            'index_trigger' => '`Mailbox`, inbox, Gmail OAuth, IMAP, compose',
            'index_note' => 'Local module folder present; module README exists',
            'keywords' => ['mailbox', 'gmail', 'oauth', 'imap', 'compose', 'threads', 'attachments'],
            'related_docs' => [
                repoLink('Modules/Mailbox/README.md'),
                aiLink('laravel-conventions.md'),
                aiLink('security-and-auth.md'),
                aiLink('ui-components.md'),
            ],
        ],
        'Projectauto' => [
            'purpose' => 'Entry map for Projectauto tasks, workflow builder screens, settings, and API draft/publish routes.',
            'web_summary' => '`/projectauto/tasks/*`, `/projectauto/settings/*`, `/projectauto/workflows/*`, and workflow API endpoints under `/projectauto/api/*`',
            'api_summary' => 'auth API route for `/projectauto/tasks`',
            'index_trigger' => '`Projectauto`, tasks, workflows, builder',
            'index_note' => 'Local module folder present; workflow-wizard doc exists',
            'keywords' => ['projectauto', 'workflow', 'tasks', 'from-wizard', 'validate-draft', 'publish'],
            'asset_paths' => [
                'Modules/Projectauto/Resources/assets/workflow-builder/src/main.js',
            ],
            'related_docs' => [
                aiLink('projectauto-workflow-wizard.md'),
                aiLink('laravel-conventions.md'),
                aiLink('security-and-auth.md'),
            ],
        ],
        'StorageManager' => [
            'purpose' => 'Entry map for the warehouse and storage execution module under `/storage-manager/*`.',
            'web_summary' => '`/storage-manager/*` routes for settings, areas, slots, inbound, putaway, outbound, transfers, counts, damage, replenishment, and control tower',
            'index_trigger' => '`StorageManager`, warehouse, inbound, putaway, counts',
            'index_note' => 'Local module folder present; no `Routes/api.php` file in this checkout',
            'keywords' => ['storage-manager', 'control-tower', 'inbound', 'putaway', 'replenishment', 'counts', 'outbound', 'slots'],
            'related_docs' => [
                aiLink('laravel-conventions.md'),
                aiLink('security-and-auth.md'),
                aiLink('database-map.md'),
            ],
        ],
        'VasAccounting' => [
            'purpose' => 'Entry map for the `vas-accounting` module, including web UI routes, API routes, and the large finance controller surface.',
            'web_summary' => '`/vas-accounting/*` web routes for setup, vouchers, treasury, invoices, reports, integrations, closing, cutover, and more',
            'api_summary' => '`/vas-accounting/*` API routes for health, domains, posting previews, finance documents, treasury reconciliation, and provider webhooks',
            'index_trigger' => '`VasAccounting`, `vas-accounting`, vouchers, budgets, reports',
            'index_note' => 'Local module folder present; API controller subfolder exists',
            'keywords' => ['vas-accounting', 'voucher', 'cash-bank', 'payment-documents', 'procurement', 'closing', 'budget', 'integration', 'treasury'],
            'related_docs' => [
                aiLink('laravel-conventions.md'),
                aiLink('security-and-auth.md'),
                aiLink('database-map.md'),
            ],
        ],
    ];
}

/**
 * @param array<string, string> $documents
 * @param array<int, string> $expectedModuleDocs
 * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, string>, 3: array<int, string>}
 */
function syncDocuments(
    string $entrypointsDir,
    array $documents,
    array $expectedModuleDocs,
    string $today,
    bool $checkOnly
): array {
    $written = [];
    $unchanged = [];
    $removed = [];
    $issues = [];

    if (!is_dir($entrypointsDir)) {
        if ($checkOnly) {
            $issues[] = 'Missing ai/entrypoints directory.';
        } else {
            mkdir($entrypointsDir, 0777, true);
        }
    }

    foreach ($documents as $name => $tokenizedContent) {
        $path = $entrypointsDir . '/' . $name;
        $existing = is_file($path) ? (string) file_get_contents($path) : null;
        $existingNormalized = $existing !== null ? normalizeLastReviewed($existing) : null;

        if ($existingNormalized === $tokenizedContent) {
            $unchanged[] = $name;
            continue;
        }

        if ($checkOnly) {
            $issues[] = 'Would update ' . $name;
            continue;
        }

        $reviewedDate = $today;
        $finalContent = str_replace(LAST_REVIEWED_TOKEN, $reviewedDate, $tokenizedContent);
        file_put_contents($path, $finalContent);
        $written[] = $name;
    }

    $existingModuleDocs = [];
    foreach (glob($entrypointsDir . '/module-*.md') ?: [] as $absolute) {
        if (is_file($absolute)) {
            $existingModuleDocs[] = basename($absolute);
        }
    }

    foreach ($existingModuleDocs as $staleName) {
        if (in_array($staleName, $expectedModuleDocs, true)) {
            continue;
        }

        if ($checkOnly) {
            $issues[] = 'Would remove stale ' . $staleName;
            continue;
        }

        @unlink($entrypointsDir . '/' . $staleName);
        $removed[] = $staleName;
    }

    return [$written, $unchanged, $removed, $issues];
}

function extractLastReviewed(string $content): ?string
{
    if (preg_match('/## Last reviewed\s+\n\s*-\s+([0-9]{4}-[0-9]{2}-[0-9]{2})/m', $content, $matches) !== 1) {
        return null;
    }

    return $matches[1] ?? null;
}

function normalizeLastReviewed(string $content): string
{
    return (string) preg_replace(
        '/## Last reviewed\s+\n\s*-\s+[0-9]{4}-[0-9]{2}-[0-9]{2}/m',
        "## Last reviewed\n\n- " . LAST_REVIEWED_TOKEN,
        str_replace("\r\n", "\n", $content)
    );
}
