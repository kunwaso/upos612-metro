#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Recursively compare English and Vietnamese locale trees.
 *
 * The script is intentionally standalone so it can be used both from the CLI
 * and from PHPUnit tests.
 */

/**
 * @return array<int, string>
 */
function translation_audit_normalize_allowlist(array $allowValues): array
{
    $defaultAllowValues = [
        'Email',
        'tls / ssl',
        'CR',
        'DR',
        'MM/YY',
        'CVV',
        'gg',
        'Pantone',
        'Pantone TXC',
        'Incoterm',
        'BR RD #',
        'Webhook URL',
        'CMS',
        'SEO',
        'Blog',
        'FAQ',
        'ProjectX',
        'Projectauto',
        'Aichat',
        'SKU',
        'X-Projects',
        'POS',
        'Telegram',
    ];

    $allowValues = array_merge($defaultAllowValues, $allowValues);
    $allowValues = array_map(static function ($value) {
        return trim((string) $value);
    }, $allowValues);

    $allowValues = array_filter($allowValues, static function ($value) {
        return $value !== '';
    });

    return array_values(array_unique($allowValues));
}

/**
 * @return array<string, mixed>
 */
function translation_audit_run(string $repoRoot, array $allowValues = []): array
{
    $repoRoot = rtrim(realpath($repoRoot) ?: $repoRoot, DIRECTORY_SEPARATOR);
    $allowValues = translation_audit_normalize_allowlist($allowValues);
    $allowLookup = array_fill_keys($allowValues, true);

    $report = [
        'repo_root' => $repoRoot,
        'allow_values' => $allowValues,
        'roots' => [],
        'issues' => [],
        'summary' => [
            'roots_scanned' => 0,
            'files_checked' => 0,
            'missing_files' => 0,
            'missing_keys' => 0,
            'unchanged_values' => 0,
            'issue_count' => 0,
        ],
    ];

    foreach (translation_audit_discover_roots($repoRoot) as $root) {
        $rootReport = translation_audit_compare_root(
            $repoRoot,
            $root['label'],
            $root['en_root'],
            $root['vi_root'],
            $allowLookup
        );

        $report['roots'][] = $rootReport;
        $report['summary']['roots_scanned']++;
        $report['summary']['files_checked'] += $rootReport['files_checked'];
        $report['summary']['missing_files'] += $rootReport['missing_files'];
        $report['summary']['missing_keys'] += $rootReport['missing_keys'];
        $report['summary']['unchanged_values'] += $rootReport['unchanged_values'];
        $report['issues'] = array_merge($report['issues'], $rootReport['issues']);
    }

    $report['summary']['issue_count'] = count($report['issues']);

    return $report;
}

/**
 * @return array<int, array{label: string, en_root: string, vi_root: string}>
 */
function translation_audit_discover_roots(string $repoRoot): array
{
    $roots = [];

    $coreEnRoot = $repoRoot . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'en';
    $coreViRoot = $repoRoot . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'vi';
    if (is_dir($coreEnRoot)) {
        $roots[] = [
            'label' => 'core',
            'en_root' => $coreEnRoot,
            'vi_root' => $coreViRoot,
        ];
    }

    foreach (glob($repoRoot . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'en', GLOB_ONLYDIR) ?: [] as $enRoot) {
        $moduleName = basename(dirname(dirname(dirname($enRoot))));
        $roots[] = [
            'label' => 'module:' . $moduleName,
            'en_root' => $enRoot,
            'vi_root' => dirname($enRoot) . DIRECTORY_SEPARATOR . 'vi',
        ];
    }

    foreach (glob($repoRoot . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'en', GLOB_ONLYDIR) ?: [] as $enRoot) {
        $vendorName = basename(dirname($enRoot));
        $roots[] = [
            'label' => 'vendor:' . $vendorName,
            'en_root' => $enRoot,
            'vi_root' => dirname($enRoot) . DIRECTORY_SEPARATOR . 'vi',
        ];
    }

    usort($roots, static function (array $a, array $b): int {
        return strcmp($a['label'], $b['label']);
    });

    return $roots;
}

/**
 * @param array<string, bool> $allowLookup
 * @return array<string, mixed>
 */
function translation_audit_compare_root(string $repoRoot, string $label, string $enRoot, string $viRoot, array $allowLookup): array
{
    $files = translation_audit_collect_php_files($enRoot);
    $rootReport = [
        'label' => $label,
        'en_root' => translation_audit_relative_path($repoRoot, $enRoot),
        'vi_root' => translation_audit_relative_path($repoRoot, $viRoot),
        'vi_exists' => is_dir($viRoot),
        'files_checked' => 0,
        'missing_files' => 0,
        'missing_keys' => 0,
        'unchanged_values' => 0,
        'issues' => [],
        'files' => [],
    ];

    foreach ($files as $relativePath => $enFile) {
        $rootReport['files_checked']++;
        $viFile = $viRoot . DIRECTORY_SEPARATOR . $relativePath;

        if (! is_file($viFile)) {
            $rootReport['missing_files']++;
            $issue = [
                'type' => 'missing_file',
                'scope' => $label,
                'file' => translation_audit_relative_path($repoRoot, $viFile),
                'relative_path' => $relativePath,
            ];
            $rootReport['issues'][] = $issue;
            $rootReport['files'][] = [
                'relative_path' => $relativePath,
                'status' => 'missing_file',
                'missing_keys' => 0,
                'unchanged_values' => 0,
            ];
            continue;
        }

        $enData = translation_audit_load_locale_array($enFile);
        $viData = translation_audit_load_locale_array($viFile);
        $comparison = translation_audit_compare_arrays($label, $relativePath, $enData, $viData, $allowLookup, $repoRoot);

        $rootReport['missing_keys'] += $comparison['missing_keys_count'];
        $rootReport['unchanged_values'] += $comparison['unchanged_values_count'];
        $rootReport['issues'] = array_merge($rootReport['issues'], $comparison['issues']);
        $rootReport['files'][] = [
            'relative_path' => $relativePath,
            'status' => 'compared',
            'missing_keys' => $comparison['missing_keys_count'],
            'unchanged_values' => $comparison['unchanged_values_count'],
        ];
    }

    return $rootReport;
}

/**
 * @return array<string, string>
 */
function translation_audit_collect_php_files(string $root): array
{
    if (! is_dir($root)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (! $fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php') {
            continue;
        }

        $fullPath = $fileInfo->getPathname();
        $relativePath = substr(str_replace('\\', '/', $fullPath), strlen(str_replace('\\', '/', rtrim($root, DIRECTORY_SEPARATOR))) + 1);
        $files[$relativePath] = $fullPath;
    }

    ksort($files);

    return $files;
}

/**
 * @return array<string, mixed>
 */
function translation_audit_compare_arrays(string $scope, string $relativePath, array $enData, array $viData, array $allowLookup, string $repoRoot): array
{
    $enFlat = translation_audit_flatten($enData);
    $viFlat = translation_audit_flatten($viData);

    $issues = [];
    $missingKeys = 0;
    $unchangedValues = 0;

    foreach ($enFlat as $key => $enValue) {
        if (! array_key_exists($key, $viFlat)) {
            $missingKeys++;
            $issues[] = [
                'type' => 'missing_key',
                'scope' => $scope,
                'file' => translation_audit_relative_path($repoRoot, translation_audit_join_relative($scope, $relativePath)),
                'relative_path' => $relativePath,
                'key' => $key,
            ];
            continue;
        }

        $viValue = $viFlat[$key];
        if (! is_string($enValue) || ! is_string($viValue)) {
            continue;
        }

        if (trim($enValue) === trim($viValue) && ! isset($allowLookup[trim($enValue)])) {
            $unchangedValues++;
            $issues[] = [
                'type' => 'unchanged_value',
                'scope' => $scope,
                'file' => translation_audit_relative_path($repoRoot, translation_audit_join_relative($scope, $relativePath)),
                'relative_path' => $relativePath,
                'key' => $key,
                'value' => $viValue,
            ];
        }
    }

    return [
        'missing_keys_count' => $missingKeys,
        'unchanged_values_count' => $unchangedValues,
        'issues' => $issues,
    ];
}

/**
 * @return array<string, mixed>
 */
function translation_audit_load_locale_array(string $path): array
{
    $data = include $path;

    if (! is_array($data)) {
        throw new RuntimeException('Locale file did not return an array: ' . $path);
    }

    return $data;
}

/**
 * @return array<string, mixed>
 */
function translation_audit_flatten(array $data, string $prefix = ''): array
{
    $flat = [];

    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

        if (is_array($value)) {
            $flat = array_merge($flat, translation_audit_flatten($value, $path));
            continue;
        }

        $flat[$path] = $value;
    }

    return $flat;
}

function translation_audit_join_relative(string $scope, string $relativePath): string
{
    if ($scope === 'core') {
        return 'lang' . DIRECTORY_SEPARATOR . 'vi' . DIRECTORY_SEPARATOR . $relativePath;
    }

    if (str_starts_with($scope, 'module:')) {
        $module = substr($scope, strlen('module:'));

        return 'Modules' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'vi' . DIRECTORY_SEPARATOR . $relativePath;
    }

    if (str_starts_with($scope, 'vendor:')) {
        $vendor = substr($scope, strlen('vendor:'));

        return 'lang' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $vendor . DIRECTORY_SEPARATOR . 'vi' . DIRECTORY_SEPARATOR . $relativePath;
    }

    return $relativePath;
}

function translation_audit_relative_path(string $repoRoot, string $path): string
{
    $normalizedRoot = str_replace('\\', '/', rtrim($repoRoot, DIRECTORY_SEPARATOR));
    $normalizedPath = str_replace('\\', '/', $path);

    if (str_starts_with($normalizedPath, $normalizedRoot . '/')) {
        return substr($normalizedPath, strlen($normalizedRoot) + 1);
    }

    return $path;
}

/**
 * @param array<string, mixed> $report
 */
function translation_audit_render_text(array $report): string
{
    $lines = [];
    $lines[] = 'Translation audit for ' . $report['repo_root'];
    $lines[] = sprintf(
        'Roots: %d | Files: %d | Missing files: %d | Missing keys: %d | Unchanged values: %d | Issues: %d',
        $report['summary']['roots_scanned'],
        $report['summary']['files_checked'],
        $report['summary']['missing_files'],
        $report['summary']['missing_keys'],
        $report['summary']['unchanged_values'],
        $report['summary']['issue_count']
    );

    foreach ($report['issues'] as $issue) {
        $lines[] = sprintf(
            '- [%s] %s :: %s%s',
            $issue['type'],
            $issue['scope'],
            $issue['file'],
            isset($issue['key']) ? ' (' . $issue['key'] . ')' : ''
        );
    }

    return implode(PHP_EOL, $lines) . PHP_EOL;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $options = getopt('', ['repo-root::', 'allow-values::', 'json']);
    $repoRoot = $options['repo-root'] ?? dirname(__DIR__);
    $allowValues = [];

    if (! empty($options['allow-values'])) {
        $allowValues = array_map('trim', explode(',', (string) $options['allow-values']));
    }

    $report = translation_audit_run($repoRoot, $allowValues);

    if (array_key_exists('json', $options)) {
        fwrite(STDOUT, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
    } else {
        fwrite(STDOUT, translation_audit_render_text($report));
    }

    exit($report['summary']['issue_count'] > 0 ? 1 : 0);
}
