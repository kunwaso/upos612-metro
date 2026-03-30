<#
.SYNOPSIS
    Unified MCP warm/index automation for Codex sessions.

.DESCRIPTION
    Automates read-file cache warm-up, semantic indexing, and GitNexus refresh with
    two explicit profiles:
      - startup: fast daily readiness
      - nightly-embeddings: deeper nightly maintenance with GitNexus embeddings

    It can also register/unregister Windows Task Scheduler jobs for nightly runs.

.EXAMPLES
    .\scripts\warm-cache.ps1
    .\scripts\warm-cache.ps1 -Profile nightly-embeddings
    .\scripts\warm-cache.ps1 -Profile startup -RequireGitNexusReady
    .\scripts\warm-cache.ps1 -Register
    .\scripts\warm-cache.ps1 -Unregister
#>

param(
    [ValidateSet('startup', 'nightly-embeddings')]
    [string]$Profile = 'startup',
    [switch]$Register,
    [switch]$Unregister,
    [switch]$SkipSemantic,
    [switch]$SkipGitNexus,
    [switch]$DeepSemanticProbe,
    [switch]$RequireGitNexusReady,
    [switch]$RequireSemanticReady,
    [switch]$DryRun,
    [int]$MaxFiles = 500,
    [string]$WarmPath = '',
    [string]$GitNexusVersion = '1.4.8'
)

$ErrorActionPreference = 'Stop'

$TaskNameStartup = 'UPOS612-MCP-Startup'
$TaskNameNightlyEmbeddings = 'UPOS612-GitNexus-Embeddings'
$RepoRoot = Split-Path -Parent $PSScriptRoot
$ScriptPath = Join-Path $RepoRoot 'scripts\warm-cache.ps1'
$LogRoot = Join-Path $RepoRoot '.cache\mcp-automation'

function Ensure-LogRoot {
    if (-not (Test-Path $LogRoot)) {
        New-Item -ItemType Directory -Path $LogRoot -Force | Out-Null
    }
}

function New-LogFilePath {
    Ensure-LogRoot
    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    return Join-Path $LogRoot "mcp-$Profile-$stamp.log"
}

function Write-Log {
    param(
        [string]$Message,
        [string]$Level = 'INFO'
    )

    $line = "[{0}] [{1}] {2}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Level.ToUpperInvariant(), $Message
    Write-Host $line
    Add-Content -Path $script:LogFile -Value $line
}

function Find-Php {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    $php = $null
    if ($null -ne $phpCommand) {
        $php = $phpCommand.Source
    }

    if (-not $php) {
        throw 'php.exe not found on PATH.'
    }
    return $php
}

function Find-Npx {
    $npxCmd = Get-Command npx.cmd -ErrorAction SilentlyContinue
    if ($null -ne $npxCmd) {
        return $npxCmd.Source
    }

    $npxCommand = Get-Command npx -ErrorAction SilentlyContinue
    if ($null -ne $npxCommand) {
        return $npxCommand.Source
    }

    return $null
}

function Find-Npm {
    $npmCmd = Get-Command npm.cmd -ErrorAction SilentlyContinue
    if ($null -ne $npmCmd) {
        return $npmCmd.Source
    }

    $npmCommand = Get-Command npm -ErrorAction SilentlyContinue
    if ($null -ne $npmCommand) {
        return $npmCommand.Source
    }

    return $null
}

function Find-GitNexusCli {
    $gitnexusCommand = Get-Command gitnexus -ErrorAction SilentlyContinue
    if ($null -ne $gitnexusCommand) {
        return $gitnexusCommand.Source
    }

    return $null
}

function Resolve-PythonBinary {
    param(
        [string]$ConfiguredPath = ''
    )

    if (-not [string]::IsNullOrWhiteSpace($ConfiguredPath) -and (Test-Path $ConfiguredPath)) {
        return (Resolve-Path $ConfiguredPath).Path
    }

    $pythonCommand = Get-Command python -ErrorAction SilentlyContinue
    if ($null -eq $pythonCommand) {
        return $null
    }

    $candidate = $pythonCommand.Source
    if ([string]::IsNullOrWhiteSpace($candidate)) {
        return $null
    }

    try {
        $probe = & $candidate -c "import sys; print(sys.executable)" 2>$null
        if ($LASTEXITCODE -eq 0 -and -not [string]::IsNullOrWhiteSpace($probe)) {
            $resolvedProbe = $probe.Trim()
            if (Test-Path $resolvedProbe) {
                return (Resolve-Path $resolvedProbe).Path
            }
        }
    } catch {
        # Fall through to candidate path.
    }

    if (Test-Path $candidate) {
        return (Resolve-Path $candidate).Path
    }

    return $null
}

function Invoke-CommandSafe {
    param(
        [string]$Label,
        [scriptblock]$Action
    )

    if ($DryRun) {
        Write-Log "DRY RUN: $Label"
        return $true
    }

    try {
        $global:LASTEXITCODE = 0
        & $Action
        $code = $LASTEXITCODE
        if ($code -ne $null -and $code -ne 0) {
            Write-Log "$Label failed with exit code $code" 'WARN'
            return $false
        }

        Write-Log "$Label completed."
        return $true
    } catch {
        Write-Log "$Label threw: $($_.Exception.Message)" 'WARN'
        return $false
    }
}

function Register-Tasks {
    $actionStartup = New-ScheduledTaskAction `
        -Execute 'powershell.exe' `
        -Argument "-NonInteractive -ExecutionPolicy Bypass -File `"$ScriptPath`" -Profile startup -SkipSemantic:$false -SkipGitNexus:$false"
    $triggerStartup = New-ScheduledTaskTrigger -Daily -At '02:00'

    $actionNightly = New-ScheduledTaskAction `
        -Execute 'powershell.exe' `
        -Argument "-NonInteractive -ExecutionPolicy Bypass -File `"$ScriptPath`" -Profile nightly-embeddings -SkipSemantic:$false -SkipGitNexus:$false"
    $triggerNightly = New-ScheduledTaskTrigger -Daily -At '03:00'

    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -RunOnlyIfNetworkAvailable:$false

    Register-ScheduledTask -TaskName $TaskNameStartup -Action $actionStartup -Trigger $triggerStartup -Settings $settings -RunLevel Highest -Force | Out-Null
    Register-ScheduledTask -TaskName $TaskNameNightlyEmbeddings -Action $actionNightly -Trigger $triggerNightly -Settings $settings -RunLevel Highest -Force | Out-Null

    Write-Host "Registered tasks:"
    Write-Host " - $TaskNameStartup (02:00 daily)"
    Write-Host " - $TaskNameNightlyEmbeddings (03:00 daily)"
}

function Unregister-Tasks {
    foreach ($taskName in @($TaskNameStartup, $TaskNameNightlyEmbeddings)) {
        if (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue) {
            Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
            Write-Host "Removed scheduled task '$taskName'."
        } else {
            Write-Host "No scheduled task named '$taskName' found."
        }
    }
}

if ($Unregister) {
    Unregister-Tasks
    exit 0
}

if ($Register) {
    Register-Tasks
    exit 0
}

$script:LogFile = New-LogFilePath
Write-Log "Profile: $Profile"
Write-Log "Repo root: $RepoRoot"

$Php = Find-Php
$Npx = Find-Npx
$Npm = Find-Npm
$GitNexusCli = Find-GitNexusCli

Write-Log "PHP: $Php"
if ($Npx) {
    Write-Log "npx: $Npx"
} else {
    Write-Log 'npx: not found'
}
if ($Npm) {
    Write-Log "npm: $Npm"
} else {
    Write-Log 'npm: not found'
}
if ($GitNexusCli) {
    Write-Log "gitnexus: $GitNexusCli"
} else {
    Write-Log 'gitnexus: not found'
}

# 1) Warm read_file_cache
$WarmBin = Join-Path $RepoRoot 'mcp\read-file-cache-mcp\bin\warm-cache'
$WarmVendor = Join-Path $RepoRoot 'mcp\read-file-cache-mcp\vendor\autoload.php'
if (-not (Test-Path $WarmVendor)) {
    Write-Log 'read-file-cache-mcp vendor missing; skipping warm-cache.' 'WARN'
} else {
    $env:MCP_READ_FILE_WORKSPACE_ROOT = $RepoRoot
    $env:MCP_READ_FILE_CACHE_ROOT = Join-Path $RepoRoot '.cache\read-file-cache-mcp'

    $warmTargets = @()
    if ([string]::IsNullOrWhiteSpace($WarmPath)) {
        # Avoid expensive workspace-root scans; warm common source roots in bounded batches.
        $warmTargets = @('app', 'Modules', 'resources', 'routes', 'config', 'ai', 'mcp', 'tests')
    } elseif ($WarmPath -like '*,*') {
        $warmTargets = $WarmPath.Split(',') | ForEach-Object { $_.Trim() } | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }
    } else {
        $warmTargets = @($WarmPath)
    }

    foreach ($target in $warmTargets) {
        $warmLabel = "Warm read_file_cache (path=$target, max_files=$MaxFiles)"
        Invoke-CommandSafe -Label $warmLabel -Action { & $Php $WarmBin "--path=$target" "--max-files=$MaxFiles" } | Out-Null
    }
}

# 2) Semantic index (optional but enabled by default in this workflow)
$SemanticBin = Join-Path $RepoRoot 'mcp\semantic-code-search-mcp\bin\index-codebase'
$SemanticVendor = Join-Path $RepoRoot 'mcp\semantic-code-search-mcp\vendor\autoload.php'
if ($SkipSemantic) {
    Write-Log 'Semantic indexing skipped by flag.'
} elseif (-not (Test-Path $SemanticVendor)) {
    Write-Log 'semantic-code-search-mcp vendor missing; skipping semantic index.' 'WARN'
} else {
    $env:MCP_SEMANTIC_WORKSPACE_ROOT = $RepoRoot
    $env:MCP_SEMANTIC_INDEX_ROOT = Join-Path $RepoRoot '.cache\semantic-code-search-mcp'
    $resolvedPython = Resolve-PythonBinary -ConfiguredPath $env:MCP_SEMANTIC_PYTHON_BIN
    if (-not [string]::IsNullOrWhiteSpace($resolvedPython)) {
        $env:MCP_SEMANTIC_PYTHON_BIN = $resolvedPython
        Write-Log "Semantic python runtime: $resolvedPython"
    } else {
        Write-Log 'Python runtime not found; semantic embedding may fail.' 'WARN'
    }
    $env:MCP_SEMANTIC_EMBED_BACKEND = 'huggingface'
    $env:MCP_SEMANTIC_EMBED_MODEL = 'BAAI/bge-small-en'
    $env:MCP_SEMANTIC_HF_DEVICE = 'cpu'
    $env:MCP_SEMANTIC_HF_BATCH_SIZE = '12'
    $env:MCP_SEMANTIC_HF_LOCAL_FILES_ONLY = '1'
    $env:MCP_SEMANTIC_INCLUDE_ROOTS = 'app,Modules,routes,resources/views,config,ai,mcp,.cursor'
    $env:MCP_SEMANTIC_INCLUDE_ROOT_FILES = 'AGENTS.md,AGENTS-FAST.md,composer.json,composer.lock,README.md,modules_statuses.json'
    $env:MCP_SEMANTIC_CHUNK_LINES = '80'
    $env:MCP_SEMANTIC_CHUNK_OVERLAP = '8'
    $env:MCP_SEMANTIC_MAX_FILE_BYTES = '524288'
    Write-Log "Semantic model: $($env:MCP_SEMANTIC_EMBED_MODEL)"
    Write-Log "Semantic roots: $($env:MCP_SEMANTIC_INCLUDE_ROOTS)"

    $semanticArgs = @()
    $semanticMode = 'incremental'
    if ($Profile -eq 'nightly-embeddings') {
        $semanticArgs += '--force'
        $semanticMode = 'force'
    }

    $semanticLabel = "Semantic index refresh ($Profile, $semanticMode)"
    Invoke-CommandSafe -Label $semanticLabel -Action { & $Php $SemanticBin @semanticArgs } | Out-Null
}

# 3) GitNexus refresh cadence
if ($SkipGitNexus) {
    Write-Log 'GitNexus refresh skipped by flag.'
} else {
    $gitnexusArgs = @('analyze')
    if ($Profile -eq 'nightly-embeddings') {
        $gitnexusArgs += '--embeddings'
        Write-Log 'GitNexus nightly profile: embeddings enabled (--embeddings).'
    }
    $npxArgs = @('-y', "gitnexus@$GitNexusVersion") + $gitnexusArgs

    $ok = $false
    if ($GitNexusCli) {
        Write-Log 'Using local gitnexus CLI as primary launcher.'
        $ok = Invoke-CommandSafe -Label "GitNexus analyze via local CLI ($Profile)" -Action { & $GitNexusCli @gitnexusArgs }
    } elseif ($Npx) {
        Write-Log 'Local gitnexus CLI not found; falling back to npx.' 'WARN'
        $ok = Invoke-CommandSafe -Label "GitNexus analyze via npx ($Profile)" -Action { & $Npx @npxArgs }
    } else {
        Write-Log 'Neither local gitnexus CLI nor npx were found.' 'WARN'
    }

    if (-not $ok -and -not $GitNexusCli -and $Npx -and $Npm) {
        Write-Log 'Attempting npm cache verify before one npx retry.' 'WARN'
        $cacheOk = Invoke-CommandSafe -Label 'npm cache verify' -Action { & $Npm cache verify }
        if ($cacheOk) {
            $ok = Invoke-CommandSafe -Label "GitNexus analyze via npx retry ($Profile)" -Action { & $Npx @npxArgs }
        }
    }

    if (-not $ok -and -not $GitNexusCli -and $Npx) {
        Write-Log 'GitNexus npx path failed and no local CLI is available.' 'WARN'
    }

    if (-not $ok) {
        Write-Log 'GitNexus analyze failed after npx/local fallback attempts.' 'WARN'
    }
}

# 4) Health check
$HealthScript = Join-Path $RepoRoot 'scripts\check-mcp-health.php'
if (Test-Path $HealthScript) {
    if ($DeepSemanticProbe -or $Profile -eq 'nightly-embeddings') {
        $env:MCP_HEALTH_DEEP_SEMANTIC_PROBE = '1'
    }
    if ($RequireGitNexusReady) {
        $env:MCP_HEALTH_REQUIRE_GITNEXUS_READY = '1'
    }
    if ($RequireSemanticReady) {
        $env:MCP_HEALTH_REQUIRE_SEMANTIC_READY = '1'
    }

    Invoke-CommandSafe -Label 'MCP health check' -Action { & $Php $HealthScript } | Out-Null
}

Write-Log "Done. Log file: $script:LogFile"
