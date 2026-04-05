<#
.SYNOPSIS
    Install, uninstall, or inspect managed MCP git-hook blocks.

.DESCRIPTION
    Adds a marker-based, non-destructive block to:
      - .git/hooks/pre-push

    Semantic reindex + GitNexus analyze run only when you git push (and only if pushed
    commits touch indexed paths). Work is backgrounded so push is not blocked.
    Incremental semantic index is used (no --force).

    On install, managed blocks are removed from post-commit and post-merge so you do
    not reindex on every local commit or pull.

    Existing hook content is preserved. Only the managed marker block is replaced.
#>

param(
    [ValidateSet('install', 'uninstall', 'status')]
    [string]$Action = 'install',
    [string]$GitNexusVersion = '1.4.8'
)

$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$HooksDir = Join-Path $RepoRoot '.git\hooks'

$MarkerStart = '# >>> UPOS612 MCP MANAGED START >>>'
$MarkerEnd = '# <<< UPOS612 MCP MANAGED END <<<'

function Ensure-HookFile {
    param([string]$Path)

    if (-not (Test-Path $Path)) {
        Set-Content -Path $Path -Value "#!/bin/sh`n" -Encoding ASCII
    }
}

function Remove-ManagedBlock {
    param([string]$Text)

    $pattern = "(?s)$([regex]::Escape($MarkerStart)).*?$([regex]::Escape($MarkerEnd))\r?\n?"
    return [regex]::Replace($Text, $pattern, '')
}

function Build-Block {
    param(
        [string]$HookScript,
        [switch]$PassGitPrePushArgs
    )

    $invokeLine = if ($PassGitPrePushArgs) {
        "  sh ""`$REPO_ROOT/scripts/hooks/$HookScript"" ""$GitNexusVersion"" ""`$1"" ""`$2"""
    } else {
        "  sh ""`$REPO_ROOT/scripts/hooks/$HookScript"" ""$GitNexusVersion"""
    }

    $lines = @(
        $MarkerStart,
        'REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null)"',
        "if [ -n ""`$REPO_ROOT"" ] && [ -f ""`$REPO_ROOT/scripts/hooks/$HookScript"" ]; then",
        $invokeLine,
        'fi',
        $MarkerEnd
    )

    return ($lines -join "`n")
}

function Upsert-Hook {
    param(
        [string]$HookName,
        [string]$HookScript,
        [switch]$PassGitPrePushArgs
    )

    $hookPath = Join-Path $HooksDir $HookName
    Ensure-HookFile -Path $hookPath

    $text = Get-Content $hookPath -Raw
    if (-not $text.StartsWith('#!/bin/sh')) {
        $text = "#!/bin/sh`n" + $text
    }

    $clean = Remove-ManagedBlock -Text $text
    $block = Build-Block -HookScript $HookScript -PassGitPrePushArgs:$PassGitPrePushArgs

    $exitPattern = '(?m)^\s*exit\s+0\s*$'
    $exitMatches = [regex]::Matches($clean, $exitPattern)
    if ($exitMatches.Count -gt 0) {
        $lastExit = $exitMatches[$exitMatches.Count - 1]
        $before = $clean.Substring(0, $lastExit.Index).TrimEnd()
        $after = $clean.Substring($lastExit.Index).TrimStart("`r", "`n")
        $updated = $before + "`n`n" + $block + "`n`n" + $after
        $updated = $updated.TrimEnd() + "`n"
    } else {
        $updated = $clean.TrimEnd() + "`n`n" + $block + "`n"
    }
    Set-Content -Path $hookPath -Value $updated -Encoding ASCII
    Write-Host "Updated managed block in $hookPath"
}

function Uninstall-HookBlock {
    param([string]$HookName)

    $hookPath = Join-Path $HooksDir $HookName
    if (-not (Test-Path $hookPath)) {
        Write-Host "Hook not found: $hookPath"
        return
    }

    $text = Get-Content $hookPath -Raw
    $updated = Remove-ManagedBlock -Text $text
    Set-Content -Path $hookPath -Value ($updated.TrimEnd() + "`n") -Encoding ASCII
    Write-Host "Removed managed block from $hookPath"
}

function Show-Status {
    param([string]$HookName)

    $hookPath = Join-Path $HooksDir $HookName
    if (-not (Test-Path $hookPath)) {
        Write-Host "${HookName}: missing"
        return
    }

    $text = Get-Content $hookPath -Raw
    $hasManaged = $text.Contains($MarkerStart) -and $text.Contains($MarkerEnd)
    Write-Host "${HookName}: $(if ($hasManaged) { 'managed block present' } else { 'managed block absent' })"
}

if (-not (Test-Path $HooksDir)) {
    throw "Hooks directory not found: $HooksDir"
}

switch ($Action) {
    'install' {
        Upsert-Hook -HookName 'pre-push' -HookScript 'pre-push-mcp.sh' -PassGitPrePushArgs
        Uninstall-HookBlock -HookName 'post-commit'
        Uninstall-HookBlock -HookName 'post-merge'
        Write-Host 'Installed pre-push MCP sync; removed managed blocks from post-commit and post-merge (if any).'
    }
    'uninstall' {
        Uninstall-HookBlock -HookName 'pre-push'
        Uninstall-HookBlock -HookName 'post-commit'
        Uninstall-HookBlock -HookName 'post-merge'
    }
    'status' {
        Show-Status -HookName 'pre-push'
        Show-Status -HookName 'post-commit'
        Show-Status -HookName 'post-merge'
    }
}
