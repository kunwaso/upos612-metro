@echo off
cd /d "%~dp0"

echo === Warm read-file cache ===
php mcp/read-file-cache-mcp/bin/warm-cache
echo.

echo === Index codebase (semantic search) ===
php mcp/semantic-code-search-mcp/bin/index-codebase
echo.

echo === Index status ===
php mcp/semantic-code-search-mcp/bin/index-status
echo.

pause
