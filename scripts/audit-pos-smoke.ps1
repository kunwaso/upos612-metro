<#
.SYNOPSIS
    Bootstrap and run authenticated POS smoke audits using audit-web-mcp.

.DESCRIPTION
    Modes:
      - bootstrap : Opens interactive audit on /login and saves storage state.
      - single    : Runs a single authenticated smoke check against one POS URL.
      - matrix    : Runs authenticated smoke checks across GET routes under a prefix.

    Examples:
      .\scripts\audit-pos-smoke.ps1 -Mode bootstrap -BaseUrl https://upos612
      .\scripts\audit-pos-smoke.ps1 -Mode single -BaseUrl https://upos612 -PosPath pos
      .\scripts\audit-pos-smoke.ps1 -Mode matrix -BaseUrl https://upos612 -PathPrefix pos
#>

param(
    [ValidateSet('bootstrap', 'single', 'matrix')]
    [string]$Mode = 'single',

    [string]$BaseUrl = 'https://upos612',

    [string]$PosPath = 'pos',

    [string]$PathPrefix = 'pos',

    [string]$StorageStatePath = 'output/playwright/audit-web-mcp/reports/.auth/pos-admin.json',

    [string]$ReportSlug = 'pos-smoke',

    [ValidateSet('domcontentloaded', 'load', 'networkidle', 'commit')]
    [string]$WaitUntil = 'networkidle',

    [int]$WaitAfterLoadMs = 1200,

    [int]$Concurrency = 3,

    [switch]$OpenDebugChrome,

    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'

function Join-Url {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Root,

        [Parameter(Mandatory = $true)]
        [string]$Path
    )

    $normalizedRoot = $Root.TrimEnd('/')
    $normalizedPath = $Path.TrimStart('/')

    if ([string]::IsNullOrWhiteSpace($normalizedPath)) {
        return $normalizedRoot
    }

    return "$normalizedRoot/$normalizedPath"
}

function Get-ToolExecutable {
    param([Parameter(Mandatory = $true)][string]$CommandName)

    $command = Get-Command $CommandName -ErrorAction SilentlyContinue
    if (-not $command) {
        throw "$CommandName not found on PATH."
    }

    return $command.Source
}

function Parse-JsonFromOutputText {
    param([Parameter(Mandatory = $true)][string]$OutputText)

    $lines = $OutputText -split "`r?`n" | Where-Object { $_.Trim() -ne '' }
    for ($i = $lines.Count - 1; $i -ge 0; $i -= 1) {
        $candidate = $lines[$i].Trim()
        if (-not ($candidate.StartsWith('{') -or $candidate.StartsWith('['))) {
            continue
        }

        try {
            return $candidate | ConvertFrom-Json -ErrorAction Stop
        } catch {
            continue
        }
    }

    $startCandidates = @('[', '{')
    foreach ($startToken in $startCandidates) {
        $startIndex = $OutputText.IndexOf($startToken)
        if ($startIndex -lt 0) {
            continue
        }

        $candidate = $OutputText.Substring($startIndex).Trim()
        try {
            return $candidate | ConvertFrom-Json -ErrorAction Stop
        } catch {
            continue
        }
    }

    throw 'Could not parse JSON payload from audit-web output.'
}

function Invoke-NodeWithJsonPayload {
    param(
        [Parameter(Mandatory = $true)][string]$NodePath,
        [Parameter(Mandatory = $true)][string]$ScriptPath,
        [Parameter(Mandatory = $true)][hashtable]$Payload,
        [Parameter(Mandatory = $true)][string]$WorkingDirectory
    )

    $payloadJson = $Payload | ConvertTo-Json -Depth 30 -Compress

    if ($DryRun) {
        Write-Host '[DryRun] Working directory:' $WorkingDirectory
        Write-Host '[DryRun] Command:' $NodePath $ScriptPath
        Write-Host '[DryRun] Payload:' $payloadJson
        return $null
    }

    Push-Location $WorkingDirectory
    try {
        $rawOutput = $payloadJson | & $NodePath $ScriptPath 2>&1
        $exitCode = $LASTEXITCODE
    } finally {
        Pop-Location
    }

    $outputText = ($rawOutput | ForEach-Object { $_.ToString() }) -join "`n"
    if ($exitCode -ne 0 -and [string]::IsNullOrWhiteSpace($outputText)) {
        throw "audit-web script failed with exit code $exitCode."
    }

    $json = Parse-JsonFromOutputText -OutputText $outputText

    return @{
        ExitCode = $exitCode
        OutputText = $outputText
        Json = $json
    }
}

function Get-PrefixUrlsFromRoutes {
    param(
        [Parameter(Mandatory = $true)][string]$PhpPath,
        [Parameter(Mandatory = $true)][string]$RepoRoot,
        [Parameter(Mandatory = $true)][string]$BaseUrl,
        [Parameter(Mandatory = $true)][string]$PathPrefix
    )

    $normalizedPrefix = $PathPrefix.Trim('/').ToLowerInvariant()
    if ([string]::IsNullOrWhiteSpace($normalizedPrefix)) {
        throw 'PathPrefix must not be empty for matrix mode.'
    }

    Push-Location $RepoRoot
    try {
        $routeJson = & $PhpPath artisan route:list --json 2>&1
        $exitCode = $LASTEXITCODE
    } finally {
        Pop-Location
    }

    if ($exitCode -ne 0) {
        $errorText = ($routeJson | ForEach-Object { $_.ToString() }) -join "`n"
        throw "Failed to read route list via artisan route:list --json.`n$errorText"
    }

    $routeText = ($routeJson | ForEach-Object { $_.ToString() }) -join "`n"
    $parsed = Parse-JsonFromOutputText -OutputText $routeText
    $urls = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)

    foreach ($route in $parsed) {
        if (-not $route.uri) {
            continue
        }

        $uri = [string]$route.uri
        if ($uri.Contains('{')) {
            continue
        }

        $uriPrefix = $uri.Trim('/').ToLowerInvariant()
        if (-not ($uriPrefix -eq $normalizedPrefix -or $uriPrefix.StartsWith("$normalizedPrefix/"))) {
            continue
        }

        $methods = @()
        if ($route.methods) {
            if ($route.methods -is [System.Array]) {
                $methods += @($route.methods)
            } else {
                $methods += ([string]$route.methods -split '\|')
            }
        }
        if ($route.method) {
            $methods += ([string]$route.method -split '\|')
        }

        $methodSet = $methods | ForEach-Object { ([string]$_).Trim().ToUpperInvariant() } | Where-Object { $_ -ne '' }
        if (-not ($methodSet -contains 'GET' -or $methodSet -contains 'HEAD')) {
            continue
        }

        $fullUrl = Join-Url -Root $BaseUrl -Path $uri
        [void]$urls.Add($fullUrl)
    }

    return @($urls)
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$auditRoot = Join-Path $repoRoot 'mcp\audit-web-mcp'
$singleScript = Join-Path $auditRoot 'scripts\run-audit-single.js'
$batchScript = Join-Path $auditRoot 'scripts\run-audit-batch.js'
$interactiveScript = Join-Path $auditRoot 'scripts\run-audit-interactive.js'
$openChromeScript = Join-Path $repoRoot 'scripts\open-audit-chrome.ps1'

if ($DryRun) {
    $nodeProbe = Get-Command node -ErrorAction SilentlyContinue
    $phpProbe = Get-Command php -ErrorAction SilentlyContinue
    $nodePath = if ($nodeProbe) { $nodeProbe.Source } else { 'node' }
    $phpPath = if ($phpProbe) { $phpProbe.Source } else { 'php' }
} else {
    $nodePath = Get-ToolExecutable -CommandName 'node'
    $phpPath = Get-ToolExecutable -CommandName 'php'
}

if (-not $DryRun -and -not (Test-Path (Join-Path $auditRoot 'node_modules'))) {
    throw 'Missing mcp/audit-web-mcp/node_modules. Run: Set-Location mcp/audit-web-mcp; npm install; npx playwright install'
}

$storageStateAbsolute = if ([System.IO.Path]::IsPathRooted($StorageStatePath)) {
    $StorageStatePath
} else {
    Join-Path $repoRoot $StorageStatePath
}

$storageStateDirectory = Split-Path -Parent $storageStateAbsolute
if ($storageStateDirectory -and -not (Test-Path $storageStateDirectory)) {
    New-Item -ItemType Directory -Force -Path $storageStateDirectory | Out-Null
}

$normalizedBaseUrl = $BaseUrl.TrimEnd('/')

Write-Host "Mode           : $Mode"
Write-Host "Base URL       : $normalizedBaseUrl"
Write-Host "Storage state  : $storageStateAbsolute"
Write-Host "Audit MCP root : $auditRoot"
Write-Host "Working root   : $repoRoot"
Write-Host ''

if ($Mode -eq 'bootstrap') {
    $loginUrl = Join-Url -Root $normalizedBaseUrl -Path 'login'
    $bootstrapSlug = "$ReportSlug-auth-bootstrap"

    if ($OpenDebugChrome) {
        if (-not (Test-Path $openChromeScript)) {
            throw "Debug Chrome helper not found: $openChromeScript"
        }

        if ($DryRun) {
            Write-Host "[DryRun] $openChromeScript"
        } else {
            & $openChromeScript
        }
    }

    $args = @(
        $interactiveScript,
        $loginUrl,
        '--persist_report', 'true',
        '--report_slug', $bootstrapSlug,
        '--save_storage_state_path', $StorageStatePath,
        '--waitUntil', 'load',
        '--waitAfterLoadMs', '700'
    )

    if ($DryRun) {
        Write-Host '[DryRun] Working directory:' $repoRoot
        Write-Host '[DryRun] Command:' $nodePath ($args -join ' ')
        exit 0
    }

    Write-Host "Starting interactive login bootstrap at $loginUrl"
    Write-Host 'Login in the opened browser, then press Enter in this terminal when prompted.'
    Write-Host ''

    Push-Location $repoRoot
    try {
        & $nodePath @args
        $exitCode = $LASTEXITCODE
    } finally {
        Pop-Location
    }

    if ($exitCode -ne 0) {
        throw "Interactive bootstrap failed with exit code $exitCode."
    }

    if (Test-Path $storageStateAbsolute) {
        Write-Host ''
        Write-Host "Auth bootstrap complete. Storage state saved at:"
        Write-Host "  $storageStateAbsolute"
        exit 0
    }

    throw "Interactive run finished but storage state file was not created: $storageStateAbsolute"
}

if (-not $DryRun -and -not (Test-Path $storageStateAbsolute)) {
    throw "Storage state not found at $storageStateAbsolute. Run bootstrap mode first."
}

if ($Mode -eq 'single') {
    $targetUrl = Join-Url -Root $normalizedBaseUrl -Path $PosPath
    $payload = @{
        url = $targetUrl
        mode = 'headless'
        persist_report = $true
        report_slug = $ReportSlug
        storage_state_path = $StorageStatePath
        waitUntil = $WaitUntil
        waitAfterLoadMs = $WaitAfterLoadMs
        steps = @(
            @{
                action = 'waitForSelector'
                selector = '#pos_table'
                timeoutMs = 10000
                stopOnFailure = $true
            }
        )
    }

    $run = Invoke-NodeWithJsonPayload -NodePath $nodePath -ScriptPath $singleScript -Payload $payload -WorkingDirectory $repoRoot
    if ($DryRun) {
        exit 0
    }

    $result = $run.Json
    $findings = @($result.findings)
    $errorFindings = @($findings | Where-Object { $_.severity -eq 'error' })
    $selectorFailures = @($errorFindings | Where-Object { $_.message -match '#pos_table' })

    Write-Host "Single smoke status : $($result.auditStatus)"
    Write-Host "Target URL          : $targetUrl"
    Write-Host "Findings            : $($findings.Count) total / $($errorFindings.Count) errors"
    if ($result.reportJsonPath) {
        Write-Host "Report JSON         : $($result.reportJsonPath)"
    }
    if ($result.reportMarkdownPath) {
        Write-Host "Report Markdown     : $($result.reportMarkdownPath)"
    }

    if ($selectorFailures.Count -gt 0) {
        Write-Warning 'POS selector check failed (#pos_table). This usually means auth expired or the session was redirected to /login.'
    }

    if ($run.ExitCode -ne 0) {
        throw "Single smoke command failed with exit code $($run.ExitCode)."
    }

    exit 0
}

if ($Mode -eq 'matrix') {
    if ($DryRun) {
        Write-Host '[DryRun] Matrix mode will resolve GET/HEAD routes from artisan route:list --json'
        Write-Host "[DryRun] Prefix: $PathPrefix"
        Write-Host "[DryRun] Command: $nodePath $batchScript"
        exit 0
    }

    $urls = Get-PrefixUrlsFromRoutes -PhpPath $phpPath -RepoRoot $repoRoot -BaseUrl $normalizedBaseUrl -PathPrefix $PathPrefix
    if ($urls.Count -eq 0) {
        throw "No GET/HEAD routes found for prefix '$PathPrefix' without URI parameters."
    }

    $payload = @{
        urls = $urls
        mode = 'headless'
        persist_report = $true
        report_slug = "$ReportSlug-matrix"
        storage_state_path = $StorageStatePath
        waitUntil = $WaitUntil
        waitAfterLoadMs = $WaitAfterLoadMs
        concurrency = $Concurrency
    }

    $run = Invoke-NodeWithJsonPayload -NodePath $nodePath -ScriptPath $batchScript -Payload $payload -WorkingDirectory $repoRoot
    if ($DryRun) {
        exit 0
    }

    if ($run.ExitCode -ne 0) {
        throw "Matrix smoke command failed with exit code $($run.ExitCode)."
    }

    $results = @($run.Json)
    $failed = @($results | Where-Object { $_.auditStatus -ne 'pass' })
    $passed = $results.Count - $failed.Count

    Write-Host "Matrix smoke status : completed"
    Write-Host "Path prefix         : $PathPrefix"
    Write-Host "URLs audited        : $($results.Count)"
    Write-Host "Passed              : $passed"
    Write-Host "Failed              : $($failed.Count)"

    if ($failed.Count -gt 0) {
        Write-Warning 'One or more prefix routes failed. Review latest report artifacts under output/playwright/audit-web-mcp/reports/.'
    }

    exit 0
}

throw "Unsupported mode: $Mode"
