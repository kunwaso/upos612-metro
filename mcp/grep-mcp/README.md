# Grep MCP Server

Standalone MCP server for guarded repo-wide pattern search using `ripgrep`.

## Status

- Recommended as the default exact/pattern search server in MCP-aware clients.

## Location

`mcp/grep-mcp/`

## Requirements

- PHP `^8.1`
- Composer
- `ripgrep` (`rg`) on `PATH`

## Install

```bash
cd mcp/grep-mcp
composer install
```

## Run

From repo root:

```bash
php mcp/grep-mcp/bin/server
```

## Tool Contract

- Tool name: `grep`
- Input: `grep(pattern, path?, include_glob?, exclude_glob?, max_count?, fixed_strings?, ignore_case?, max_depth?, smart_case?)`
- `pattern` is required and may be a regex or a literal depending on `fixed_strings`
- `path` is optional and must resolve to an allowed file or directory inside the workspace
- `include_glob` and `exclude_glob` are optional single ripgrep glob filters
- `max_count` defaults to `MCP_GREP_MAX_COUNT` and is clamped to `500`
- `fixed_strings` defaults to `false`
- `ignore_case` defaults to `false`
- `smart_case` defaults to `false`

Successful calls return structured metadata including:

- `matches`
- `total_count`
- `truncated`

**No codebase index.** This tool runs ripgrep on each call and does not build a semantic index.

## Environment Variables

- `MCP_GREP_WORKSPACE_ROOT`
- `MCP_GREP_MAX_COUNT`
- `MCP_GREP_TIMEOUT_SECONDS`

## Guardrails

- Only files inside the configured workspace root are searchable.
- The server blocks:
  - `.git`
  - `vendor`
  - `storage`
  - `node_modules`
  - `.env` and `.env.*`
  - path segments containing `secret` or `password`
  - key or certificate extensions: `pem`, `key`, `p12`, `crt`
- Every returned match is filtered through the same path guard.

## Error Codes

- `INVALID_ARGUMENT`
- `PATH_NOT_ALLOWED`
- `SEARCH_PATH_NOT_FOUND`
- `RIPGREP_NOT_AVAILABLE`
- `SEARCH_TIMEOUT`
- `SEARCH_FAILED`
- `WORKSPACE_ROOT_INVALID`

## Codex Config

Typical Codex config file:

- `~/.codex/config.toml`

Use placeholder paths in repo docs and replace them locally:

```toml
[mcp_servers.grep]
command = "php"
args = ["<repo-root>/mcp/grep-mcp/bin/server"]
env = { MCP_GREP_WORKSPACE_ROOT = "<repo-root>" }
```

Short local example:

- If your clone lives at `D:/path/to/rey`, then `<repo-root>` becomes `D:/path/to/rey`.
