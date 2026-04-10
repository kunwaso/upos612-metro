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
    $generatedDir = $entrypointsDir . '/generated';
    $today = (new DateTimeImmutable('now'))->format('Y-m-d');
    $metadata = loadEntrypointMetadata($repoRoot);

    $inventory = buildInventory($repoRoot);
    $documents = buildDocuments($repoRoot, $inventory, $metadata['modules'] ?? []);
    $jsonDocuments = buildJsonDocuments($repoRoot, $inventory, $metadata);
    $expectedModuleDocs = array_values(array_filter(
        array_keys($documents),
        static fn (string $name): bool => str_starts_with($name, 'module-')
    ));
    $expectedJsonDocs = array_keys($jsonDocuments);

    [$writtenMd, $unchangedMd, $removedMd, $issuesMd] = syncDocuments(
        $entrypointsDir,
        $documents,
        $expectedModuleDocs,
        $today,
        $checkOnly
    );
    [$writtenJson, $unchangedJson, $removedJson, $issuesJson] = syncJsonDocuments(
        $generatedDir,
        $jsonDocuments,
        $expectedJsonDocs,
        $today,
        $checkOnly
    );
    $issues = array_merge($issuesMd, $issuesJson);
    $written = array_merge($writtenMd, $writtenJson);
    $unchanged = array_merge($unchangedMd, $unchangedJson);
    $removed = array_merge($removedMd, $removedJson);

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
    fwrite(STDOUT, 'Markdown written: ' . count($writtenMd) . "\n");
    fwrite(STDOUT, 'Markdown unchanged: ' . count($unchangedMd) . "\n");
    fwrite(STDOUT, 'Markdown removed stale: ' . count($removedMd) . "\n");
    fwrite(STDOUT, 'JSON written: ' . count($writtenJson) . "\n");
    fwrite(STDOUT, 'JSON unchanged: ' . count($unchangedJson) . "\n");
    fwrite(STDOUT, 'JSON removed stale: ' . count($removedJson) . "\n");

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
 * @return array{modules: array<string, array<string, mixed>>, core_maps: array<string, array<string, mixed>>, compatibility: array<string, mixed>}
 */
function loadEntrypointMetadata(string $repoRoot): array
{
    $metadataPath = $repoRoot . '/ai/entrypoints/metadata.php';
    if (!is_file($metadataPath)) {
        return [
            'modules' => [],
            'core_maps' => [],
            'compatibility' => [],
        ];
    }

    $loaded = require $metadataPath;
    if (!is_array($loaded)) {
        fwrite(STDERR, "Invalid ai/entrypoints/metadata.php payload. Expected array.\n");

        return [
            'modules' => [],
            'core_maps' => [],
            'compatibility' => [],
        ];
    }

    return [
        'modules' => is_array($loaded['modules'] ?? null) ? $loaded['modules'] : [],
        'core_maps' => is_array($loaded['core_maps'] ?? null) ? $loaded['core_maps'] : [],
        'compatibility' => is_array($loaded['compatibility'] ?? null) ? $loaded['compatibility'] : [],
    ];
}

/**
 * @return array{
 *   enabled_modules: array<int, string>,
 *   local_modules: array<int, string>,
 *   root_controllers: array<int, string>,
 *   controller_sections: array<string, array<int, string>>,
 *   utils: array<int, string>,
 *   other_utils: array<int, string>,
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
    $rootControllers = listFiles($repoRoot . '/app/Http/Controllers', '*.php');
    $controllerSections = [
        'Auth' => listFiles($repoRoot . '/app/Http/Controllers/Auth', '*.php'),
        'Install' => listFiles($repoRoot . '/app/Http/Controllers/Install', '*.php'),
        'Restaurant' => listFiles($repoRoot . '/app/Http/Controllers/Restaurant', '*.php'),
    ];
    $utils = listFiles($repoRoot . '/app/Utils', '*Util.php');
    $allUtils = listFiles($repoRoot . '/app/Utils', '*.php');
    $otherUtils = array_values(array_diff($allUtils, $utils));
    sort($otherUtils, SORT_NATURAL | SORT_FLAG_CASE);

    $modules = [];
    foreach ($localModules as $module) {
        $modules[$module] = collectModuleInventory($repoRoot, $module);
    }

    return [
        'enabled_modules' => $enabledModules,
        'local_modules' => $localModules,
        'root_controllers' => $rootControllers,
        'controller_sections' => $controllerSections,
        'utils' => $utils,
        'other_utils' => $otherUtils,
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
 * @return array<int, string>
 */
function collectPhpFilesRecursive(string $repoRoot, string $directory): array
{
    return collectFilesByExtensionsRecursive($repoRoot, $directory, ['php']);
}

/**
 * @param array<int, string> $extensions
 * @return array<int, string>
 */
function collectFilesByExtensionsRecursive(string $repoRoot, string $directory, array $extensions): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $normalizedExtensions = array_values(array_filter(array_map(
        static fn (string $extension): string => strtolower(trim($extension, '. ')),
        $extensions
    )));
    if ($normalizedExtensions === []) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $entry */
    foreach ($iterator as $entry) {
        if (!$entry->isFile()) {
            continue;
        }

        $extension = strtolower($entry->getExtension());
        if (!in_array($extension, $normalizedExtensions, true)) {
            continue;
        }

        $files[] = toRepoRelative($repoRoot, $entry->getPathname());
    }

    $files = array_values(array_unique($files));
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return $files;
}

/**
 * @param array<int, string> $controllerFiles
 * @return array{requests: array<int, string>, services: array<int, string>, utils: array<int, string>, models: array<int, string>}
 */
function collectControllerDependencies(string $repoRoot, array $controllerFiles): array
{
    $dependencies = [
        'requests' => [],
        'services' => [],
        'utils' => [],
        'models' => [],
    ];

    foreach ($controllerFiles as $relativePath) {
        $absolutePath = $repoRoot . '/' . $relativePath;
        if (!is_file($absolutePath)) {
            continue;
        }

        $content = (string) file_get_contents($absolutePath);
        if ($content === '') {
            continue;
        }

        $symbols = extractDependencySymbols($content);

        foreach ($symbols as $symbol) {
            classifyDependencySymbol($symbol, $dependencies);
        }
    }

    foreach ($dependencies as $category => $values) {
        $deduped = array_values(array_unique($values));
        sort($deduped, SORT_NATURAL | SORT_FLAG_CASE);
        $dependencies[$category] = $deduped;
    }

    return $dependencies;
}

/**
 * Extract dependency class names from a PHP file's content using multiple strategies:
 * 1. `use` import statements
 * 2. Constructor type-hints
 * 3. Method parameter type-hints (action injection)
 * 4. Inline `new ClassName(...)` instantiations
 * 5. Static method calls `ClassName::method(...)`
 *
 * @return array<int, string>
 */
function extractDependencySymbols(string $content): array
{
    $symbols = [];

    if (preg_match_all('/^use\s+([^;]+);/m', $content, $matches) > 0) {
        foreach ($matches[1] as $match) {
            if (is_string($match)) {
                $symbols[] = trim($match);
            }
        }
    }

    if (preg_match_all('/function\s+\w+\s*\((.*?)\)/s', $content, $methods) > 0) {
        foreach ($methods[1] as $signature) {
            if (!is_string($signature) || $signature === '') {
                continue;
            }

            if (preg_match_all('/([\\\\A-Za-z_][\\\\A-Za-z0-9_]*)\s+\$/', $signature, $typed) > 0) {
                foreach ($typed[1] as $typeMatch) {
                    if (is_string($typeMatch)) {
                        $symbols[] = trim($typeMatch, "\\ \t\n\r\0\x0B");
                    }
                }
            }
        }
    }

    if (preg_match_all('/new\s+([\\\\A-Za-z_][\\\\A-Za-z0-9_]*)\s*\(/', $content, $newCalls) > 0) {
        foreach ($newCalls[1] as $className) {
            if (is_string($className)) {
                $symbols[] = trim($className, "\\ \t\n\r\0\x0B");
            }
        }
    }

    if (preg_match_all('/([\\\\A-Za-z_][\\\\A-Za-z0-9_]*)::(?!class\b)\w+\s*\(/', $content, $staticCalls) > 0) {
        foreach ($staticCalls[1] as $className) {
            if (is_string($className) && !in_array(strtolower($className), ['self', 'static', 'parent', 'route', 'auth', 'view', 'response', 'config', 'cache', 'log', 'db', 'app', 'request', 'session', 'event', 'validator', 'redirect', 'url', 'lang', 'str', 'arr', 'collect'], true)) {
                $symbols[] = trim($className, "\\ \t\n\r\0\x0B");
            }
        }
    }

    return $symbols;
}

/**
 * Classify a dependency symbol into requests, services, utils, or models.
 *
 * @param array{requests: array<int, string>, services: array<int, string>, utils: array<int, string>, models: array<int, string>} $dependencies
 */
function classifyDependencySymbol(string $symbol, array &$dependencies): void
{
    $normalized = trim($symbol, "\\ \t\n\r\0\x0B");
    if ($normalized === '') {
        return;
    }

    $lc = strtolower($normalized);

    if (str_contains($lc, '\\http\\requests\\') || str_ends_with($lc, 'request')) {
        if (!str_ends_with($lc, '\\request') && $lc !== 'request' && $lc !== 'illuminate\\http\\request') {
            $dependencies['requests'][] = $normalized;
            return;
        }
    }

    if (str_contains($lc, '\\http\\requests\\')) {
        $dependencies['requests'][] = $normalized;
        return;
    }

    if (str_contains($lc, '\\services\\')) {
        $dependencies['services'][] = $normalized;
        return;
    }

    if (str_contains($lc, '\\utils\\')) {
        $dependencies['utils'][] = $normalized;
        return;
    }

    if (str_contains($lc, '\\entities\\') || str_contains($lc, '\\models\\')) {
        $dependencies['models'][] = $normalized;
        return;
    }

    $shortLc = strtolower(basename(str_replace('\\', '/', $normalized)));
    if (str_ends_with($shortLc, 'util') && $shortLc !== 'util') {
        $dependencies['utils'][] = $normalized;
        return;
    }
    if (str_ends_with($shortLc, 'service') && $shortLc !== 'service') {
        $dependencies['services'][] = $normalized;
    }
}

/**
 * @return array<string, mixed>
 */
function collectModuleInventory(string $repoRoot, string $module): array
{
    $moduleBase = 'Modules/' . $module;
    $moduleRoot = $repoRoot . '/' . $moduleBase;
    $webPath = 'Modules/' . $module . '/Routes/web.php';
    $apiPath = 'Modules/' . $module . '/Routes/api.php';
    $controllersRoot = 'Modules/' . $module . '/Http/Controllers';
    $requestsRoot = 'Modules/' . $module . '/Http/Requests';
    $viewsRoot = 'Modules/' . $module . '/Resources/views';
    $servicesRoot = 'Modules/' . $module . '/Services';
    $utilsRoot = 'Modules/' . $module . '/Utils';
    $entitiesRoot = 'Modules/' . $module . '/Entities';
    $modelsRoot = 'Modules/' . $module . '/Models';
    $jobsRoot = 'Modules/' . $module . '/Jobs';
    $notificationsRoot = 'Modules/' . $module . '/Notifications';
    $assetsRoot = 'Modules/' . $module . '/Resources/assets';
    $testsRoot = 'Modules/' . $module . '/Tests';

    $routeWebAbsolute = $repoRoot . '/' . $webPath;
    $routeApiAbsolute = $repoRoot . '/' . $apiPath;
    $controllerFiles = collectPhpFilesRecursive($repoRoot, $repoRoot . '/' . $controllersRoot);
    $modelPaths = array_values(array_unique(array_merge(
        collectPhpFilesRecursive($repoRoot, $repoRoot . '/' . $entitiesRoot),
        collectPhpFilesRecursive($repoRoot, $repoRoot . '/' . $modelsRoot)
    )));
    sort($modelPaths, SORT_NATURAL | SORT_FLAG_CASE);

    return [
        'name' => $module,
        'readme' => detectModuleReadme($repoRoot, $moduleRoot),
        'module_root' => $moduleBase,
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
        'controller_files' => $controllerFiles,
        'requests_root' => $requestsRoot,
        'request_files' => collectPhpFilesRecursive($repoRoot, $repoRoot . '/' . $requestsRoot),
        'views_root' => $viewsRoot,
        'view_dirs' => listDirectories($repoRoot . '/' . $viewsRoot),
        'services_root' => $servicesRoot,
        'service_files' => collectPhpFilesRecursive($repoRoot, $repoRoot . '/' . $servicesRoot),
        'utils_root' => $utilsRoot,
        'util_files' => collectPhpFilesRecursive($repoRoot, $repoRoot . '/' . $utilsRoot),
        'models' => $modelPaths,
        'jobs_root' => $jobsRoot,
        'job_files' => collectPhpFilesRecursive($repoRoot, $repoRoot . '/' . $jobsRoot),
        'notifications_root' => $notificationsRoot,
        'notification_files' => collectPhpFilesRecursive($repoRoot, $repoRoot . '/' . $notificationsRoot),
        'assets_root' => $assetsRoot,
        'asset_files' => collectFilesByExtensionsRecursive($repoRoot, $repoRoot . '/' . $assetsRoot, ['js', 'ts', 'vue', 'css', 'scss']),
        'tests_root' => $testsRoot,
        'test_files' => collectPhpFilesRecursive($repoRoot, $repoRoot . '/' . $testsRoot),
        'dependencies' => collectControllerDependencies($repoRoot, $controllerFiles),
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
 *   root_controllers: array<int, string>,
 *   controller_sections: array<string, array<int, string>>,
 *   utils: array<int, string>,
 *   other_utils: array<int, string>,
 *   modules: array<string, array<string, mixed>>
 * } $inventory
 * @param array<string, array<string, mixed>> $moduleMeta
 * @return array<string, string>
 */
function buildDocuments(string $repoRoot, array $inventory, array $moduleMeta): array
{
    $documents = [];

    $documents['README.md'] = buildReadmeDocument();
    $documents['_TEMPLATE.md'] = buildTemplateDocument();
    $documents['core-http-entry.md'] = buildCoreHttpEntryDocument();
    $documents['core-http-controllers.md'] = buildCoreHttpControllersDocument(
        $inventory['root_controllers'],
        $inventory['controller_sections']
    );
    $documents['core-utils-index.md'] = buildCoreUtilsIndexDocument(
        $inventory['utils'],
        $inventory['other_utils']
    );

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

/**
 * @param array{
 *   modules: array<string, array<string, mixed>>,
 *   core_maps: array<string, array<string, mixed>>
 * } $metadata
 * @return array<string, string>
 */
function buildJsonDocuments(string $repoRoot, array $inventory, array $metadata): array
{
    $moduleMeta = is_array($metadata['modules'] ?? null) ? $metadata['modules'] : [];
    $coreMeta = is_array($metadata['core_maps'] ?? null) ? $metadata['core_maps'] : [];

    $documents = [];
    $documents['index.json'] = encodeJsonDocument(buildIndexMapContract(
        $inventory['enabled_modules'],
        $inventory['local_modules'],
        $inventory['modules'],
        $moduleMeta
    ));

    $documents['core-http-entry.json'] = encodeJsonDocument(buildCoreHttpEntryMapContract(
        $inventory,
        $coreMeta['core-http-entry'] ?? []
    ));
    $documents['core-http-controllers.json'] = encodeJsonDocument(buildCoreHttpControllersMapContract(
        $inventory,
        $coreMeta['core-http-controllers'] ?? []
    ));
    $documents['core-utils-index.json'] = encodeJsonDocument(buildCoreUtilsMapContract(
        $inventory,
        $coreMeta['core-utils-index'] ?? []
    ));

    foreach ($inventory['local_modules'] as $module) {
        /** @var array<string, mixed> $moduleInventory */
        $moduleInventory = $inventory['modules'][$module];
        $documents['module-' . $module . '.json'] = encodeJsonDocument(
            buildModuleMapContract($module, $moduleInventory, $moduleMeta[$module] ?? [])
        );
    }

    return $documents;
}

/**
 * @param array<string, mixed> $inventory
 * @param array<string, mixed> $meta
 * @return array<string, mixed>
 */
function buildModuleMapContract(string $module, array $inventory, array $meta): array
{
    $keywords = buildModuleKeywords($module, $inventory['route_prefixes'], $meta['keywords'] ?? []);
    $verifyCommands = buildVerifyCommands($module, $meta['verify_commands'] ?? []);
    $tests = array_values(array_unique(array_merge(
        is_array($meta['tests'] ?? null) ? $meta['tests'] : [],
        collectRootPaths($inventory['test_files'] ?? [])
    )));
    sort($tests, SORT_NATURAL | SORT_FLAG_CASE);

    $map = [
        'kind' => 'module',
        'title' => $module,
        'purpose' => $meta['purpose'] ?? ('Entry map for the ' . $module . ' module.'),
        'triggers' => normalizeTriggerList($meta['use_when'] ?? [], $meta['index_trigger'] ?? null, $module, $inventory['route_prefixes']),
        'verified_paths' => [
            'routes' => [
                routeContractItem(
                    $inventory['route_web'],
                    $meta['web_summary'] ?? genericRouteSummary('web', $inventory['route_prefixes'], (bool) $inventory['route_web_empty'])
                ),
                routeContractItem(
                    $inventory['route_api'],
                    $meta['api_summary'] ?? genericRouteSummary('api', $inventory['route_prefixes'], (bool) $inventory['route_api_empty'])
                ),
            ],
            'controllers' => array_values(array_unique(array_merge(
                [$inventory['controllers_root']],
                array_map(static fn (array $entry): string => (string) $entry['path'], $inventory['controller_entries'] ?? []),
                $inventory['controller_files'] ?? []
            ))),
            'views' => array_values(array_unique(array_merge(
                [$inventory['views_root']],
                array_map(
                    fn (string $viewDir): string => $inventory['views_root'] . '/' . $viewDir,
                    $inventory['view_dirs'] ?? []
                )
            ))),
            'requests' => array_values(array_unique(array_merge(
                pathIfDirectoryExists($inventory['requests_root'], $inventory['request_files']),
                $inventory['request_files'] ?? []
            ))),
            'services' => array_values(array_unique(array_merge(
                pathIfDirectoryExists($inventory['services_root'], $inventory['service_files']),
                $inventory['service_files'] ?? []
            ))),
            'utils' => array_values(array_unique(array_merge(
                pathIfDirectoryExists($inventory['utils_root'], $inventory['util_files']),
                $inventory['util_files'] ?? []
            ))),
            'models' => array_values($inventory['models'] ?? []),
            'jobs' => array_values(array_unique(array_merge(
                pathIfDirectoryExists($inventory['jobs_root'], $inventory['job_files']),
                $inventory['job_files'] ?? []
            ))),
            'notifications' => array_values(array_unique(array_merge(
                pathIfDirectoryExists($inventory['notifications_root'], $inventory['notification_files']),
                $inventory['notification_files'] ?? []
            ))),
            'assets' => array_values(array_unique(array_merge(
                pathIfDirectoryExists($inventory['assets_root'], $inventory['asset_files']),
                $inventory['asset_files'] ?? [],
                is_array($meta['asset_paths'] ?? null) ? $meta['asset_paths'] : []
            ))),
            'tests' => array_values(array_unique(array_merge(
                pathIfDirectoryExists($inventory['tests_root'], $inventory['test_files']),
                $inventory['test_files'] ?? []
            ))),
        ],
        'route_prefixes' => array_values($inventory['route_prefixes']),
        'search_keywords' => $keywords,
        'related_docs' => normalizeRelatedDocPaths($inventory, $meta),
        'workflows' => normalizeNamedPathBlocks($meta['workflows'] ?? []),
        'edit_bundles' => normalizeNamedPathBlocks($meta['edit_bundles'] ?? []),
        'dependencies' => [
            'requests' => array_values($inventory['dependencies']['requests'] ?? []),
            'services' => array_values($inventory['dependencies']['services'] ?? []),
            'utils' => array_values($inventory['dependencies']['utils'] ?? []),
            'models' => array_values($inventory['dependencies']['models'] ?? []),
        ],
        'tests' => $tests,
        'verify_commands' => $verifyCommands,
        'last_reviewed' => LAST_REVIEWED_TOKEN,
    ];

    return normalizeMapContract($map);
}

/**
 * @param array<string, mixed> $inventory
 * @param array<string, mixed> $meta
 * @return array<string, mixed>
 */
function buildCoreHttpEntryMapContract(array $inventory, array $meta): array
{
    $map = [
        'kind' => 'core',
        'title' => $meta['title'] ?? 'Core HTTP Entry',
        'purpose' => $meta['purpose'] ?? 'Use this map when the task is in the root Laravel app rather than a `Modules/*` package.',
        'triggers' => normalizeStringList($meta['triggers'] ?? ['Core (root)', 'root routes', 'app/Http/Controllers', 'app/Utils']),
        'verified_paths' => [
            'routes' => [
                routeContractItem('routes/web.php', 'main root web routes; includes routes/install_r.php'),
                routeContractItem('routes/api.php', 'root API routes'),
                routeContractItem('routes/install_r.php', 'install/bootstrap route file pulled into web.php'),
                routeContractItem('routes/channels.php', 'broadcast authorization hooks'),
            ],
            'controllers' => [
                'app/Http/Controllers',
                'app/Http/Controllers/Auth',
                'app/Http/Controllers/Install',
                'app/Http/Controllers/Restaurant',
            ],
            'views' => [],
            'requests' => ['app/Http/Requests'],
            'services' => [],
            'utils' => ['app/Utils'],
            'models' => [],
            'jobs' => ['app/Jobs'],
            'notifications' => ['app/Notifications'],
            'assets' => ['resources/js', 'resources/css'],
            'tests' => ['tests/Feature'],
        ],
        'route_prefixes' => [],
        'search_keywords' => normalizeStringList($meta['search_keywords'] ?? ['routes/web.php', 'routes/api.php', 'install_r.php', 'App\\Http\\Controllers']),
        'related_docs' => normalizeRelatedDocPaths([], ['related_docs' => $meta['related_docs'] ?? []]),
        'workflows' => normalizeNamedPathBlocks($meta['workflows'] ?? []),
        'edit_bundles' => normalizeNamedPathBlocks($meta['edit_bundles'] ?? []),
        'dependencies' => [
            'requests' => [],
            'services' => [],
            'utils' => [],
            'models' => [],
        ],
        'tests' => normalizeStringList($meta['tests'] ?? ['tests/Feature']),
        'verify_commands' => normalizeStringList($meta['verify_commands'] ?? ['php artisan route:list']),
        'last_reviewed' => LAST_REVIEWED_TOKEN,
    ];

    return normalizeMapContract($map);
}

/**
 * @param array<string, mixed> $inventory
 * @param array<string, mixed> $meta
 * @return array<string, mixed>
 */
function buildCoreHttpControllersMapContract(array $inventory, array $meta): array
{
    $controllers = array_values(array_unique(array_merge(
        array_map(static fn (string $name): string => 'app/Http/Controllers/' . $name, $inventory['root_controllers']),
        array_map(static fn (string $name): string => 'app/Http/Controllers/Auth/' . $name, $inventory['controller_sections']['Auth'] ?? []),
        array_map(static fn (string $name): string => 'app/Http/Controllers/Install/' . $name, $inventory['controller_sections']['Install'] ?? []),
        array_map(static fn (string $name): string => 'app/Http/Controllers/Restaurant/' . $name, $inventory['controller_sections']['Restaurant'] ?? [])
    )));
    sort($controllers, SORT_NATURAL | SORT_FLAG_CASE);

    $map = [
        'kind' => 'core',
        'title' => $meta['title'] ?? 'Core HTTP Controllers',
        'purpose' => $meta['purpose'] ?? 'Verified root controller index for root, Auth, Install, and Restaurant surfaces.',
        'triggers' => normalizeStringList($meta['triggers'] ?? ['root controller edits', 'Auth controller', 'Install controller', 'Restaurant controller']),
        'verified_paths' => [
            'routes' => [
                routeContractItem('routes/web.php', 'primary root route declarations'),
                routeContractItem('routes/api.php', 'root API route declarations'),
            ],
            'controllers' => array_values(array_unique(array_merge(
                ['app/Http/Controllers', 'app/Http/Controllers/Auth', 'app/Http/Controllers/Install', 'app/Http/Controllers/Restaurant'],
                $controllers
            ))),
            'views' => [],
            'requests' => ['app/Http/Requests'],
            'services' => [],
            'utils' => [],
            'models' => [],
            'jobs' => [],
            'notifications' => [],
            'assets' => [],
            'tests' => ['tests/Feature'],
        ],
        'route_prefixes' => [],
        'search_keywords' => normalizeStringList($meta['search_keywords'] ?? ['Controller.php', 'App\\Http\\Controllers', 'Auth', 'Install', 'Restaurant']),
        'related_docs' => normalizeRelatedDocPaths([], ['related_docs' => $meta['related_docs'] ?? []]),
        'workflows' => normalizeNamedPathBlocks($meta['workflows'] ?? []),
        'edit_bundles' => normalizeNamedPathBlocks($meta['edit_bundles'] ?? []),
        'dependencies' => [
            'requests' => [],
            'services' => [],
            'utils' => [],
            'models' => [],
        ],
        'tests' => normalizeStringList($meta['tests'] ?? ['tests/Feature']),
        'verify_commands' => normalizeStringList($meta['verify_commands'] ?? ['php artisan test --filter=Feature']),
        'last_reviewed' => LAST_REVIEWED_TOKEN,
    ];

    return normalizeMapContract($map);
}

/**
 * @param array<string, mixed> $inventory
 * @param array<string, mixed> $meta
 * @return array<string, mixed>
 */
function buildCoreUtilsMapContract(array $inventory, array $meta): array
{
    $utils = array_map(static fn (string $name): string => 'app/Utils/' . $name, $inventory['utils']);
    $otherUtils = array_map(static fn (string $name): string => 'app/Utils/' . $name, $inventory['other_utils']);
    $files = array_values(array_unique(array_merge($utils, $otherUtils)));
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    $map = [
        'kind' => 'core',
        'title' => $meta['title'] ?? 'Core Utils Index',
        'purpose' => $meta['purpose'] ?? 'Primary utility index for app/Utils and shared helper/service files.',
        'triggers' => normalizeStringList($meta['triggers'] ?? ['shared util refactor', 'App\\Utils dependency']),
        'verified_paths' => [
            'routes' => [],
            'controllers' => ['app/Http/Controllers'],
            'views' => [],
            'requests' => ['app/Http/Requests'],
            'services' => ['app/Services'],
            'utils' => array_values(array_unique(array_merge(['app/Utils'], $files))),
            'models' => [],
            'jobs' => [],
            'notifications' => [],
            'assets' => [],
            'tests' => ['tests/Unit', 'tests/Feature'],
        ],
        'route_prefixes' => [],
        'search_keywords' => normalizeStringList($meta['search_keywords'] ?? ['Util.php', 'App\\Utils']),
        'related_docs' => normalizeRelatedDocPaths([], ['related_docs' => $meta['related_docs'] ?? []]),
        'workflows' => normalizeNamedPathBlocks($meta['workflows'] ?? []),
        'edit_bundles' => normalizeNamedPathBlocks($meta['edit_bundles'] ?? []),
        'dependencies' => [
            'requests' => [],
            'services' => [],
            'utils' => [],
            'models' => [],
        ],
        'tests' => normalizeStringList($meta['tests'] ?? ['tests/Unit', 'tests/Feature']),
        'verify_commands' => normalizeStringList($meta['verify_commands'] ?? ['php artisan test --filter=Unit']),
        'last_reviewed' => LAST_REVIEWED_TOKEN,
    ];

    return normalizeMapContract($map);
}

/**
 * @param array<int, string> $enabledModules
 * @param array<int, string> $localModules
 * @param array<string, array<string, mixed>> $moduleInventories
 * @param array<string, array<string, mixed>> $moduleMeta
 * @return array<string, mixed>
 */
function buildIndexMapContract(
    array $enabledModules,
    array $localModules,
    array $moduleInventories,
    array $moduleMeta
): array {
    $entries = [
        [
            'trigger' => 'Core (root), root routes, app/Http/Controllers, app/Utils',
            'map' => 'core-http-entry',
            'notes' => 'Deeper root maps: core-http-controllers, core-utils-index',
            'status' => 'local',
        ],
    ];

    foreach ($localModules as $module) {
        $meta = $moduleMeta[$module] ?? [];
        $entries[] = [
            'trigger' => sanitizeTriggerText((string) ($meta['index_trigger'] ?? $module)),
            'map' => 'module-' . $module,
            'notes' => (string) ($meta['index_note'] ?? genericLocalIndexNote($moduleInventories[$module])),
            'status' => 'local',
        ];
    }

    $localLookup = array_flip($localModules);
    foreach ($enabledModules as $module) {
        if (isset($localLookup[$module])) {
            continue;
        }

        $entries[] = [
            'trigger' => $module,
            'map' => null,
            'notes' => 'not in checkout',
            'status' => 'enabled_missing',
        ];
    }

    foreach ($localModules as $module) {
        if (in_array($module, $enabledModules, true)) {
            continue;
        }

        $entries[] = [
            'trigger' => $module,
            'map' => 'module-' . $module,
            'notes' => 'present locally; not listed in modules_statuses.json',
            'status' => 'local_not_enabled',
        ];
    }

    $map = [
        'kind' => 'index',
        'title' => 'Entry Map Index',
        'purpose' => 'Start here when the correct repo area is unclear.',
        'triggers' => ['unclear repo area', 'where do I start'],
        'verified_paths' => [
            'routes' => [],
            'controllers' => [],
            'views' => [],
            'requests' => [],
            'services' => [],
            'utils' => [],
            'models' => [],
            'jobs' => [],
            'notifications' => [],
            'assets' => [],
            'tests' => [],
        ],
        'route_prefixes' => [],
        'search_keywords' => ['core-http-entry', 'module-', 'entrypoints'],
        'related_docs' => ['ai/entrypoints/README.md', 'ai/entrypoints/_TEMPLATE.md'],
        'workflows' => [],
        'edit_bundles' => [],
        'dependencies' => [
            'requests' => [],
            'services' => [],
            'utils' => [],
            'models' => [],
        ],
        'tests' => [],
        'verify_commands' => ['composer entrypoints:check', 'composer entrypoints:test'],
        'entries' => $entries,
        'last_reviewed' => LAST_REVIEWED_TOKEN,
    ];

    return normalizeMapContract($map);
}

/**
 * @param array<string, mixed> $map
 * @return array<string, mixed>
 */
function normalizeMapContract(array $map): array
{
    $defaults = [
        'kind' => 'module',
        'title' => '',
        'purpose' => '',
        'triggers' => [],
        'verified_paths' => [
            'routes' => [],
            'controllers' => [],
            'views' => [],
            'requests' => [],
            'services' => [],
            'utils' => [],
            'models' => [],
            'jobs' => [],
            'notifications' => [],
            'assets' => [],
            'tests' => [],
        ],
        'route_prefixes' => [],
        'search_keywords' => [],
        'related_docs' => [],
        'workflows' => [],
        'edit_bundles' => [],
        'dependencies' => [
            'requests' => [],
            'services' => [],
            'utils' => [],
            'models' => [],
        ],
        'tests' => [],
        'verify_commands' => [],
        'last_reviewed' => LAST_REVIEWED_TOKEN,
    ];

    $normalized = array_merge($defaults, $map);
    $normalized['triggers'] = normalizeStringList($normalized['triggers']);
    $normalized['route_prefixes'] = normalizeStringList($normalized['route_prefixes']);
    $normalized['search_keywords'] = normalizeStringList($normalized['search_keywords']);
    $normalized['related_docs'] = normalizeStringList($normalized['related_docs']);
    $normalized['tests'] = normalizeStringList($normalized['tests']);
    $normalized['verify_commands'] = normalizeStringList($normalized['verify_commands']);
    $normalized['workflows'] = normalizeNamedPathBlocks($normalized['workflows']);
    $normalized['edit_bundles'] = normalizeNamedPathBlocks($normalized['edit_bundles']);

    foreach ($normalized['verified_paths'] as $section => $paths) {
        if ($section === 'routes') {
            /** @var array<int, array<string, mixed>> $paths */
            $normalized['verified_paths'][$section] = array_values(array_map(
                static fn (array $item): array => [
                    'path' => is_string($item['path'] ?? null) ? $item['path'] : null,
                    'summary' => is_string($item['summary'] ?? null) ? $item['summary'] : '',
                    'exists' => (bool) ($item['exists'] ?? false),
                ],
                $paths
            ));
            continue;
        }

        $normalized['verified_paths'][$section] = normalizeStringList(is_array($paths) ? $paths : []);
    }

    foreach ($normalized['dependencies'] as $key => $values) {
        $normalized['dependencies'][$key] = normalizeStringList(is_array($values) ? $values : []);
    }

    return $normalized;
}

/**
 * @param array<int, mixed> $raw
 * @return array<int, array{name: string, paths: array<int, string>, notes: string}>
 */
function normalizeNamedPathBlocks(array $raw): array
{
    $result = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }

        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            continue;
        }

        $result[] = [
            'name' => $name,
            'paths' => normalizeStringList(is_array($item['paths'] ?? null) ? $item['paths'] : []),
            'notes' => trim((string) ($item['notes'] ?? '')),
        ];
    }

    return $result;
}

/**
 * @param array<int, mixed> $values
 * @return array<int, string>
 */
function normalizeStringList(array $values): array
{
    $normalized = [];
    foreach ($values as $value) {
        if (!is_string($value)) {
            continue;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            continue;
        }

        $normalized[] = $trimmed;
    }

    $normalized = array_values(array_unique($normalized));
    sort($normalized, SORT_NATURAL | SORT_FLAG_CASE);

    return $normalized;
}

/**
 * @param array<string, mixed> $inventory
 * @param array<string, mixed> $meta
 * @return array<int, string>
 */
function normalizeRelatedDocPaths(array $inventory, array $meta): array
{
    $docs = [];
    foreach ($meta['related_docs'] ?? [] as $doc) {
        if (is_string($doc) && $doc !== '') {
            $docs[] = trim($doc);
        }
    }

    if (is_string($inventory['readme'] ?? null) && $inventory['readme'] !== '') {
        $docs[] = $inventory['readme'];
    }

    return normalizeStringList($docs);
}

/**
 * @param array<int, string> $explicitUseWhen
 * @param array<int, string> $routePrefixes
 * @return array<int, string>
 */
function normalizeTriggerList(array $explicitUseWhen, ?string $indexTrigger, string $module, array $routePrefixes): array
{
    $triggers = normalizeStringList($explicitUseWhen);
    if ($triggers !== []) {
        return $triggers;
    }

    if (is_string($indexTrigger) && trim($indexTrigger) !== '') {
        $parts = preg_split('/,\s*/', str_replace('`', '', $indexTrigger)) ?: [];
        $fromIndex = normalizeStringList($parts);
        if ($fromIndex !== []) {
            return $fromIndex;
        }
    }

    $fallback = [$module];
    foreach ($routePrefixes as $prefix) {
        if (is_string($prefix) && $prefix !== '') {
            $fallback[] = $prefix;
        }
    }

    return normalizeStringList($fallback);
}

/**
 * @param array<int, string> $paths
 * @return array<int, string>
 */
function pathIfDirectoryExists(string $rootPath, array $paths): array
{
    if ($rootPath === '') {
        return [];
    }

    return $paths === [] ? [] : [$rootPath];
}

/**
 * @param array<int, string> $files
 * @return array<int, string>
 */
function collectRootPaths(array $files): array
{
    $roots = [];
    foreach ($files as $file) {
        if (!is_string($file) || $file === '') {
            continue;
        }

        $parts = explode('/', str_replace('\\', '/', $file));
        if (($parts[0] ?? null) === 'Modules' && count($parts) >= 3) {
            $roots[] = $parts[0] . '/' . $parts[1] . '/' . $parts[2];
            continue;
        }

        if (($parts[0] ?? null) === 'tests' && count($parts) >= 2) {
            $roots[] = $parts[0] . '/' . $parts[1];
            continue;
        }

        if (count($parts) >= 2) {
            $roots[] = $parts[0] . '/' . $parts[1];
            continue;
        }

        $roots[] = $file;
    }

    return normalizeStringList($roots);
}

function routeContractItem(?string $path, string $summary): array
{
    return [
        'path' => $path,
        'summary' => $summary,
        'exists' => is_string($path) && $path !== '',
    ];
}

/**
 * @param array<int, string> $verifyCommands
 * @return array<int, string>
 */
function buildVerifyCommands(string $module, array $verifyCommands): array
{
    $normalized = normalizeStringList($verifyCommands);
    if ($normalized !== []) {
        return $normalized;
    }

    return [
        'php artisan test --filter=' . $module,
    ];
}

function sanitizeTriggerText(string $trigger): string
{
    return trim(str_replace('`', '', $trigger));
}

/**
 * @param array<string, mixed> $document
 */
function encodeJsonDocument(array $document): string
{
    $encoded = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return ($encoded === false ? '{}' : $encoded) . "\n";
}

function buildReadmeDocument(): string
{
    $lines = [
        generatedBanner(),
        '# Repo Entry Maps',
        '',
        '`ai/entrypoints/` is the checkout-aware starting point for agents when the correct route, controller, module, or view is not obvious from the user request.',
        '',
        'Entrypoints V2 keeps the existing Markdown maps and adds machine-readable sidecars under `ai/entrypoints/generated/` so coding agents can route work with less guessing.',
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
        '- Validate schema and required sections: `composer entrypoints:test`',
        '- The generator rewrites root maps, module maps, INDEX, and generated JSON sidecars from the live checkout structure.',
        '',
        '## Compatibility rules',
        '',
        '- Markdown artifact names remain stable: `INDEX.md`, `core-*.md`, `module-*.md`.',
        '- Existing commands remain stable: `entrypoints:generate`, `entrypoints:check`.',
        '- New sidecars are additive and live under `ai/entrypoints/generated/*.json`.',
        '',
        '## Naming',
        '',
        '- `core-<area>.md` for root application shards such as [core-http-entry.md](./core-http-entry.md).',
        '- `module-<PascalCase>.md` for folders under `Modules/`.',
        '- Sidecar keys mirror Markdown names without extension: `core-http-entry.json`, `module-VasAccounting.json`, etc.',
        '',
        '## V2 JSON contract',
        '',
        'Each sidecar map contains:',
        '',
        '- `kind`, `title`, `purpose`, `triggers`',
        '- `verified_paths` grouped by routes/controllers/views/requests/services/utils/models/jobs/notifications/assets/tests',
        '- `route_prefixes`, `search_keywords`, `related_docs`',
        '- `workflows`, `edit_bundles`, `dependencies`',
        '- `tests`, `verify_commands`, `last_reviewed`',
        '',
        'Use sidecars for strict checks/tooling and Markdown for first-pass human navigation.',
        '',
        '## Coverage policy',
        '',
        '- Every local folder under `Modules/` gets a `module-<Name>.md`.',
        '- Every generated map key gets a matching JSON file under `ai/entrypoints/generated/`.',
        '- [INDEX.md](./INDEX.md) includes one row per enabled module in [modules_statuses.json](../../modules_statuses.json) plus any local module folder that is not present in that JSON file.',
        '- If a module is enabled in JSON but missing on disk, `Map` must be `—` and `Notes` must say `not in checkout`.',
        '- Do not add dead map links, placeholder file links, or stale JSON sidecars.',
        '',
        '## Maintenance rule',
        '',
        '- Re-run generation in the same PR when route wiring, controller ownership, primary views, tests, or module entry structure changes.',
        '- Update `ai/entrypoints/metadata.php` for curated workflows/edit bundles/verify guidance.',
        '- Keep `composer entrypoints:check` and `composer entrypoints:test` in CI/pre-commit validation.',
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
        'Use this structure for new root or module maps. Replace placeholders with verified checkout paths only.',
        '',
        '## Purpose',
        '',
        '- State what area this map narrows and when an agent should open it.',
        '',
        '## Use when',
        '',
        '- List trigger scenarios in plain language.',
        '',
        '## Start here',
        '',
        '- List the first 2-5 paths agents should open for this area.',
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
        '### Requests / Services / Utils / Models',
        '',
        '- Add verified module roots and key files for coding tasks.',
        '',
        '### Jobs / Notifications / Assets / Tests',
        '',
        '- Add only if those folders/files exist and help first-pass discovery.',
        '',
        '### Assets / JS',
        '',
        '- Add only when there is a real entry asset, layout script include, or source file that helps first-pass discovery.',
        '',
        '## Common edit bundles',
        '',
        '- List common multi-file edit bundles with brief notes.',
        '',
        '## Primary workflows',
        '',
        '- List major workflows and companion paths.',
        '',
        '## Shared dependencies',
        '',
        '- List first-order requests/services/utils/models used by controllers.',
        '',
        '## Tests / verify',
        '',
        '- List likely tests and verify commands for this area.',
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
        '## Use when',
        '',
        '- Root-route behavior is changing and the module owner is not involved.',
        '- You need first-pass entry files for root HTTP flows.',
        '',
        '## Start here',
        '',
        '- ' . repoLink('routes/web.php'),
        '- ' . repoLink('routes/api.php'),
        '- ' . repoLink('app/Http/Controllers/'),
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
        '## Common edit bundles',
        '',
        '- **Root route + controller bundle** — ' . repoLink('routes/web.php') . ', ' . repoLink('app/Http/Controllers/'),
        '- **Install bootstrap bundle** — ' . repoLink('routes/install_r.php') . ', ' . repoLink('app/Http/Controllers/Install/'),
        '',
        '## Primary workflows',
        '',
        '- **Root route to controller trace** — start at `routes/web.php`, then open the targeted root controller.',
        '- **Install flow trace** — confirm `routes/install_r.php` and `app/Http/Controllers/Install/*` together.',
        '',
        '## Shared dependencies',
        '',
        '- Root controllers frequently depend on `App\\Utils\\*Util` and root FormRequests under `app/Http/Requests`.',
        '',
        '## Tests / verify',
        '',
        '- ' . repoLink('tests/Feature'),
        '- `php artisan route:list`',
        '- `php artisan test --filter=Feature`',
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
 * @param array<int, string> $rootControllers
 * @param array<string, array<int, string>> $controllerSections
 */
function buildCoreHttpControllersDocument(array $rootControllers, array $controllerSections): string
{
    $lines = [
        generatedBanner(),
        '# Core HTTP Controllers',
        '',
        'Verified controller index for root files in `app/Http/Controllers/*.php` plus the `Auth/`, `Install/`, and `Restaurant/` subfolders.',
        '',
        '## Purpose',
        '',
        '- Give agents a fast, verified map of the root controller surface.',
        '- Use the grep hint column to jump from a controller name to the owning route declarations quickly.',
        '- Open the section that matches the area first instead of scanning the whole controller tree.',
        '',
        '## Use when',
        '',
        '- The task names a root controller but route ownership is still unclear.',
        '- You need the closest controller section (`Auth`, `Install`, `Restaurant`) quickly.',
        '',
        '## Start here',
        '',
        '- ' . repoLink('app/Http/Controllers/'),
        '- ' . repoLink('app/Http/Controllers/Auth/'),
        '- ' . repoLink('app/Http/Controllers/Install/'),
        '- ' . repoLink('app/Http/Controllers/Restaurant/'),
        '',
    ];

    $lines = array_merge(
        $lines,
        buildControllerTableSection(
            'Root-level controllers',
            'app/Http/Controllers',
            $rootControllers,
            'Open ' . repoLink('app/Http/Controllers/', 'app/Http/Controllers/') . ' first for non-module controllers that live directly under the root controller directory.'
        ),
        buildControllerTableSection(
            'Auth controllers',
            'app/Http/Controllers/Auth',
            $controllerSections['Auth'] ?? [],
            'Open ' . repoLink('app/Http/Controllers/Auth/', 'app/Http/Controllers/Auth/') . ' for login, registration, password reset, and verification flows.'
        ),
        buildControllerTableSection(
            'Install controllers',
            'app/Http/Controllers/Install',
            $controllerSections['Install'] ?? [],
            'Open ' . repoLink('app/Http/Controllers/Install/', 'app/Http/Controllers/Install/') . ' for installer and module-bootstrap flows.'
        ),
        buildControllerTableSection(
            'Restaurant controllers',
            'app/Http/Controllers/Restaurant',
            $controllerSections['Restaurant'] ?? [],
            'Open ' . repoLink('app/Http/Controllers/Restaurant/', 'app/Http/Controllers/Restaurant/') . ' for bookings, kitchen screens, orders, tables, and modifier-set flows.'
        )
    );

    $lines = array_merge($lines, [
        '',
        '## Common edit bundles',
        '',
        '- **Controller + route bundle** — ' . repoLink('app/Http/Controllers/') . ', ' . repoLink('routes/web.php') . ', ' . repoLink('routes/api.php'),
        '',
        '## Primary workflows',
        '',
        '- **Controller ownership pass** — identify the owning route and middleware before changing controller logic.',
        '',
        '## Shared dependencies',
        '',
        '- Root controllers frequently call `App\\Utils\\*Util` and root FormRequests under `app/Http/Requests`.',
        '',
        '## Tests / verify',
        '',
        '- ' . repoLink('tests/Feature'),
        '- `php artisan test --filter=Feature`',
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
 * @param array<int, string> $otherUtils
 */
function buildCoreUtilsIndexDocument(array $utils, array $otherUtils): string
{
    $lines = [
        generatedBanner(),
        '# Core Utils Index',
        '',
        'Primary utility index for `app/Utils/*Util.php`.',
        '',
        '## Use when',
        '',
        '- You are changing shared helper behavior used by multiple controllers/modules.',
        '- You need to find util ownership quickly before refactoring.',
        '',
        '## Start here',
        '',
        '- ' . repoLink('app/Utils/'),
        '- ' . repoLink('app/Http/Controllers/'),
        '',
        'This map is intentionally Util-focused. The first table lists the primary `*Util.php` classes, and the companion section below lists the remaining PHP files in `app/Utils/` so agents can see the full directory shape without assuming every file follows the Util naming pattern.',
        '',
        '| Util | Purpose | Grep hint |',
        '|---|---|---|',
    ];

    foreach ($utils as $util) {
        $lines[] = '| ' . repoLink('app/Utils/' . $util, $util)
            . ' | ' . utilPurpose($util)
            . ' | `' . utilHint($util) . '` |';
    }

    if ($otherUtils !== []) {
        $lines = array_merge($lines, [
            '',
            '## Other files in app/Utils/',
            '',
            '| File | Purpose | Grep hint |',
            '|---|---|---|',
        ]);

        foreach ($otherUtils as $file) {
            $lines[] = '| ' . repoLink('app/Utils/' . $file, $file)
                . ' | ' . otherUtilPurpose($file)
                . ' | `' . otherUtilHint($file) . '` |';
        }
    }

    $lines = array_merge($lines, [
        '',
        '## Common edit bundles',
        '',
        '- **Util + caller bundle** — ' . repoLink('app/Utils/') . ', ' . repoLink('app/Http/Controllers/'),
        '',
        '## Primary workflows',
        '',
        '- **Util impact pass** — inspect util changes and at least one likely caller path before patching.',
        '',
        '## Shared dependencies',
        '',
        '- Root and module controllers commonly depend on `App\\Utils\\*Util`; verify constructor imports and call sites.',
        '',
        '## Tests / verify',
        '',
        '- ' . repoLink('tests/Unit'),
        '- ' . repoLink('tests/Feature'),
        '- `php artisan test --filter=Unit`',
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
 * @param array<int, string> $controllers
 * @return array<int, string>
 */
function buildControllerTableSection(
    string $heading,
    string $directory,
    array $controllers,
    string $instruction
): array {
    if ($controllers === []) {
        return [];
    }

    $lines = [
        '## ' . $heading,
        '',
        $instruction,
        '',
        '| Controller | Purpose | Grep / route hint |',
        '|---|---|---|',
    ];

    foreach ($controllers as $controller) {
        $className = basename($controller, '.php');
        $lines[] = '| ' . repoLink($directory . '/' . $controller, $controller)
            . ' | ' . controllerPurpose($controller)
            . ' | `' . controllerHint($className) . '` |';
    }

    $lines[] = '';

    return $lines;
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
    $map = buildModuleMapContract($module, $inventory, $meta);
    $startHere = buildStartHerePaths($map, $meta, $inventory);

    $lines = [
        generatedBanner(),
        '# ' . $map['title'],
        '',
        $map['purpose'],
        '',
        '## Use when',
        '',
    ];

    foreach ($map['triggers'] as $trigger) {
        $lines[] = '- ' . $trigger;
    }

    $lines[] = '';
    $lines[] = '## Start here';
    $lines[] = '';
    foreach ($startHere as $path) {
        $lines[] = '- ' . repoLink($path);
    }

    $lines = array_merge($lines, [
        '',
        '## Verified paths',
        '',
        '### Routes',
        '',
    ]);

    foreach ($map['verified_paths']['routes'] as $routeItem) {
        if ((bool) ($routeItem['exists'] ?? false) && is_string($routeItem['path'] ?? null)) {
            $lines[] = '- ' . repoLink((string) $routeItem['path']) . ' — ' . (string) ($routeItem['summary'] ?? '');
            continue;
        }

        $missingPath = is_string($routeItem['path'] ?? null) ? (string) $routeItem['path'] : 'missing route file';
        $lines[] = '- `' . $missingPath . '` is not present in this checkout';
    }

    $lines[] = '';
    $lines[] = '### Controllers';
    $lines[] = '';
    $lines = array_merge($lines, renderPathList($map['verified_paths']['controllers']));

    $lines[] = '';
    $lines[] = '### Views';
    $lines[] = '';
    $lines = array_merge($lines, renderPathList($map['verified_paths']['views']));

    $lines[] = '';
    $lines[] = '### Requests';
    $lines[] = '';
    $lines = array_merge($lines, renderPathList($map['verified_paths']['requests']));

    $lines[] = '';
    $lines[] = '### Services';
    $lines[] = '';
    $lines = array_merge($lines, renderPathList($map['verified_paths']['services']));

    $lines[] = '';
    $lines[] = '### Utils';
    $lines[] = '';
    $lines = array_merge($lines, renderPathList($map['verified_paths']['utils']));

    $lines[] = '';
    $lines[] = '### Models / Entities';
    $lines[] = '';
    $lines = array_merge($lines, renderPathList($map['verified_paths']['models']));

    $lines[] = '';
    $lines[] = '### Jobs / Notifications';
    $lines[] = '';
    $lines = array_merge($lines, renderPathList(array_merge(
        $map['verified_paths']['jobs'],
        $map['verified_paths']['notifications']
    )));

    $lines[] = '';
    $lines[] = '### Assets / JS';
    $lines[] = '';
    $lines = array_merge($lines, renderPathList($map['verified_paths']['assets']));

    $lines[] = '';
    $lines[] = '### Tests';
    $lines[] = '';
    $lines = array_merge($lines, renderPathList($map['verified_paths']['tests']));

    $lines[] = '';
    $lines[] = '## Common edit bundles';
    $lines[] = '';
    $lines = array_merge($lines, renderNamedPathBlocks($map['edit_bundles']));

    $lines[] = '';
    $lines[] = '## Primary workflows';
    $lines[] = '';
    $lines = array_merge($lines, renderNamedPathBlocks($map['workflows']));

    $lines[] = '';
    $lines[] = '## Shared dependencies';
    $lines[] = '';
    $lines[] = '### Requests';
    $lines[] = '';
    $lines = array_merge($lines, renderCodeList($map['dependencies']['requests']));
    $lines[] = '';
    $lines[] = '### Services';
    $lines[] = '';
    $lines = array_merge($lines, renderCodeList($map['dependencies']['services']));
    $lines[] = '';
    $lines[] = '### Utils';
    $lines[] = '';
    $lines = array_merge($lines, renderCodeList($map['dependencies']['utils']));
    $lines[] = '';
    $lines[] = '### Models / Entities';
    $lines[] = '';
    $lines = array_merge($lines, renderCodeList($map['dependencies']['models']));

    $lines[] = '';
    $lines[] = '## Tests / verify';
    $lines[] = '';
    foreach ($map['tests'] as $testPath) {
        $lines[] = '- ' . repoLink($testPath);
    }
    foreach ($map['verify_commands'] as $command) {
        $lines[] = '- `' . $command . '`';
    }

    $lines[] = '';
    $lines[] = '## Search keywords';
    $lines[] = '';
    foreach ($map['search_keywords'] as $keyword) {
        $lines[] = '- `' . $keyword . '`';
    }

    $lines[] = '';
    $lines[] = '## Related docs';
    $lines[] = '';
    foreach (buildRelatedDocs($inventory, $meta) as $doc) {
        $lines[] = '- ' . $doc;
    }

    $lines[] = '';
    $lines[] = '## Last reviewed';
    $lines[] = '';
    $lines[] = '- ' . LAST_REVIEWED_TOKEN;

    return implode("\n", $lines) . "\n";
}

/**
 * @param array<string, mixed> $map
 * @param array<string, mixed> $meta
 * @param array<string, mixed> $inventory
 * @return array<int, string>
 */
function buildStartHerePaths(array $map, array $meta, array $inventory): array
{
    $startHere = normalizeStringList(is_array($meta['start_here'] ?? null) ? $meta['start_here'] : []);
    if ($startHere !== []) {
        return $startHere;
    }

    $fallback = [];
    foreach ($map['verified_paths']['routes'] as $routeItem) {
        if ((bool) ($routeItem['exists'] ?? false) && is_string($routeItem['path'] ?? null)) {
            $fallback[] = (string) $routeItem['path'];
        }
    }

    if (is_string($inventory['controllers_root'] ?? null) && $inventory['controllers_root'] !== '') {
        $fallback[] = $inventory['controllers_root'];
    }
    if (is_string($inventory['views_root'] ?? null) && $inventory['views_root'] !== '') {
        $fallback[] = $inventory['views_root'];
    }

    return normalizeStringList(array_slice($fallback, 0, 5));
}

/**
 * @param array<int, string> $paths
 * @return array<int, string>
 */
function renderPathList(array $paths): array
{
    if ($paths === []) {
        return ['- _None discovered in this checkout_'];
    }

    $lines = [];
    foreach ($paths as $path) {
        $lines[] = '- ' . repoLink($path);
    }

    return $lines;
}

/**
 * @param array<int, array{name: string, paths: array<int, string>, notes: string}> $blocks
 * @return array<int, string>
 */
function renderNamedPathBlocks(array $blocks): array
{
    if ($blocks === []) {
        return ['- _None curated yet_'];
    }

    $lines = [];
    foreach ($blocks as $block) {
        $text = '- **' . $block['name'] . '**';
        if ($block['notes'] !== '') {
            $text .= ' — ' . $block['notes'];
        }
        if ($block['paths'] !== []) {
            $text .= ' | ' . implode(', ', array_map('repoLink', $block['paths']));
        }
        $lines[] = $text;
    }

    return $lines;
}

/**
 * @param array<int, string> $values
 * @return array<int, string>
 */
function renderCodeList(array $values): array
{
    if ($values === []) {
        return ['- _None discovered from first-order controller references_'];
    }

    $lines = [];
    foreach ($values as $value) {
        $lines[] = '- `' . $value . '`';
    }

    return $lines;
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
    $docPaths = normalizeRelatedDocPaths($inventory, $meta);
    if ($docPaths === []) {
        $docPaths = [
            'ai/laravel-conventions.md',
            'ai/security-and-auth.md',
        ];
    }

    $links = [];
    foreach ($docPaths as $path) {
        if (str_starts_with($path, 'ai/')) {
            $links[] = aiLink(substr($path, 3));
            continue;
        }

        $links[] = repoLink($path);
    }

    return array_values(array_unique($links));
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
        'ConfirmPasswordController.php' => 'Password confirmation gate before sensitive actions.',
        'ContactController.php' => 'Customer and supplier CRUD, ledgers, and contact feeds.',
        'Controller.php' => 'Base app controller class used by other controllers.',
        'CustomerGroupController.php' => 'Customer group management.',
        'DashboardConfiguratorController.php' => 'Dashboard configuration and widget settings.',
        'DataController.php' => 'Restaurant AJAX and data-feed endpoints.',
        'DiscountController.php' => 'Discount management.',
        'DocumentAndNoteController.php' => 'Document and note attachment flows.',
        'ExpenseCategoryController.php' => 'Expense category management.',
        'ExpenseController.php' => 'Expense CRUD and expense reports.',
        'ForgotPasswordController.php' => 'Password reset request and email dispatch flows.',
        'GlobalSearchController.php' => 'Global search endpoints across entities.',
        'GroupTaxController.php' => 'Group tax configuration.',
        'HomeController.php' => 'Dashboard and authenticated home screens.',
        'ImportOpeningStockController.php' => 'Opening stock import workflows.',
        'ImportProductsController.php' => 'Product import workflows.',
        'ImportSalesController.php' => 'Sales import workflows.',
        'InstallController.php' => 'Installer setup steps and bootstrap flow.',
        'InvoiceLayoutController.php' => 'Invoice layout management.',
        'InvoiceSchemeController.php' => 'Invoice numbering scheme management.',
        'KitchenController.php' => 'Kitchen display and food-order preparation flows.',
        'LabelsController.php' => 'Label generation and printing.',
        'LedgerDiscountController.php' => 'Ledger discount maintenance.',
        'LocationSettingsController.php' => 'Location-specific settings.',
        'LoginController.php' => 'Login and logout flows.',
        'ManageUserController.php' => 'Admin user management and sign-in-as-user flows.',
        'ModifierSetsController.php' => 'Restaurant modifier-set management.',
        'ModulesController.php' => 'Module activation checks during install and update.',
        'MyFatoorahController.php' => 'MyFatoorah payment integration callbacks and redirects.',
        'NotificationController.php' => 'Notification listing and read/update flows.',
        'NotificationTemplateController.php' => 'Notification template management.',
        'OpeningStockController.php' => 'Opening stock CRUD flows.',
        'OrderController.php' => 'Restaurant order and table-service flows.',
        'PesaPalController.php' => 'PesaPal payment integration callbacks and redirects.',
        'PrinterController.php' => 'Printer management.',
        'ProductController.php' => 'Product catalog CRUD, bulk actions, and detail flows.',
        'ProductModifierSetController.php' => 'Product-to-modifier-set assignment flows.',
        'ProductQuoteController.php' => 'Quote creation entry points tied to products.',
        'ProductSalesOrderController.php' => 'Product-specific sales order entry points.',
        'PublicQuoteController.php' => 'Public quote viewing flows.',
        'PurchaseController.php' => 'Purchase transaction workflows.',
        'PurchaseOrderController.php' => 'Purchase order workflows.',
        'PurchaseRequisitionController.php' => 'Purchase requisition workflows.',
        'PurchaseReturnController.php' => 'Purchase return workflows.',
        'RegisterController.php' => 'Registration and first-account setup flows.',
        'ReportController.php' => 'Business reporting endpoints.',
        'ResetPasswordController.php' => 'Reset-token password update flows.',
        'RoleController.php' => 'Role and permission management.',
        'BookingController.php' => 'Restaurant booking and reservation flows.',
        'RestaurantController.php' => 'Restaurant settings, service areas, and dining flows.',
        'SalesCommissionAgentController.php' => 'Sales commission agent management.',
        'SalesOrderController.php' => 'Sales order workflows.',
        'SellController.php' => 'Sales transaction workflows and sell screens.',
        'SellingPriceGroupController.php' => 'Selling price group management.',
        'SellPosController.php' => 'POS selling, invoices, and payment confirmation flows.',
        'SellReturnController.php' => 'Sell return workflows.',
        'StockAdjustmentController.php' => 'Stock adjustment workflows.',
        'StockTransferController.php' => 'Stock transfer workflows.',
        'TableController.php' => 'Restaurant table management and seating flows.',
        'TaxonomyController.php' => 'Taxonomy maintenance.',
        'TaxRateController.php' => 'Tax rate management.',
        'TransactionPaymentController.php' => 'Transaction payment actions.',
        'TypesOfServiceController.php' => 'Types of service management.',
        'UnifiedQuoteController.php' => 'Unified quote hub and quote search entry points.',
        'UnitController.php' => 'Unit management.',
        'UserController.php' => 'User profile and account updates.',
        'VariationTemplateController.php' => 'Variation template management.',
        'WarrantyController.php' => 'Warranty management.',
        'VerificationController.php' => 'Email verification notice, resend, and confirmation flows.',
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

function utilHint(string $utilFile): string
{
    $stem = basename($utilFile, '.php');
    $short = preg_replace('/Util$/', '', $stem) ?? $stem;

    return grepHintForTerms([$stem, $short]);
}

function otherUtilPurpose(string $file): string
{
    $purposeMap = [
        'ContactFeedProviderInterface.php' => 'Contract for contact-feed provider adapters.',
        'FacebookContactFeedProvider.php' => 'Facebook contact-feed provider adapter.',
        'GoogleContactFeedProvider.php' => 'Google contact-feed provider adapter.',
        'LinkedInContactFeedProvider.php' => 'LinkedIn contact-feed provider adapter.',
        'QuoteDisplayPresenter.php' => 'Quote display and presentation formatting helpers.',
        'QuoteInvoiceReleaseService.php' => 'Service that releases quotes into invoice-ready state.',
        'SerpApiGoogleContactFeedProvider.php' => 'SerpApi-backed Google contact-feed provider adapter.',
    ];

    if (isset($purposeMap[$file])) {
        return $purposeMap[$file];
    }

    $base = basename($file, '.php');
    $humanized = preg_replace('/([a-z])([A-Z])/', '$1 $2', $base) ?? $base;

    return 'Support class for ' . strtolower((string) $humanized) . '.';
}

function otherUtilHint(string $file): string
{
    $stem = basename($file, '.php');
    $short = preg_replace('/(Interface|Service|Presenter)$/', '', $stem) ?? $stem;

    return grepHintForTerms([$stem, $short, str_contains($stem, 'ContactFeedProvider') ? 'ContactFeedProvider' : '']);
}

/**
 * @param array<int, string> $terms
 */
function grepHintForTerms(array $terms): string
{
    $filtered = [];
    foreach ($terms as $term) {
        if ($term === '') {
            continue;
        }

        if (!in_array($term, $filtered, true)) {
            $filtered[] = $term;
        }
    }

    return "rg -n '" . implode('|', $filtered) . "' app routes Modules";
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

/**
 * @param array<string, string> $documents
 * @param array<int, string> $expectedDocs
 * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, string>, 3: array<int, string>}
 */
function syncJsonDocuments(
    string $generatedDir,
    array $documents,
    array $expectedDocs,
    string $today,
    bool $checkOnly
): array {
    $written = [];
    $unchanged = [];
    $removed = [];
    $issues = [];

    if (!is_dir($generatedDir)) {
        if ($checkOnly) {
            $issues[] = 'Missing ai/entrypoints/generated directory.';
        } else {
            mkdir($generatedDir, 0777, true);
        }
    }

    foreach ($documents as $name => $tokenizedContent) {
        $path = $generatedDir . '/' . $name;
        $existing = is_file($path) ? (string) file_get_contents($path) : null;
        $existingNormalized = $existing !== null ? normalizeJsonLastReviewed($existing) : null;

        if ($existingNormalized === $tokenizedContent) {
            $unchanged[] = 'generated/' . $name;
            continue;
        }

        if ($checkOnly) {
            $issues[] = 'Would update generated/' . $name;
            continue;
        }

        $finalContent = str_replace(LAST_REVIEWED_TOKEN, $today, $tokenizedContent);
        file_put_contents($path, $finalContent);
        $written[] = 'generated/' . $name;
    }

    $existingJsonDocs = [];
    foreach (glob($generatedDir . '/*.json') ?: [] as $absolute) {
        if (is_file($absolute)) {
            $existingJsonDocs[] = basename($absolute);
        }
    }

    foreach ($existingJsonDocs as $staleName) {
        if (in_array($staleName, $expectedDocs, true)) {
            continue;
        }

        if ($checkOnly) {
            $issues[] = 'Would remove stale generated/' . $staleName;
            continue;
        }

        @unlink($generatedDir . '/' . $staleName);
        $removed[] = 'generated/' . $staleName;
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

function normalizeJsonLastReviewed(string $content): string
{
    return (string) preg_replace(
        '/"last_reviewed"\s*:\s*"[0-9]{4}-[0-9]{2}-[0-9]{2}"/m',
        '"last_reviewed": "' . LAST_REVIEWED_TOKEN . '"',
        str_replace("\r\n", "\n", $content)
    );
}
