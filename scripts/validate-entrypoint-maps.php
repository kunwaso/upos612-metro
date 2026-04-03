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

$requiredMarkdown = [
    'INDEX.md',
    'core-http-entry.md',
    'core-http-controllers.md',
    'core-utils-index.md',
    'module-Aichat.md',
    'module-Projectauto.md',
    'module-VasAccounting.md',
];

foreach ($requiredMarkdown as $name) {
    $path = $entrypointsDir . '/' . $name;
    if (!is_file($path)) {
        $issues[] = 'Missing markdown map: ai/entrypoints/' . $name;
    }
}

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

if (!is_dir($generatedDir)) {
    $issues[] = 'Missing generated sidecar directory: ai/entrypoints/generated';
} else {
    $jsonFiles = glob($generatedDir . '/*.json') ?: [];
    if ($jsonFiles === []) {
        $issues[] = 'No JSON sidecars found under ai/entrypoints/generated.';
    }

    foreach ($jsonFiles as $jsonPath) {
        $content = (string) file_get_contents($jsonPath);
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            $issues[] = 'Invalid JSON: ' . str_replace('\\', '/', substr($jsonPath, strlen($repoRoot) + 1));
            continue;
        }

        foreach ($requiredJsonKeys as $key) {
            if (!array_key_exists($key, $decoded)) {
                $issues[] = 'Missing key `' . $key . '` in ' . basename($jsonPath);
            }
        }
    }

    $requiredJsonFiles = [
        'index.json',
        'core-http-entry.json',
        'core-http-controllers.json',
        'core-utils-index.json',
        'module-Aichat.json',
        'module-Projectauto.json',
        'module-VasAccounting.json',
    ];
    foreach ($requiredJsonFiles as $name) {
        if (!is_file($generatedDir . '/' . $name)) {
            $issues[] = 'Missing JSON sidecar: ai/entrypoints/generated/' . $name;
        }
    }
}

$requiredSections = [
    '## Use when',
    '## Start here',
    '## Common edit bundles',
    '## Primary workflows',
    '## Shared dependencies',
    '## Tests / verify',
];

$sectionPilotMaps = [
    'core-http-entry.md',
    'module-Aichat.md',
    'module-Projectauto.md',
    'module-VasAccounting.md',
];

foreach ($sectionPilotMaps as $name) {
    $path = $entrypointsDir . '/' . $name;
    if (!is_file($path)) {
        continue;
    }

    $content = (string) file_get_contents($path);
    foreach ($requiredSections as $section) {
        if (!str_contains($content, $section)) {
            $issues[] = 'Missing section `' . $section . '` in ai/entrypoints/' . $name;
        }
    }
}

if ($issues !== []) {
    fwrite(STDOUT, "Entrypoint validation failed:\n");
    foreach ($issues as $issue) {
        fwrite(STDOUT, '- ' . $issue . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "Entrypoint validation passed.\n");
exit(0);

