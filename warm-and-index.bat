@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "WARM_PATH=app"
set "WARM_MAX_FILES=5000"
set "RUN_SEMANTIC=0"

:parse_args
if "%~1"=="" goto args_done
if /i "%~1"=="--semantic" (
    set "RUN_SEMANTIC=1"
    shift
    goto parse_args
)
if /i "%~1"=="--all" (
    set "WARM_PATH="
    set "WARM_MAX_FILES=5000"
    shift
    goto parse_args
)
if /i "%~1"=="--path" (
    if "%~2"=="" goto usage
    set "WARM_PATH=%~2"
    shift
    shift
    goto parse_args
)
if /i "%~1"=="--max-files" (
    if "%~2"=="" goto usage
    set "WARM_MAX_FILES=%~2"
    shift
    shift
    goto parse_args
)
echo Unknown argument: %~1
goto usage

:args_done
echo === Warm read-file cache ===
if defined WARM_PATH (
    echo Path: %WARM_PATH%   Max files: %WARM_MAX_FILES%
    php mcp/read-file-cache-mcp/bin/warm-cache --path=%WARM_PATH% --max-files=%WARM_MAX_FILES%
) else (
    echo Path: [root]   Max files: %WARM_MAX_FILES%
    php mcp/read-file-cache-mcp/bin/warm-cache --max-files=%WARM_MAX_FILES%
)
if errorlevel 1 (
    echo [WARN] warm-cache failed. Running health check below for details.
)
echo.

echo === Semantic index (optional) ===
if "%RUN_SEMANTIC%"=="0" (
    echo Skipped by default. Use --semantic to build semantic index.
    echo.
    goto health_check
)

where ollama >nul 2>nul
if errorlevel 1 (
    echo Ollama was not found on PATH. Skipping semantic index build.
    echo.
    goto health_check
)

where curl >nul 2>nul
if not errorlevel 1 (
    curl --silent --max-time 2 http://127.0.0.1:11434/api/tags >nul 2>nul
    if errorlevel 1 (
        echo Ollama is not reachable at http://127.0.0.1:11434. Skipping semantic index build.
        echo.
        goto health_check
    )
)

php mcp/semantic-code-search-mcp/bin/index-codebase
if errorlevel 1 (
    echo [WARN] Semantic index build did not complete. The health check below will report the exact status.
)
echo.

:health_check
echo === MCP health check ===
php scripts/check-mcp-health.php
echo.

echo === GitNexus quick check ===
if exist ".gitnexus\meta.json" (
    echo .gitnexus\meta.json found.
) else (
    echo [WARN] .gitnexus\meta.json not found. Run: npx gitnexus analyze
)
set "CODEX_CFG=%USERPROFILE%\.codex\config.toml"
if exist "%CODEX_CFG%" (
    findstr /i /c:"gitnexus@latest" "%CODEX_CFG%" >nul
    if not errorlevel 1 (
        echo [WARN] Codex config uses gitnexus@latest ^(can cause MCP handshake timeouts^).
        echo        Prefer a pinned version or a local/global gitnexus binary.
    )
)
echo.

pause
endlocal
goto :eof

:usage
echo Usage:
echo   warm-and-index.bat [--semantic] [--all] [--path ^<dir^>] [--max-files ^<n^>]
echo.
echo Defaults:
echo   --path app --max-files 500
echo.
pause
endlocal
exit /b 1
