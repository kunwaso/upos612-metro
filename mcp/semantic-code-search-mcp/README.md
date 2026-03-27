# Semantic Code Search MCP Server

Standalone MCP server for fully local semantic code search in this workspace.

## Status

- Optional. Use it when exact symbols are unknown and behavior-level search is needed.
- No external API calls at runtime.

## Location

`mcp/semantic-code-search-mcp/`

## Requirements

- PHP `^8.1`
- Composer
- Python `>=3.10`
- Local Python packages from `scripts/requirements.txt`
- Local Hugging Face model files (default: `BAAI/bge-base-en`)

## Install

```bash
cd mcp/semantic-code-search-mcp
composer install
python -m pip install -r scripts/requirements.txt
```

Download or pre-cache your embedding model locally once (example):

```bash
python -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('BAAI/bge-base-en')"
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

## Embedding Defaults

- Backend: local Hugging Face (`MCP_SEMANTIC_EMBED_BACKEND=huggingface`)
- Model: `BAAI/bge-base-en`
- Device: auto (`cuda` if available, otherwise `cpu`; `mps` on Apple Silicon if available)
- Batch encoding: enabled (`MCP_SEMANTIC_HF_BATCH_SIZE=24`)
- Normalization: enabled (`MCP_SEMANTIC_HF_NORMALIZE=1`)
- BGE query instruction: enabled for query embedding
- Offline/local-only loading: enabled (`MCP_SEMANTIC_HF_LOCAL_FILES_ONLY=1`)

Switch to a smaller model for lower memory:

- `MCP_SEMANTIC_EMBED_MODEL=BAAI/bge-small-en`

## Building the Index (CLI or MCP)

Semantic search requires an index.

1. CLI:

```bash
php mcp/semantic-code-search-mcp/bin/index-codebase
```

Force full re-index:

```bash
php mcp/semantic-code-search-mcp/bin/index-codebase --force
```

2. MCP tool:

- `index_codebase(force?)`

## Validation Script

Quick local validation for indexing + semantic search:

```bash
php mcp/semantic-code-search-mcp/bin/validate-local-search
```

Fast deterministic validation (no Python model load):

```bash
php mcp/semantic-code-search-mcp/bin/validate-local-search --backend=deterministic
```

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
- `MCP_SEMANTIC_EMBED_BACKEND` (`huggingface` or `deterministic`)
- `MCP_SEMANTIC_EMBED_MODEL`
- `MCP_SEMANTIC_PYTHON_BIN` (default `python`)
- `MCP_SEMANTIC_HF_DEVICE` (`auto`, `cuda`, `cpu`, `mps`)
- `MCP_SEMANTIC_HF_BATCH_SIZE`
- `MCP_SEMANTIC_HF_MAX_LENGTH`
- `MCP_SEMANTIC_HF_TIMEOUT_SECONDS`
- `MCP_SEMANTIC_HF_NORMALIZE`
- `MCP_SEMANTIC_HF_LOCAL_FILES_ONLY`
- `MCP_SEMANTIC_HF_QUERY_INSTRUCTION`
- `MCP_SEMANTIC_MAX_FILE_BYTES`
- `MCP_SEMANTIC_CHUNK_LINES`
- `MCP_SEMANTIC_CHUNK_OVERLAP`
- `MCP_SEMANTIC_INCLUDE_ROOTS` (optional CSV override for indexed top-level roots)
- `MCP_SEMANTIC_INCLUDE_ROOT_FILES` (optional CSV override for indexed root files)

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

You can override indexed scope with:

- `MCP_SEMANTIC_INCLUDE_ROOTS` (for example `app,Modules,routes,resources/views,mcp,ai,tests,config`)
- `MCP_SEMANTIC_INCLUDE_ROOT_FILES` (for example `AGENTS.md,composer.json,README.md`)

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
env = { MCP_SEMANTIC_WORKSPACE_ROOT = "<repo-root>", MCP_SEMANTIC_INDEX_ROOT = "<repo-root>/.cache/semantic-code-search-mcp", MCP_SEMANTIC_EMBED_BACKEND = "huggingface", MCP_SEMANTIC_EMBED_MODEL = "BAAI/bge-base-en", MCP_SEMANTIC_HF_LOCAL_FILES_ONLY = "1" }
```

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
        "MCP_SEMANTIC_EMBED_BACKEND": "huggingface",
        "MCP_SEMANTIC_EMBED_MODEL": "BAAI/bge-base-en",
        "MCP_SEMANTIC_HF_LOCAL_FILES_ONLY": "1"
      }
    }
  }
}
```

Official Cursor docs: [docs.cursor.com/context/model-context-protocol](https://docs.cursor.com/context/model-context-protocol)

