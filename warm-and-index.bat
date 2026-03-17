@echo off
setlocal
cd /d "%~dp0"

echo === Warm read-file cache ===
php mcp/read-file-cache-mcp/bin/warm-cache
echo.

echo === Semantic index (optional) ===
where ollama >nul 2>nul
if errorlevel 1 (
    echo Ollama was not found on PATH. Skipping semantic index build.
    echo.
) else (
    php mcp/semantic-code-search-mcp/bin/index-codebase
    if errorlevel 1 (
        echo Semantic index build did not complete. The health check below will report the exact status.
    )
    echo.
)

echo === MCP health check ===
php scripts/check-mcp-health.php
echo.

pause
endlocal
