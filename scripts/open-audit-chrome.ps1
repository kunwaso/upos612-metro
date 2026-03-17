param(
    [int]$Port = 9222
)

$ErrorActionPreference = 'Stop'

function Find-ChromeExecutable {
    $candidates = @(
        $env:CHROME_PATH,
        (Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe'),
        (Join-Path ${env:ProgramFiles(x86)} 'Google\Chrome\Application\chrome.exe'),
        (Join-Path $env:LOCALAPPDATA 'Google\Chrome\Application\chrome.exe'),
        (Join-Path $env:ProgramFiles 'Google\Chrome for Testing\Application\chrome.exe'),
        (Join-Path ${env:ProgramFiles(x86)} 'Google\Chrome for Testing\Application\chrome.exe')
    ) | Where-Object { $_ -and (Test-Path $_) }

    if ($candidates.Count -gt 0) {
        return $candidates[0]
    }

    throw 'Chrome executable not found. Set CHROME_PATH or install Chrome.'
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$profileRoot = Join-Path $repoRoot '.cache\chrome-devtools-mcp\remote-debug-profile'
New-Item -ItemType Directory -Force -Path $profileRoot | Out-Null

$chrome = Find-ChromeExecutable
$arguments = @(
    "--remote-debugging-port=$Port",
    "--user-data-dir=$profileRoot",
    '--no-first-run',
    '--no-default-browser-check',
    '--disable-background-networking',
    'about:blank'
)

Start-Process -FilePath $chrome -ArgumentList $arguments | Out-Null
Write-Host "Chrome debug session started on http://127.0.0.1:$Port"
Write-Host "Profile dir: $profileRoot"
