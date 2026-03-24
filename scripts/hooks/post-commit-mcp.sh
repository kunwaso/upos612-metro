#!/bin/sh

# Managed MCP post-commit hook payload.
# Runs in the background and never blocks commit completion.

REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null)"
[ -z "$REPO_ROOT" ] && exit 0

VERSION="${1:-1.4.8}"
LOG_DIR="$REPO_ROOT/.cache/mcp-hooks"
LOG_FILE="$LOG_DIR/post-commit.log"

mkdir -p "$LOG_DIR"

(
  echo "--- post-commit MCP sync at $(date) ---"

  SEMANTIC_BIN="$REPO_ROOT/mcp/semantic-code-search-mcp/bin/index-codebase"
  SEMANTIC_VENDOR="$REPO_ROOT/mcp/semantic-code-search-mcp/vendor/autoload.php"
  if [ -f "$SEMANTIC_VENDOR" ]; then
    MCP_SEMANTIC_INCLUDE_ROOTS="mcp/README.md" \
    MCP_SEMANTIC_CHUNK_LINES="20" \
    MCP_SEMANTIC_CHUNK_OVERLAP="4" \
    MCP_SEMANTIC_MAX_FILE_BYTES="262144" \
      php "$SEMANTIC_BIN" --force >/dev/null 2>&1 || true
  fi

  if command -v npx >/dev/null 2>&1; then
    npx -y "gitnexus@$VERSION" analyze >/dev/null 2>&1 || {
      npm cache verify >/dev/null 2>&1 || true
      npx -y "gitnexus@$VERSION" analyze >/dev/null 2>&1 || true
    }
  fi
) >> "$LOG_FILE" 2>&1 &

exit 0
