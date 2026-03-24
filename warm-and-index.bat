@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "PROFILE=startup"
set "WARM_PATH=app"
set "WARM_MAX_FILES=5000"
set "DRY_RUN=0"
set "SKIP_SEMANTIC=0"
set "SKIP_GITNEXUS=0"
set "REGISTER=0"
set "UNREGISTER=0"
set "NO_PAUSE=0"

:parse_args
if "%~1"=="" goto args_done
if /i "%~1"=="--profile" (
    if "%~2"=="" goto usage
    set "PROFILE=%~2"
    shift
    shift
    goto parse_args
)
if /i "%~1"=="--nightly-embeddings" (
    set "PROFILE=nightly-embeddings"
    shift
    goto parse_args
)
if /i "%~1"=="--all" (
    set "WARM_PATH="
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
if /i "%~1"=="--dry-run" (
    set "DRY_RUN=1"
    shift
    goto parse_args
)
if /i "%~1"=="--skip-semantic" (
    set "SKIP_SEMANTIC=1"
    shift
    goto parse_args
)
if /i "%~1"=="--skip-gitnexus" (
    set "SKIP_GITNEXUS=1"
    shift
    goto parse_args
)
if /i "%~1"=="--register" (
    set "REGISTER=1"
    shift
    goto parse_args
)
if /i "%~1"=="--unregister" (
    set "UNREGISTER=1"
    shift
    goto parse_args
)
if /i "%~1"=="--no-pause" (
    set "NO_PAUSE=1"
    shift
    goto parse_args
)
echo Unknown argument: %~1
goto usage

:args_done
if "%REGISTER%"=="1" if "%UNREGISTER%"=="1" (
    echo [ERROR] --register and --unregister cannot be used together.
    goto usage
)

echo === MCP warm/index orchestration ===
if "%REGISTER%"=="1" (
    echo Mode    : register scheduled tasks
) else if "%UNREGISTER%"=="1" (
    echo Mode    : unregister scheduled tasks
) else (
    echo Profile : !PROFILE!
)
if defined WARM_PATH (
    echo Warm path: !WARM_PATH!
) else (
    echo Warm path: [root]
)
echo Max files: !WARM_MAX_FILES!
if "%SKIP_SEMANTIC%"=="1" echo Semantic : skipped
if "%SKIP_GITNEXUS%"=="1" echo GitNexus : skipped
echo.

if "%REGISTER%"=="1" (
    powershell -NoProfile -ExecutionPolicy Bypass -File ".\scripts\warm-cache.ps1" -Register
) else if "%UNREGISTER%"=="1" (
    powershell -NoProfile -ExecutionPolicy Bypass -File ".\scripts\warm-cache.ps1" -Unregister
) else (
    set "ARG_WARM="
    if defined WARM_PATH set "ARG_WARM=-WarmPath !WARM_PATH!"
    set "ARG_DRY="
    if "%DRY_RUN%"=="1" set "ARG_DRY=-DryRun"
    set "ARG_SKIP_SEMANTIC="
    if "%SKIP_SEMANTIC%"=="1" set "ARG_SKIP_SEMANTIC=-SkipSemantic"
    set "ARG_SKIP_GITNEXUS="
    if "%SKIP_GITNEXUS%"=="1" set "ARG_SKIP_GITNEXUS=-SkipGitNexus"

    powershell -NoProfile -ExecutionPolicy Bypass -File ".\scripts\warm-cache.ps1" -Profile !PROFILE! -MaxFiles !WARM_MAX_FILES! !ARG_WARM! !ARG_DRY! !ARG_SKIP_SEMANTIC! !ARG_SKIP_GITNEXUS!
)
if errorlevel 1 (
    echo [WARN] warm-cache script reported failures. Review the generated log under .cache\mcp-automation.
)
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
        echo [WARN] Codex config uses gitnexus@latest. Prefer a pinned version.
    )
)
echo.

if "%NO_PAUSE%"=="0" pause
endlocal
goto :eof

:usage
echo Usage:
echo   warm-and-index.bat [--profile startup^|nightly-embeddings] [--all] [--path ^<dir^>] [--max-files ^<n^>] [--dry-run] [--skip-semantic] [--skip-gitnexus] [--no-pause]
echo   warm-and-index.bat [--register ^| --unregister]
echo.
echo Defaults:
echo   --profile startup --path app --max-files 5000
echo.
pause
endlocal
exit /b 1
