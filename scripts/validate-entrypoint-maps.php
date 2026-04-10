#!/usr/bin/env php
<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$repoRoot = realpath(__DIR__ . '/..');
if ($repoRoot === false) {
    fwrite(STDERR, "Unable to resolve repo root.\n");
    exit(1);
}

$entrypointsDir = $repoRoot . '/ai/entrypoints';
$generatedDir = $entrypointsDir . '/generated';
$issues = [];
$warnings = [];

// ---------------------------------------------------------------------------
// 1. Required markdown maps
// ---------------------------------------------------------------------------

$requiredMarkdown = [
    'INDEX.md',
    'README.md',
    '_TEMPLATE.md',
    'core-http-entry.md',
    'core-http-controllers.md',
    'core-utils-index.md',
];

$localModules = [];
$modulesDir = $repoRoot . '/Modules';
if (is_dir($modulesDir)) {
    foreach (scandir($modulesDir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.') || !is_dir($modulesDir . '/' . $entry)) {
            continue;
        }
        $localModules[] = $entry;
        $requiredMarkdown[] = 'module-' . $entry . '.md';
    }
    sort($localModules, SORT_NATURAL | SORT_FLAG_CASE);
}

foreach ($requiredMarkdown as $name) {
    $path = $entrypointsDir . '/' . $name;
    if (!is_file($path)) {
        $issues[] = 'Missing markdown map: ai/entrypoints/' . $name;
    }
}

// ---------------------------------------------------------------------------
// 2. JSON sidecar validation
// ---------------------------------------------------------------------------

$requiredJsonKeys = [
    'kind',
    'title',
    'purpose',
    'triggers',
    'verified_paths',
    'route_prefixes',
    'search_keywords',
    'related_docs',
    'workflows',
    'edit_bundles',
    'dependencies',
    'tests',
    'verify_commands',
    'last_reviewed',
];

$requiredVerifiedPathSections = [
    'routes',
    'controllers',
    'views',
    'requests',
    'services',
    'utils',
    'models',
    'jobs',
    'notifications',
    'assets',
    'tests',
];

$requiredDependencyKeys = [
    'requests',
    'services',
    'utils',
    'models',
];

$requiredJsonFiles = [
    'index.json',
    'core-http-entry.json',
    'core-http-controllers.json',
    'core-utils-index.json',
];
foreach ($localModules as $module) {
    $requiredJsonFiles[] = 'module-' . $module . '.json';
}

if (!is_dir($generatedDir)) {
    $issues[] = 'Missing generated sidecar directory: ai/entrypoints/generated';
} else {
    $jsonFiles = glob($generatedDir . '/*.json') ?: [];
    if ($jsonFiles === []) {
        $issues[] = 'No JSON sidecars found under ai/entrypoints/generated.';
    }

    foreach ($jsonFiles as $jsonPath) {
        $jsonName = basename($jsonPath);
        $content = (string) file_get_contents($jsonPath);
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            $issues[] = 'Invalid JSON: ai/entrypoints/generated/' . $jsonName;
            continue;
        }

        foreach ($requiredJsonKeys as $key) {
            if (!array_key_exists($key, $decoded)) {
                $issues[] = 'Missing key `' . $key . '` in ' . $jsonName;
            }
        }

        $kind = is_string($decoded['kind'] ?? null) ? $decoded['kind'] : '';
        if ($kind !== '' && !in_array($kind, ['index', 'core', 'module'], true)) {
            $issues[] = 'Invalid `kind` value "' . $kind . '" in ' . $jsonName . ' (expected: index, core, or module)';
        }

        $title = is_string($decoded['title'] ?? null) ? trim($decoded['title']) : '';
        if ($title === '' && $kind !== 'index') {
            $warnings[] = 'Empty `title` in ' . $jsonName;
        }

        $purpose = is_string($decoded['purpose'] ?? null) ? trim($decoded['purpose']) : '';
        if ($purpose === '' && $kind !== 'index') {
            $warnings[] = 'Empty `purpose` in ' . $jsonName;
        }

        $triggers = is_array($decoded['triggers'] ?? null) ? $decoded['triggers'] : [];
        if ($triggers === [] && $kind !== 'index') {
            $warnings[] = 'Empty `triggers` in ' . $jsonName;
        }

        if (is_array($decoded['verified_paths'] ?? null)) {
            foreach ($requiredVerifiedPathSections as $section) {
                if (!array_key_exists($section, $decoded['verified_paths'])) {
                    $issues[] = 'Missing `verified_paths.' . $section . '` in ' . $jsonName;
                }
            }

            if ($kind === 'module') {
                $controllers = $decoded['verified_paths']['controllers'] ?? [];
                if (is_array($controllers) && $controllers === []) {
                    $warnings[] = 'Empty `verified_paths.controllers` in module sidecar ' . $jsonName;
                }

                $routes = $decoded['verified_paths']['routes'] ?? [];
                if (is_array($routes)) {
                    $hasExistingRoute = false;
                    foreach ($routes as $routeItem) {
                        if (is_array($routeItem) && ($routeItem['exists'] ?? false) === true) {
                            $hasExistingRoute = true;
                            break;
                        }
                    }
                    if (!$hasExistingRoute) {
                        $warnings[] = 'No existing route file in `verified_paths.routes` for module sidecar ' . $jsonName;
                    }
                }
            }
        }

        if (is_array($decoded['dependencies'] ?? null)) {
            foreach ($requiredDependencyKeys as $depKey) {
                if (!array_key_exists($depKey, $decoded['dependencies'])) {
                    $issues[] = 'Missing `dependencies.' . $depKey . '` in ' . $jsonName;
                }
            }
        }

        $lastReviewed = is_string($decoded['last_reviewed'] ?? null) ? $decoded['last_reviewed'] : '';
        if ($lastReviewed !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastReviewed) !== 1) {
            $issues[] = 'Invalid `last_reviewed` date format in ' . $jsonName . ' (expected YYYY-MM-DD)';
        }

        if (is_array($decoded['workflows'] ?? null)) {
            foreach ($decoded['workflows'] as $i => $workflow) {
                if (!is_array($workflow)) {
                    continue;
                }
                $wName = is_string($workflow['name'] ?? null) ? trim($workflow['name']) : '';
                if ($wName === '') {
                    $issues[] = 'Workflow at index ' . $i . ' has empty `name` in ' . $jsonName;
                }
                $wPaths = is_array($workflow['paths'] ?? null) ? $workflow['paths'] : [];
                if ($wPaths === []) {
                    $warnings[] = 'Workflow "' . $wName . '" has empty `paths` in ' . $jsonName;
                }
            }
        }

        if (is_array($decoded['edit_bundles'] ?? null)) {
            foreach ($decoded['edit_bundles'] as $i => $bundle) {
                if (!is_array($bundle)) {
                    continue;
                }
                $bName = is_string($bundle['name'] ?? null) ? trim($bundle['name']) : '';
                if ($bName === '') {
                    $issues[] = 'Edit bundle at index ' . $i . ' has empty `name` in ' . $jsonName;
                }
                $bPaths = is_array($bundle['paths'] ?? null) ? $bundle['paths'] : [];
                if ($bPaths === []) {
                    $warnings[] = 'Edit bundle "' . $bName . '" has empty `paths` in ' . $jsonName;
                }
            }
        }

        $verifyCommands = is_array($decoded['verify_commands'] ?? null) ? $decoded['verify_commands'] : [];
        if ($verifyCommands === [] && $kind !== 'index') {
            $warnings[] = 'Empty `verify_commands` in ' . $jsonName;
        }
    }

    foreach ($requiredJsonFiles as $name) {
        if (!is_file($generatedDir . '/' . $name)) {
            $issues[] = 'Missing JSON sidecar: ai/entrypoints/generated/' . $name;
        }
    }

    $existingJsonFiles = array_map('basename', $jsonFiles);
    $expectedJsonFiles = array_merge(
        $requiredJsonFiles,
        ['index.json']
    );
    foreach ($existingJsonFiles as $existingJson) {
        $expectedPrefix = false;
        foreach (['index.json', 'core-', 'module-'] as $prefix) {
            if ($existingJson === $prefix || str_starts_with($existingJson, $prefix)) {
                $expectedPrefix = true;
                break;
            }
        }
        if (!$expectedPrefix) {
            $warnings[] = 'Unexpected JSON sidecar naming: ai/entrypoints/generated/' . $existingJson;
        }
    }
}

// ---------------------------------------------------------------------------
// 3. Markdown section validation (all module + core maps, not just pilots)
// ---------------------------------------------------------------------------

$sharedRequiredSections = [
    '## Use when',
    '## Start here',
    '## Common edit bundles',
    '## Primary workflows',
    '## Shared dependencies',
    '## Tests / verify',
    '## Related docs',
    '## Last reviewed',
];

$moduleOnlySections = [
    '## Verified paths',
    '## Search keywords',
];

$coreMapFiles = ['core-http-entry.md', 'core-http-controllers.md', 'core-utils-index.md'];
$moduleMapFiles = array_map(static fn (string $m): string => 'module-' . $m . '.md', $localModules);

foreach ($coreMapFiles as $name) {
    $path = $entrypointsDir . '/' . $name;
    if (!is_file($path)) {
        continue;
    }

    $content = (string) file_get_contents($path);
    foreach ($sharedRequiredSections as $section) {
        if (!str_contains($content, $section)) {
            $issues[] = 'Missing section `' . $section . '` in ai/entrypoints/' . $name;
        }
    }
}

foreach ($moduleMapFiles as $name) {
    $path = $entrypointsDir . '/' . $name;
    if (!is_file($path)) {
        continue;
    }

    $content = (string) file_get_contents($path);
    $allModuleSections = array_merge($sharedRequiredSections, $moduleOnlySections);
    foreach ($allModuleSections as $section) {
        if (!str_contains($content, $section)) {
            $issues[] = 'Missing section `' . $section . '` in ai/entrypoints/' . $name;
        }
    }
}

// ---------------------------------------------------------------------------
// 4. INDEX.md coverage check
// ---------------------------------------------------------------------------

$indexPath = $entrypointsDir . '/INDEX.md';
if (is_file($indexPath)) {
    $indexContent = (string) file_get_contents($indexPath);
    foreach ($localModules as $module) {
        $expectedLink = 'module-' . $module . '.md';
        if (!str_contains($indexContent, $expectedLink)) {
            $issues[] = 'INDEX.md is missing entry for local module ' . $module . ' (expected link to ' . $expectedLink . ')';
        }
    }

    if (!str_contains($indexContent, 'core-http-entry.md')) {
        $issues[] = 'INDEX.md is missing core-http-entry.md reference';
    }
}

// ---------------------------------------------------------------------------
// 5. Stale file detection
// ---------------------------------------------------------------------------

$existingModuleMd = [];
foreach (glob($entrypointsDir . '/module-*.md') ?: [] as $mdPath) {
    if (is_file($mdPath)) {
        $baseName = basename($mdPath, '.md');
        $moduleName = substr($baseName, strlen('module-'));
        if (!in_array($moduleName, $localModules, true)) {
            $warnings[] = 'Stale markdown map: ai/entrypoints/' . basename($mdPath) . ' (module folder not in Modules/)';
        }
    }
}

if (is_dir($generatedDir)) {
    foreach (glob($generatedDir . '/module-*.json') ?: [] as $jsonPath) {
        if (is_file($jsonPath)) {
            $baseName = basename($jsonPath, '.json');
            $moduleName = substr($baseName, strlen('module-'));
            if (!in_array($moduleName, $localModules, true)) {
                $warnings[] = 'Stale JSON sidecar: ai/entrypoints/generated/' . basename($jsonPath) . ' (module folder not in Modules/)';
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Output
// ---------------------------------------------------------------------------

if ($warnings !== []) {
    fwrite(STDOUT, "Warnings (" . count($warnings) . "):\n");
    foreach ($warnings as $warning) {
        fwrite(STDOUT, '  [WARN] ' . $warning . "\n");
    }
    fwrite(STDOUT, "\n");
}

if ($issues !== []) {
    fwrite(STDOUT, "Entrypoint validation failed (" . count($issues) . " issues):\n");
    foreach ($issues as $issue) {
        fwrite(STDOUT, '- ' . $issue . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "Entrypoint validation passed.\n");
exit(0);
