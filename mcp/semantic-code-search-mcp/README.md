# Semantic Code Search MCP Server

Standalone MCP server for local-only semantic code search in this workspace.

## Status

- Optional. Use it when exact symbols are unknown and a behavior-level search is needed.

## Location

`mcp/semantic-code-search-mcp/`

## Requirements

- PHP `^8.1`
- Composer
- Ollama running locally for production use

## Install

```bash
cd mcp/semantic-code-search-mcp
composer install
ollama pull nomic-embed-text
```

## Run

From repo root:

```bash
php mcp/semantic-code-search-mcp/bin/server
```

Default semantic index location:

```text
<repo-root>/.cache/semantic-code-search-mcp/semantic-code-search.sqlite
```

## Embedding model upgrade (recommended)

The default `nomic-embed-text` model is a good starting point but produces weaker results on nuanced "where is X done?" queries compared to larger models. For noticeably better semantic search quality while keeping all data local, upgrade to `mxbai-embed-large`:

```bash
ollama pull mxbai-embed-large
```

Then update your MCP config and re-index:

```toml
# ~/.codex/config.toml or .cursor/mcp.json
MCP_SEMANTIC_EMBED_MODEL = "mxbai-embed-large"
```

```bash
php mcp/semantic-code-search-mcp/bin/index-codebase --force
```

Trade-off: `mxbai-embed-large` uses ~560 MB VRAM/RAM vs ~270 MB for `nomic-embed-text` and indexing takes longer. Both are fully local; no data leaves your machine.

Model comparison for this use case:

| Model | Quality | RAM | Index speed |
|-------|---------|-----|-------------|
| `nomic-embed-text` | Good | ~270 MB | Faster |
| `mxbai-embed-large` | Better | ~560 MB | Slower |

Switch back at any time by reverting the env var and re-running `--force`.

---

## Building the index (CLI or MCP)

**Index required.** Unlike grep MCP, this server needs a semantic index for meaning-based search.

You can build the index in two ways:

1. **CLI (recommended for first run or after big changes)** — from repo root:
   ```bash
   php mcp/semantic-code-search-mcp/bin/index-codebase
   ```
   Use `--force` to re-index all files even when unchanged:
   ```bash
   php mcp/semantic-code-search-mcp/bin/index-codebase --force
   ```
   Uses the same env as the MCP server (`MCP_SEMANTIC_WORKSPACE_ROOT`, `MCP_SEMANTIC_INDEX_ROOT`, `MCP_SEMANTIC_OLLAMA_HOST`, `MCP_SEMANTIC_EMBED_MODEL`). Requires Ollama with the embed model (e.g. `ollama pull nomic-embed-text`).

2. **MCP tool** — when the server is running in Cursor/Codex, call the `index_codebase` tool (optionally with `force: true`).

## Tool Contract

- `index_codebase(workspace_path?, force?)`
- `search_code(query, limit?, path?)`
- `index_status()`

Use `search_code` for questions such as:

- `Where is MCP tool-choice guidance defined?`
- `How does the AI drawer survive tab changes?`
- `Where is grep MCP documented?`

Use grep MCP for exact strings, selectors, routes, and regex/pattern search.

## Environment Variables

- `MCP_SEMANTIC_WORKSPACE_ROOT`
- `MCP_SEMANTIC_INDEX_ROOT`
- `MCP_SEMANTIC_OLLAMA_HOST`
- `MCP_SEMANTIC_EMBED_MODEL`
- `MCP_SEMANTIC_MAX_FILE_BYTES`
- `MCP_SEMANTIC_CHUNK_LINES`
- `MCP_SEMANTIC_CHUNK_OVERLAP`

## Indexed Scope and Guardrails

Default indexed scope includes:

- `app`
- `Modules`
- `routes`
- `resources`
- `mcp`
- `ai`
- `.cursor`
- `tests`
- `config`
- `src`
- selected root files such as `AGENTS.md`, `composer.json`, and `composer.lock`

Blocked or skipped paths include:

- `.git`
- `vendor`
- `storage`
- `node_modules`
- `.cache`
- `.env` and `.env.*`
- `public/assets`
- `public/modules`
- paths containing `secret` or `password`
- key/certificate extensions: `pem`, `key`, `p12`, `crt`

## Error Codes

- `INVALID_ARGUMENT`
- `PATH_NOT_ALLOWED`
- `SEARCH_PATH_NOT_FOUND`
- `WORKSPACE_ROOT_INVALID`
- `INDEX_ROOT_INVALID`
- `INDEX_NOT_READY`
- `INDEX_STALE`
- `INDEX_FAILED`
- `STATUS_FAILED`
- `READ_FAILED`
- `BINARY_FILE`
- `FILE_TOO_LARGE`
- `EMBEDDING_FAILED`
- `INVALID_RESPONSE`
- `INVALID_VECTOR`
- `SQLITE_NOT_AVAILABLE`

## Codex Config

Typical Codex config file:

- `~/.codex/config.toml`

Use placeholder paths in repo docs and replace them locally:

```toml
[mcp_servers.semantic_code_search]
command = "php"
args = ["<repo-root>/mcp/semantic-code-search-mcp/bin/server"]
env = { MCP_SEMANTIC_WORKSPACE_ROOT = "<repo-root>", MCP_SEMANTIC_INDEX_ROOT = "<repo-root>/.cache/semantic-code-search-mcp", MCP_SEMANTIC_OLLAMA_HOST = "http://127.0.0.1:11434", MCP_SEMANTIC_EMBED_MODEL = "nomic-embed-text" }
```

Short local example:

- If your clone lives at `D:/path/to/rey`, then `<repo-root>` becomes `D:/path/to/rey`.

## Cursor Config

This repo does not check in a default `.cursor/mcp.json`. Add the semantic server locally if you want Cursor agents to use it.

Example project-level config:

```json
{
  "mcpServers": {
    "semantic_code_search": {
      "command": "php",
      "args": ["<repo-root>/mcp/semantic-code-search-mcp/bin/server"],
      "env": {
        "MCP_SEMANTIC_WORKSPACE_ROOT": "<repo-root>",
        "MCP_SEMANTIC_INDEX_ROOT": "<repo-root>/.cache/semantic-code-search-mcp",
        "MCP_SEMANTIC_OLLAMA_HOST": "http://127.0.0.1:11434",
        "MCP_SEMANTIC_EMBED_MODEL": "nomic-embed-text"
      }
    }
  }
}
```

Official Cursor docs: [docs.cursor.com/context/model-context-protocol](https://docs.cursor.com/context/model-context-protocol)
