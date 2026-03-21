<#
.SYNOPSIS
    Pre-warm the read_file_cache and optionally re-index semantic code search.

.DESCRIPTION
    Run directly or via Windows Task Scheduler (see -Register switch) to ensure
    the disk cache is always warm before starting a Cursor or Codex session.

    Direct usage:
        .\scripts\warm-cache.ps1

    Register as a nightly Task Scheduler job (run once, as Administrator):
        .\scripts\warm-cache.ps1 -Register

    Unregister the scheduled task:
        .\scripts\warm-cache.ps1 -Unregister

.PARAMETER Register
    Creates a Windows Scheduled Task that runs this script nightly at 02:00.

.PARAMETER Unregister
    Removes the Windows Scheduled Task created by -Register.

.PARAMETER SkipSemantic
    Skip semantic index re-build even if Ollama is reachable.

.PARAMETER MaxFiles
    Maximum number of files to warm into the read_file cache (default 10000).
#>

param(
    [switch]$Register,
    [switch]$Unregister,
    [switch]$SkipSemantic,
    [int]$MaxFiles = 10000
)

$ErrorActionPreference = 'Stop'

$TaskName   = 'UPOS612-WarmCache'
$RepoRoot   = Split-Path -Parent $PSScriptRoot
$ScriptPath = Join-Path $RepoRoot 'scripts\warm-cache.ps1'

# ── Task Scheduler registration ──────────────────────────────────────────────

if ($Unregister) {
    if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Host "Scheduled task '$TaskName' removed."
    } else {
        Write-Host "No scheduled task named '$TaskName' found."
    }
    exit 0
}

if ($Register) {
    $PhpExe   = (Get-Command php -ErrorAction SilentlyContinue)?.Source
    if (-not $PhpExe) {
        throw 'php.exe not found on PATH. Add WAMP PHP to your system PATH and retry.'
    }

    $Action   = New-ScheduledTaskAction `
        -Execute 'powershell.exe' `
        -Argument "-NonInteractive -ExecutionPolicy Bypass -File `"$ScriptPath`" -SkipSemantic:$false"

    $Trigger  = New-ScheduledTaskTrigger -Daily -At '02:00'
    $Settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -RunOnlyIfNetworkAvailable:$false

    Register-ScheduledTask `
        -TaskName  $TaskName `
        -Action    $Action `
        -Trigger   $Trigger `
        -Settings  $Settings `
        -RunLevel  Highest `
        -Force | Out-Null

    Write-Host "Scheduled task '$TaskName' registered — runs nightly at 02:00."
    Write-Host "To run immediately: Start-ScheduledTask -TaskName '$TaskName'"
    exit 0
}

# ── Helpers ───────────────────────────────────────────────────────────────────

function Find-Php {
    $php = (Get-Command php -ErrorAction SilentlyContinue)?.Source
    if (-not $php) { throw 'php.exe not found on PATH.' }
    return $php
}

function Test-OllamaReachable {
    param([string]$BaseUrl = 'http://127.0.0.1:11434')
    try {
        $response = Invoke-WebRequest -Uri "$BaseUrl/api/tags" -TimeoutSec 2 -UseBasicParsing -ErrorAction SilentlyContinue
        return $response.StatusCode -eq 200
    } catch {
        return $false
    }
}

# ── Main ──────────────────────────────────────────────────────────────────────

$Php = Find-Php
Write-Host "Repo root : $RepoRoot"
Write-Host "PHP       : $Php"
Write-Host ""

# 1. Warm read_file_cache ─────────────────────────────────────────────────────

$WarmBin = Join-Path $RepoRoot 'mcp\read-file-cache-mcp\bin\warm-cache'
$WarmVendor = Join-Path $RepoRoot 'mcp\read-file-cache-mcp\vendor\autoload.php'

if (-not (Test-Path $WarmVendor)) {
    Write-Warning "read-file-cache-mcp vendor missing — skipping. Run: cd mcp/read-file-cache-mcp && composer install"
} else {
    Write-Host "[1/2] Warming read_file_cache (max $MaxFiles files)..."
    $env:MCP_READ_FILE_WORKSPACE_ROOT = $RepoRoot
    $env:MCP_READ_FILE_CACHE_ROOT     = Join-Path $RepoRoot '.cache\read-file-cache-mcp'

    & $Php $WarmBin --max-files=$MaxFiles
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "warm-cache exited with code $LASTEXITCODE — cache may be partial."
    } else {
        Write-Host "  read_file_cache warm complete."
    }
}

# 2. Re-index semantic code search ────────────────────────────────────────────

$SemanticBin    = Join-Path $RepoRoot 'mcp\semantic-code-search-mcp\bin\index-codebase'
$SemanticVendor = Join-Path $RepoRoot 'mcp\semantic-code-search-mcp\vendor\autoload.php'

if ($SkipSemantic) {
    Write-Host "[2/2] Semantic re-index skipped (-SkipSemantic)."
} elseif (-not (Test-Path $SemanticVendor)) {
    Write-Warning "semantic-code-search-mcp vendor missing — skipping. Run: cd mcp/semantic-code-search-mcp && composer install"
} else {
    $OllamaHost = $env:MCP_SEMANTIC_OLLAMA_HOST
    if (-not $OllamaHost) { $OllamaHost = 'http://127.0.0.1:11434' }

    Write-Host "[2/2] Checking Ollama at $OllamaHost..."
    if (-not (Test-OllamaReachable -BaseUrl $OllamaHost)) {
        Write-Warning "  Ollama not reachable — skipping semantic re-index. Start Ollama and retry."
    } else {
        Write-Host "  Ollama reachable. Running semantic index..."
        $env:MCP_SEMANTIC_WORKSPACE_ROOT = $RepoRoot
        $env:MCP_SEMANTIC_INDEX_ROOT     = Join-Path $RepoRoot '.cache\semantic-code-search-mcp'
        $env:MCP_SEMANTIC_OLLAMA_HOST    = $OllamaHost

        & $Php $SemanticBin
        if ($LASTEXITCODE -ne 0) {
            Write-Warning "  Semantic index exited with code $LASTEXITCODE."
        } else {
            Write-Host "  Semantic index complete."
        }
    }
}

Write-Host ""
Write-Host "Done. Cache is ready for next Cursor/Codex session."
