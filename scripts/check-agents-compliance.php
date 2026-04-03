#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Agent Compliance Checker
 *
 * Checks Blade files against constitution rules from AGENTS.md and
 * .cursor/rules/laravel-coding-constitution.mdc.
 *
 * Modes:
 *   Default (diff): scans only files changed since a base commit.
 *   --full:         scans all Blade files under resources/views and
 *                   Modules\/**\/Resources\/views, using the repo allowlist
 *                   at scripts/agent-compliance-blade-allowlist.txt for
 *                   grandfathered legacy violations.
 *
 * Exit codes:
 *   0 = clean
 *   1 = violations found
 *   2 = invocation error
 *
 * Usage:
 *   php scripts/check-agents-compliance.php [--full] [--base=<sha>]
 *
 * Environment variables (for CI):
 *   COMPLIANCE_BASE_SHA   base commit SHA for diff mode (falls back to --base arg or origin/main)
 */

$repoRoot = realpath(__DIR__ . '/..');
if ($repoRoot === false) {
    fwrite(STDERR, "Unable to resolve repo root.\n");
    exit(2);
}

// ---------------------------------------------------------------------------
// Argument parsing
// ---------------------------------------------------------------------------
$fullScan = in_array('--full', $argv, true);
$baseSha  = getenv('COMPLIANCE_BASE_SHA') ?: null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $baseSha = substr($arg, 7);
    }
}

// ---------------------------------------------------------------------------
// Forbidden Blade patterns (regex => human-readable message)
// Constitution: AGENTS.md §2.5a + .cursor/rules/laravel-coding-constitution.mdc
//
// Targeted at DEFAULT assignments and in-view computation:
//   @php $x = $x ?? ...
//   @php $x = $x ?: ...
//   @php $config = config(...)
//   @php $x = isset($x) ? $x : ...
//   multi-line @php blocks that assign from session(), config(), or request()
//
// NOTE: A bare @php that only aliases an already-passed variable (e.g.
// "$document = $inventoryDocument;") is NOT a constitution violation and is
// intentionally excluded so the check stays narrow and low in false positives.
// ---------------------------------------------------------------------------
$BLADE_FORBIDDEN_PATTERNS = [
    // Variable default/fallback in @php — the canonical violation
    '/@php\s[^\n]*\$\w+\s*=\s*\$\w+\s*\?\?/'
        => '@php assigns a default via ??  (move default to controller/composer)',

    '/@php\s[^\n]*\$\w+\s*=\s*\$\w+\s*\?:/'
        => '@php assigns a fallback via ?: (move default to controller/composer)',

    '/@php\s[^\n]*isset\s*\(\s*\$\w+\s*\)\s*\?/'
        => '@php uses isset ternary to default a variable (move to controller/composer)',

    // In-view config() pull
    '/@php\s[^\n]*\$\w+\s*=\s*config\s*\(/'
        => '@php reads config() directly in Blade (prepare data in controller/composer)',

    // In-view session() pull (not the @format_ directives — just raw session())
    '/@php\s[^\n]*\$\w+\s*=\s*session\s*\(/'
        => '@php reads session() directly in Blade (prepare data in controller/composer)',

    // In-view request() pull
    '/@php\s[^\n]*\$\w+\s*=\s*request\s*\(/'
        => '@php reads request() directly in Blade (prepare data in controller/composer)',

    // Direct DB / Eloquent call inside @php block
    '/@php\s[^\n]*::(find|findOrFail|where|all|get|first)\s*\(/'
        => '@php runs an Eloquent query in Blade (move queries to controller/Util)',
];

// ---------------------------------------------------------------------------
// Allowlist: paths that are grandfathered for --full mode.
// One path per line (relative to repo root, forward-slashes, case-sensitive).
// Lines starting with # are comments.
// ---------------------------------------------------------------------------
function load_allowlist(string $repoRoot): array
{
    $path = $repoRoot . '/scripts/agent-compliance-blade-allowlist.txt';
    if (! file_exists($path)) {
        return [];
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $list  = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        // Normalise to forward-slashes relative to repo root
        $list[] = str_replace('\\', '/', ltrim($line, '/'));
    }
    return $list;
}

// ---------------------------------------------------------------------------
// Collect Blade files to scan
// ---------------------------------------------------------------------------
function collect_blade_files_diff(string $repoRoot, ?string $baseSha): array
{
    if ($baseSha === null) {
        // Try to resolve a sensible default
        $baseSha = trim((string) shell_exec('git -C ' . escapeshellarg($repoRoot) . ' merge-base HEAD origin/main 2>/dev/null'))
               ?: trim((string) shell_exec('git -C ' . escapeshellarg($repoRoot) . ' merge-base HEAD origin/master 2>/dev/null'))
               ?: 'HEAD~1';
    }

    $cmd    = 'git -C ' . escapeshellarg($repoRoot)
            . ' diff --name-only --diff-filter=ACMR '
            . escapeshellarg($baseSha) . ' HEAD -- "*.blade.php" 2>/dev/null';
    $output = shell_exec($cmd);
    if ($output === null || trim($output) === '') {
        return [];
    }

    $files = [];
    foreach (explode("\n", trim($output)) as $rel) {
        $rel = trim($rel);
        if ($rel === '') {
            continue;
        }
        $abs = $repoRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (file_exists($abs)) {
            $files[] = ['abs' => $abs, 'rel' => str_replace('\\', '/', $rel)];
        }
    }
    return $files;
}

/**
 * @return array<int, array{abs: string, rel: string}>
 */
function collect_blade_files_full(string $repoRoot): array
{
    $roots = [
        $repoRoot . '/resources/views',
        $repoRoot . '/Modules',
    ];
    $files = [];
    foreach ($roots as $root) {
        if (! is_dir($root)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        /** @var SplFileInfo $file */
        foreach ($it as $file) {
            if (! $file->isFile()) {
                continue;
            }
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $abs = $file->getRealPath();
            if ($abs === false) {
                continue;
            }
            $rel = str_replace('\\', '/', ltrim(substr($abs, strlen($repoRoot)), '/\\'));
            // Skip node_modules inside Modules/*/
            if (str_contains($rel, '/node_modules/')) {
                continue;
            }
            $files[] = ['abs' => $abs, 'rel' => $rel];
        }
    }
    return $files;
}

// ---------------------------------------------------------------------------
// Scan a single file and return violations
// ---------------------------------------------------------------------------
/**
 * @param array<string, string> $patterns
 * @return array<int, array{line: int, pattern: string, message: string, text: string}>
 */
function scan_blade_file(string $path, array $patterns): array
{
    $content = file_get_contents($path);
    if ($content === false) {
        return [];
    }
    $lines      = explode("\n", $content);
    $violations = [];

    foreach ($lines as $lineNo => $lineText) {
        // Skip lines with the inline ignore token
        if (str_contains($lineText, 'agent-compliance:ignore')) {
            continue;
        }
        foreach ($patterns as $regex => $message) {
            if (preg_match($regex, $lineText)) {
                $violations[] = [
                    'line'    => $lineNo + 1,
                    'pattern' => $regex,
                    'message' => $message,
                    'text'    => rtrim($lineText),
                ];
            }
        }
    }
    return $violations;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------
echo "Agent Compliance Checker — Blade Constitution\n";
echo str_repeat('-', 60) . "\n";

if ($fullScan) {
    echo "Mode: full repo scan\n";
    $files     = collect_blade_files_full($repoRoot);
    $allowlist = load_allowlist($repoRoot);
    echo 'Allowlist: ' . count($allowlist) . " grandfathered path(s)\n";
} else {
    $displayBase = $baseSha ?? '(auto: merge-base with origin/main)';
    echo "Mode: diff-only (base = {$displayBase})\n";
    $files     = collect_blade_files_diff($repoRoot, $baseSha);
    $allowlist = [];
}

echo 'Files to check: ' . count($files) . "\n\n";

$totalViolations = 0;
$violatingFiles  = 0;

foreach ($files as ['abs' => $abs, 'rel' => $rel]) {
    // Full scan: skip grandfathered paths
    if ($fullScan && in_array($rel, $allowlist, true)) {
        echo "  SKIP (allowlisted)  {$rel}\n";
        continue;
    }

    $violations = scan_blade_file($abs, $BLADE_FORBIDDEN_PATTERNS);
    if (count($violations) === 0) {
        continue;
    }

    $violatingFiles++;
    foreach ($violations as $v) {
        echo "  FAIL  {$rel}:{$v['line']}\n";
        echo "        Rule:  {$v['message']}\n";
        echo "        Code:  " . substr(ltrim($v['text']), 0, 120) . "\n\n";
        $totalViolations++;
    }
}

echo str_repeat('-', 60) . "\n";
if ($totalViolations === 0) {
    echo "OK — No constitution violations found.\n";
    exit(0);
}

echo "FAIL — {$totalViolations} violation(s) in {$violatingFiles} file(s).\n";
echo "\nTo suppress a single accepted legacy line, add a comment on that line:\n";
echo "  {{-- agent-compliance:ignore --}}\n";
echo "\nFor full-scan grandfathering, add the file path to:\n";
echo "  scripts/agent-compliance-blade-allowlist.txt\n";
exit(1);
