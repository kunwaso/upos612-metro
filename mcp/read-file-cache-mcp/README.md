# Read File Cache MCP Server

Standalone MCP server for safe, cached, line-based text-file reads inside a single workspace.

## Status

- Recommended for workspace text-file reads in Codex and other MCP-aware clients.

## Location

`mcp/read-file-cache-mcp/`

## Requirements

- PHP `^8.1`
- Composer

## Install

```bash
cd mcp/read-file-cache-mcp
composer install
```

## Run

From repo root:

```bash
php mcp/read-file-cache-mcp/bin/server
```

## Tool Contract

### read_file

- Tool name: `read_file`
- Input: `read_file(path, offset?, limit?)`
- `path` is required and must resolve to a readable text file inside the workspace
- `offset` is a 1-based start line and defaults to `1`
- omitting `limit` uses `MCP_READ_FILE_DEFAULT_LIMIT`
- if the requested slice exceeds configured line or byte limits, the result is truncated safely at line boundaries

Successful calls return structured metadata including:

- `path`
- `requested_offset`
- `requested_limit`
- `start_line`
- `end_line`
- `total_lines`
- `eof`
- `truncated`
- `next_offset`
- `cache_hit`

### warm_cache (pre-build persistent disk cache)

- Tool name: `warm_cache`
- Input: `warm_cache(path?, max_files?)`
  - `path`: optional subdirectory (relative to workspace) to warm; omit to warm from workspace root
  - `max_files`: optional max files to warm (default 5000, max 50000)
- Call once (e.g. at session start or after cloning) to fill the persistent disk cache so subsequent `read_file` calls are faster. The cache is stored on disk and reused across server restarts.

Output: `warmed`, `skipped`, `errors`, `paths_scanned`.

**CLI alternative (e.g. for Codex):** From repo root you can pre-warm the cache without the MCP server:

```bash
php mcp/read-file-cache-mcp/bin/warm-cache
php mcp/read-file-cache-mcp/bin/warm-cache --max-files=10000
php mcp/read-file-cache-mcp/bin/warm-cache --path=app
```

## Persistent disk cache

The server uses a **two-tier cache**: in-memory (per process) and **persistent disk** (SQLite).

- **Location:** `MCP_READ_FILE_CACHE_ROOT` (default: `<workspace-root>/.cache/read-file-cache-mcp/`). The file `read-file-cache.sqlite` holds cached file contents.
- **Pre-build:** Call the `warm_cache` tool once to scan allowed workspace files and fill the disk cache. After that, `read_file` will often hit the disk cache (then memory) instead of reading from the filesystem.
- **Agent usage:** To make the agent use the cache faster, run `warm_cache` once per project (or when the codebase changes). The agent can call it at the start of a session.

## Environment Variables

- `MCP_READ_FILE_WORKSPACE_ROOT`
- `MCP_READ_FILE_CACHE_ROOT` — directory for the persistent SQLite cache (default: `<workspace>/.cache/read-file-cache-mcp`)
- `MCP_READ_FILE_DEFAULT_LIMIT`
- `MCP_READ_FILE_MAX_LIMIT`
- `MCP_READ_FILE_MAX_RESPONSE_BYTES`
- `MCP_READ_FILE_MAX_FILE_BYTES`
- `MCP_READ_FILE_MAX_CACHE_FILES` — in-memory cache file limit
- `MCP_READ_FILE_MAX_CACHE_BYTES` — in-memory cache byte limit
- `MCP_READ_FILE_MAX_DISK_CACHE_FILES` — disk cache file limit (default 10000)
- `MCP_READ_FILE_MAX_DISK_CACHE_BYTES` — disk cache byte limit (default 128 MiB)

## Guardrails

- Only files inside the configured workspace root are readable.
- The server blocks:
  - `.git`
  - `vendor`
  - `storage`
  - `node_modules`
  - `.env` and `.env.*`
  - path segments containing `secret` or `password`
  - key or certificate extensions: `pem`, `key`, `p12`, `crt`
- Directories are rejected.
- Binary files are rejected.
- Files larger than `MCP_READ_FILE_MAX_FILE_BYTES` are rejected.

## Error Codes

- `INVALID_ARGUMENT`
- `PATH_NOT_ALLOWED`
- `FILE_NOT_FOUND`
- `NOT_A_FILE`
- `READ_FAILED`
- `BINARY_FILE`
- `FILE_TOO_LARGE`
- `RESPONSE_TOO_LARGE`
- `WORKSPACE_ROOT_INVALID`

## Codex Config

Typical Codex config file:

- `~/.codex/config.toml`

Use placeholder paths in repo docs and replace them locally:

```toml
[mcp_servers.read_file_cache]
command = "php"
args = ["<repo-root>/mcp/read-file-cache-mcp/bin/server"]
env = { MCP_READ_FILE_WORKSPACE_ROOT = "<repo-root>", MCP_READ_FILE_CACHE_ROOT = "<repo-root>/.cache/read-file-cache-mcp" }
```

Short local example:

- If your clone lives at `D:/path/to/rey`, then `<repo-root>` becomes `D:/path/to/rey`.
