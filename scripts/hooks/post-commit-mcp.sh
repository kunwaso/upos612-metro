#!/bin/sh

# Managed MCP post-commit hook payload.
# Runs in the background and never blocks commit completion.

REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null)"
[ -z "$REPO_ROOT" ] && exit 0

VERSION="${1:-1.4.8}"
LOG_DIR="$REPO_ROOT/.cache/mcp-hooks"
LOG_FILE="$LOG_DIR/post-commit.log"

mkdir -p "$LOG_DIR"

should_semantic_reindex() {
  case "$1" in
    app/*|Modules/*|routes/*|resources/views/*|config/*|ai/*|mcp/*|.cursor/*|AGENTS.md|AGENTS-FAST.md|README.md|composer.json|composer.lock|modules_statuses.json)
      return 0
      ;;
    *)
      return 1
      ;;
  esac
}

should_gitnexus_analyze() {
  case "$1" in
    app/*|Modules/*|routes/*|resources/*|config/*|database/*|tests/*|composer.json|composer.lock)
      return 0
      ;;
    *)
      return 1
      ;;
  esac
}

CHANGED_FILES="$(git diff-tree --no-commit-id --name-only -r HEAD 2>/dev/null || true)"
RUN_SEMANTIC=0
RUN_GITNEXUS=0

IFS='
'
for path in $CHANGED_FILES; do
  [ -z "$path" ] && continue
  if should_semantic_reindex "$path"; then
    RUN_SEMANTIC=1
  fi
  if should_gitnexus_analyze "$path"; then
    RUN_GITNEXUS=1
  fi
done
unset IFS

(
  echo "--- post-commit MCP sync at $(date) ---"
  echo "changed_files: $(printf '%s\n' "$CHANGED_FILES" | sed '/^$/d' | wc -l | tr -d ' ')"

  SEMANTIC_BIN="$REPO_ROOT/mcp/semantic-code-search-mcp/bin/index-codebase"
  SEMANTIC_VENDOR="$REPO_ROOT/mcp/semantic-code-search-mcp/vendor/autoload.php"
  if [ "${UPOS612_MCP_SKIP_SEMANTIC_REINDEX:-0}" = "1" ]; then
    echo "semantic: skipped by UPOS612_MCP_SKIP_SEMANTIC_REINDEX"
  elif [ "$RUN_SEMANTIC" -eq 1 ] && [ -f "$SEMANTIC_VENDOR" ]; then
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
  elif [ "$RUN_SEMANTIC" -eq 0 ]; then
    echo "semantic: skipped, no indexed-scope files changed"
  else
    echo "semantic: skipped, vendor missing"
  fi

  if [ "${UPOS612_MCP_SKIP_GITNEXUS_ANALYZE:-0}" = "1" ]; then
    echo "gitnexus: skipped by UPOS612_MCP_SKIP_GITNEXUS_ANALYZE"
  elif [ "$RUN_GITNEXUS" -eq 1 ] && [ -f "$REPO_ROOT/.gitnexus/meta.json" ] && command -v npx >/dev/null 2>&1; then
    echo "gitnexus: analyzing upos612"
    (
      cd "$REPO_ROOT" || exit 0
      npx -y "gitnexus@$VERSION" analyze >/dev/null 2>&1 || {
        npm cache verify >/dev/null 2>&1 || true
        npx -y "gitnexus@$VERSION" analyze >/dev/null 2>&1 || true
      }
    )
  elif [ "$RUN_GITNEXUS" -eq 0 ]; then
    echo "gitnexus: skipped, no graph-relevant files changed"
  elif [ ! -f "$REPO_ROOT/.gitnexus/meta.json" ]; then
    echo "gitnexus: skipped, repo is not indexed yet"
  else
    echo "gitnexus: skipped, npx unavailable"
  fi
) >> "$LOG_FILE" 2>&1 &

exit 0
