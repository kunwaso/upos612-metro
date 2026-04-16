@echo off
setlocal enabledelayedexpansion

REM warm-and-index.bat — Windows convenience wrapper for scripts/warm-cache.ps1
REM Usage:
REM   warm-and-index.bat                          (startup profile, pause at end)
REM   warm-and-index.bat --profile startup         (explicit profile)
REM   warm-and-index.bat --profile nightly-embeddings
REM   warm-and-index.bat --skip-gitnexus --no-pause
REM   warm-and-index.bat --dry-run

set "PROFILE=startup"
set "EXTRA_FLAGS="
set "PAUSE_AT_END=1"

:parse_args
if "%~1"=="" goto run
if /I "%~1"=="--profile" (
    set "PROFILE=%~2"
    shift
    shift
    goto parse_args
)
if /I "%~1"=="--skip-semantic" (
    set "EXTRA_FLAGS=!EXTRA_FLAGS! -SkipSemantic"
    shift
    goto parse_args
)
if /I "%~1"=="--skip-gitnexus" (
    set "EXTRA_FLAGS=!EXTRA_FLAGS! -SkipGitNexus"
    shift
    goto parse_args
)
if /I "%~1"=="--dry-run" (
    set "EXTRA_FLAGS=!EXTRA_FLAGS! -DryRun"
    shift
    goto parse_args
)
if /I "%~1"=="--no-pause" (
    set "PAUSE_AT_END=0"
    shift
    goto parse_args
)
if /I "%~1"=="--require-gitnexus" (
    set "EXTRA_FLAGS=!EXTRA_FLAGS! -RequireGitNexusReady"
    shift
    goto parse_args
)
if /I "%~1"=="--require-semantic" (
    set "EXTRA_FLAGS=!EXTRA_FLAGS! -RequireSemanticReady"
    shift
    goto parse_args
)
echo Unknown flag: %~1
shift
goto parse_args

:run
echo.
echo === UPOS612 Warm and Index ===
echo Profile: %PROFILE%
echo Flags:   %EXTRA_FLAGS%
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\warm-cache.ps1 -Profile %PROFILE% %EXTRA_FLAGS%

echo.
echo === Health Check ===
php scripts\check-mcp-health.php

if "%PAUSE_AT_END%"=="1" (
    echo.
    pause
)

endlocal
