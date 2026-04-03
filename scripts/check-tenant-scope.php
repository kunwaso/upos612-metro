#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Tenant Scope Heuristic Checker
 *
 * Scans changed PHP files for Eloquent calls that fetch a single record by
 * primary key without an adjacent business_id scope, which is the canonical
 * multi-tenant security issue documented in ai/known-issues.md §1.2.
 *
 * This is a HEURISTIC — it has intentional false positives and false negatives.
 * Its job is to surface likely misses for human review, not to prove correctness.
 *
 * False positive escapes:
 *   a) Add an inline comment on the same line:  // agent-compliance:ignore-business_id
 *   b) Add the file path to scripts/agent-compliance-tenant-allowlist.txt
 *
 * Exit codes:
 *   0 = clean (no suspicious patterns found)
 *   1 = suspicious patterns found (review required)
 *   2 = invocation error
 *
 * Usage:
 *   php scripts/check-tenant-scope.php [--base=<sha>]
 *
 * Environment variables:
 *   COMPLIANCE_BASE_SHA   base commit SHA (falls back to --base or origin/main)
 */

$repoRoot = realpath(__DIR__ . '/..');
if ($repoRoot === false) {
    fwrite(STDERR, "Unable to resolve repo root.\n");
    exit(2);
}

// ---------------------------------------------------------------------------
// Argument parsing
// ---------------------------------------------------------------------------
$baseSha = getenv('COMPLIANCE_BASE_SHA') ?: null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $baseSha = substr($arg, 7);
    }
}

// ---------------------------------------------------------------------------
// Directories that are never tenant-scoped application code
// ---------------------------------------------------------------------------
$SKIP_PATH_FRAGMENTS = [
    '/vendor/',
    '/node_modules/',
    '/storage/',
    '/bootstrap/',
    '/database/migrations/',
    '/database/seeders/',
    '/database/factories/',
    '/tests/',
    '/mcp/',
    '/scripts/',
    '/config/',
    '/public/',
];

// ---------------------------------------------------------------------------
// Suspicious patterns: Eloquent fetch-by-PK without nearby business_id scope.
//
// We look for the "bare fetch" patterns on a single line.  If the SAME file
// also contains "->where('business_id'" or "->where(\"business_id\"" anywhere,
// we reduce confidence significantly, so we only flag when the model call AND
// absence evidence are both present.
//
// Patterns we flag:
//   Model::findOrFail($id)
//   Model::find($id)
//   Model::findOrNew($id)
//   Model::findMany([...])
// where the line has no "where('business_id'" and no ignore token.
// ---------------------------------------------------------------------------
$BARE_FETCH_REGEX = '/\b(findOrFail|find|findOrNew|findMany)\s*\(/';

// ---------------------------------------------------------------------------
// Allowlist loader
// ---------------------------------------------------------------------------
function load_tenant_allowlist(string $repoRoot): array
{
    $path = $repoRoot . '/scripts/agent-compliance-tenant-allowlist.txt';
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
        $list[] = str_replace('\\', '/', ltrim($line, '/'));
    }
    return $list;
}

// ---------------------------------------------------------------------------
// Collect changed PHP files
// ---------------------------------------------------------------------------
function collect_php_files_diff(string $repoRoot, ?string $baseSha, array $skipFragments): array
{
    if ($baseSha === null) {
        $baseSha = trim((string) shell_exec('git -C ' . escapeshellarg($repoRoot) . ' merge-base HEAD origin/main 2>/dev/null'))
               ?: trim((string) shell_exec('git -C ' . escapeshellarg($repoRoot) . ' merge-base HEAD origin/master 2>/dev/null'))
               ?: 'HEAD~1';
    }

    $cmd    = 'git -C ' . escapeshellarg($repoRoot)
            . ' diff --name-only --diff-filter=ACMR '
            . escapeshellarg($baseSha) . ' HEAD -- "*.php" 2>/dev/null';
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
        $relNorm = str_replace('\\', '/', $rel);
        // Skip non-application paths
        $skip = false;
        foreach ($skipFragments as $fragment) {
            if (str_contains('/' . $relNorm . '/', $fragment)) {
                $skip = true;
                break;
            }
        }
        if ($skip) {
            continue;
        }
        // Only non-Blade PHP files (Blade already covered by compliance script)
        if (str_ends_with($relNorm, '.blade.php')) {
            continue;
        }
        $abs = $repoRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (file_exists($abs)) {
            $files[] = ['abs' => $abs, 'rel' => $relNorm];
        }
    }
    return $files;
}

// ---------------------------------------------------------------------------
// Scan one file for bare fetch patterns without nearby business_id guard.
//
// Heuristic: if the file contains at least one ->where('business_id' anywhere,
// we assume it is scoped and only warn; if it has NO business_id reference at
// all, we flag more prominently.
// ---------------------------------------------------------------------------
/**
 * @return array<int, array{line: int, text: string, severity: string}>
 */
function scan_php_file_tenant(string $path, string $fetchRegex): array
{
    $content = file_get_contents($path);
    if ($content === false) {
        return [];
    }

    $hasBusinessIdScope = str_contains($content, "where('business_id'")
                       || str_contains($content, 'where("business_id"')
                       || str_contains($content, 'business_id');

    $lines   = explode("\n", $content);
    $results = [];

    foreach ($lines as $lineNo => $lineText) {
        if (str_contains($lineText, 'agent-compliance:ignore-business_id')) {
            continue;
        }
        if (! preg_match($fetchRegex, $lineText)) {
            continue;
        }
        // If the line itself already has a business_id scope, skip
        if (str_contains($lineText, 'business_id')) {
            continue;
        }
        // If the line calls a known non-tenant helper (e.g. User::find, finding
        // by a system/super-admin context), we still flag but with lower severity
        $severity = $hasBusinessIdScope ? 'WARN' : 'FLAG';
        $results[] = [
            'line'     => $lineNo + 1,
            'text'     => rtrim($lineText),
            'severity' => $severity,
        ];
    }

    return $results;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------
echo "Agent Compliance Checker — Tenant Scope Heuristic\n";
echo str_repeat('-', 60) . "\n";

$displayBase = $baseSha ?? '(auto: merge-base with origin/main)';
echo "Mode: diff-only (base = {$displayBase})\n";

$files     = collect_php_files_diff($repoRoot, $baseSha, $SKIP_PATH_FRAGMENTS);
$allowlist = load_tenant_allowlist($repoRoot);

echo 'Files to check: ' . count($files) . "\n";
echo 'Allowlist:      ' . count($allowlist) . " grandfathered path(s)\n\n";

$totalFindings  = 0;
$flagFiles      = 0;
$warnFiles      = 0;

foreach ($files as ['abs' => $abs, 'rel' => $rel]) {
    if (in_array($rel, $allowlist, true)) {
        echo "  SKIP (allowlisted)  {$rel}\n";
        continue;
    }

    $findings = scan_php_file_tenant($abs, $BARE_FETCH_REGEX);
    if (count($findings) === 0) {
        continue;
    }

    foreach ($findings as $f) {
        echo "  {$f['severity']}  {$rel}:{$f['line']}\n";
        echo "       " . substr(ltrim($f['text']), 0, 120) . "\n";
        if ($f['severity'] === 'FLAG') {
            echo "       No business_id scope found anywhere in this file.\n";
        } else {
            echo "       File has business_id elsewhere; verify this specific call is scoped.\n";
        }
        echo "\n";
        $totalFindings++;
        if ($f['severity'] === 'FLAG') {
            $flagFiles++;
        } else {
            $warnFiles++;
        }
    }
}

echo str_repeat('-', 60) . "\n";
if ($totalFindings === 0) {
    echo "OK — No suspicious tenant-scope patterns found.\n";
    exit(0);
}

echo "Findings: {$totalFindings} total";
if ($flagFiles > 0) {
    echo " ({$flagFiles} FLAG — no business_id in file)";
}
if ($warnFiles > 0) {
    echo " ({$warnFiles} WARN — file has business_id but this call may be unscoped)";
}
echo "\n";
echo "\nThis is a HEURISTIC. Review each finding and confirm the query includes:\n";
echo "  Model::where('business_id', \$business_id)->findOrFail(\$id)\n";
echo "\nTo suppress a false positive, add to the same line:\n";
echo "  // agent-compliance:ignore-business_id\n";
echo "\nTo granfather a file, add its path to:\n";
echo "  scripts/agent-compliance-tenant-allowlist.txt\n";

// Exit 1 only for FLAG severity (no business_id at all in file)
exit($flagFiles > 0 ? 1 : 0);
