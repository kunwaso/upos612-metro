#!/bin/sh

# Managed MCP post-merge hook payload (optional legacy).
# Default install uses pre-push-mcp.sh only — see scripts/manage-mcp-hooks.ps1 install.
# Runs in the background and refreshes fast local MCP indexes.

REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null)"
[ -z "$REPO_ROOT" ] && exit 0

VERSION="${1:-1.4.8}"
LOG_DIR="$REPO_ROOT/.cache/mcp-hooks"
LOG_FILE="$LOG_DIR/post-merge.log"

mkdir -p "$LOG_DIR"

(
  echo "--- post-merge MCP sync at $(date) ---"

  READ_WARM_BIN="$REPO_ROOT/mcp/read-file-cache-mcp/bin/warm-cache"
  READ_VENDOR="$REPO_ROOT/mcp/read-file-cache-mcp/vendor/autoload.php"
  if [ -f "$READ_VENDOR" ]; then
    MCP_READ_FILE_WORKSPACE_ROOT="$REPO_ROOT" \
    MCP_READ_FILE_CACHE_ROOT="$REPO_ROOT/.cache/read-file-cache-mcp" \
      php "$READ_WARM_BIN" --path=app --max-files=5000 >/dev/null 2>&1 || true
    if [ -d "$REPO_ROOT/Modules" ]; then
      MCP_READ_FILE_WORKSPACE_ROOT="$REPO_ROOT" \
      MCP_READ_FILE_CACHE_ROOT="$REPO_ROOT/.cache/read-file-cache-mcp" \
        php "$READ_WARM_BIN" --path=Modules --max-files=5000 >/dev/null 2>&1 || true
    fi
  fi

  SEMANTIC_BIN="$REPO_ROOT/mcp/semantic-code-search-mcp/bin/index-codebase"
  SEMANTIC_VENDOR="$REPO_ROOT/mcp/semantic-code-search-mcp/vendor/autoload.php"
  if [ "${UPOS612_MCP_SKIP_SEMANTIC_REINDEX:-0}" = "1" ]; then
    echo "semantic: skipped by UPOS612_MCP_SKIP_SEMANTIC_REINDEX"
  elif [ -f "$SEMANTIC_VENDOR" ]; then
    PYTHON_BIN=""
    if command -v python >/dev/null 2>&1; then
      PYTHON_BIN="$(python -c 'import sys; print(sys.executable)' 2>/dev/null | tr -d '\r')"
      [ -z "$PYTHON_BIN" ] && PYTHON_BIN="$(command -v python)"
    fi
    echo "semantic: indexing with BAAI/bge-small-en"
    MCP_SEMANTIC_EMBED_MODEL="BAAI/bge-small-en" \
    MCP_SEMANTIC_HF_DEVICE="cpu" \
    MCP_SEMANTIC_HF_BATCH_SIZE="12" \
    MCP_SEMANTIC_HF_LOCAL_FILES_ONLY="1" \
    MCP_SEMANTIC_HF_TIMEOUT_SECONDS="180" \
    MCP_SEMANTIC_INCLUDE_ROOTS="app,Modules,routes,resources/views,config,ai,mcp,.cursor" \
    MCP_SEMANTIC_INCLUDE_ROOT_FILES="AGENTS.md,AGENTS-FAST.md,composer.json,composer.lock,README.md,modules_statuses.json" \
    MCP_SEMANTIC_CHUNK_LINES="80" \
    MCP_SEMANTIC_CHUNK_OVERLAP="8" \
    MCP_SEMANTIC_MAX_FILE_BYTES="524288" \
    MCP_SEMANTIC_PYTHON_BIN="$PYTHON_BIN" \
      php "$SEMANTIC_BIN" >/dev/null 2>&1 || true
  else
    echo "semantic: skipped, vendor missing"
  fi

  if [ "${UPOS612_MCP_SKIP_GITNEXUS_ANALYZE:-0}" = "1" ]; then
    echo "gitnexus: skipped by UPOS612_MCP_SKIP_GITNEXUS_ANALYZE"
  elif [ -f "$REPO_ROOT/.gitnexus/meta.json" ]; then
    echo "gitnexus: analyzing upos612"
    (
      cd "$REPO_ROOT" || exit 0
      if command -v gitnexus >/dev/null 2>&1; then
        gitnexus analyze >/dev/null 2>&1 || true
      elif command -v npx >/dev/null 2>&1; then
        npx -y "gitnexus@$VERSION" analyze >/dev/null 2>&1 || true
      fi
    )
  elif [ ! -f "$REPO_ROOT/.gitnexus/meta.json" ]; then
    echo "gitnexus: skipped, repo is not indexed yet"
  else
    echo "gitnexus: skipped, gitnexus/npx unavailable"
  fi
) >> "$LOG_FILE" 2>&1 &

exit 0
