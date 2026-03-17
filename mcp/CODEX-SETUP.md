# Codex MCP setup (this project)

Use these steps so Codex indexes and reads this codebase faster.

## 1. Codex config

Copy the example config into your Codex config file (create the file if it doesn’t exist):

- **Windows:** `%USERPROFILE%\.codex\config.toml` (e.g. `C:\Users\<You>\.codex\config.toml`)
- **macOS/Linux:** `~/.codex/config.toml`

**Option A — copy the whole example (if you don’t have Codex config yet):**

```powershell
# Windows PowerShell: ensure directory exists, then copy
New-Item -ItemType Directory -Force -Path "$env:USERPROFILE\.codex"
Copy-Item "D:\wamp64\www\projectx\mcp\codex-config.toml.example" "$env:USERPROFILE\.codex\config.toml"
```

**Option B — merge by hand:** Open `mcp/codex-config.toml.example`, copy the `[mcp_servers.*]` blocks into your existing `~/.codex/config.toml`. If your repo path is not `D:/wamp64/www/projectx`, replace it in the copied lines.

Then restart Codex (or reload the extension) so it picks up the MCP servers.

## 2. One-time: install read-file-cache MCP deps

From repo root:

```powershell
cd D:\wamp64\www\projectx\mcp\read-file-cache-mcp
composer install
cd D:\wamp64\www\projectx
```

Or in one line (PowerShell):

```powershell
Set-Location D:\wamp64\www\projectx\mcp\read-file-cache-mcp; composer install; Set-Location D:\wamp64\www\projectx
```

## 3. Before or during Codex: warm the read-file cache

From repo root, run once per session (or after a big pull) so file reads are faster:

```powershell
cd D:\wamp64\www\projectx
php mcp/read-file-cache-mcp/bin/warm-cache
```

Optional: `--max-files=10000` or `--path=app` to limit scope.

## 4. Grep MCP: ripgrep (rg) on PATH

The grep MCP server runs `rg` (ripgrep). Ensure it is on your PATH:

- **Windows:** `winget install BurntSushi.ripgrep.MSVC` or [releases](https://github.com/BurntSushi/ripgrep/releases), then restart the terminal/Codex.
- **macOS:** `brew install ripgrep`

If `rg` is missing, grep MCP will return `RIPGREP_NOT_AVAILABLE`.

## 5. Optional: semantic search (Ollama required)

If you use the semantic_code_search server and have Ollama installed:

```powershell
ollama pull nomic-embed-text
cd D:\wamp64\www\projectx
php mcp/semantic-code-search-mcp/bin/index-codebase
```

Run `index-codebase` again after large codebase changes. Use `--force` to re-index everything.

**When to re-index:** Re-run `index-codebase` after large refactors, new modules, or when semantic results seem stale. Use `--force` for a full re-index when the workspace or embed model has changed.

## 6. Plan files: do not rewrite

Plans in `.cursor/plans/*.plan.md` are **canonical**. When you give Codex a plan file to execute or "plan from," it must not rewrite or replace it. In-repo rules: **AGENTS.md** (intent `execute-plan`) and **.cursor/plans/README.md** §7. If your Codex setup supports project or global instructions, add that `.cursor/plans/*.plan.md` are canonical and must not be rewritten (see .cursor/plans/README.md §7).

---

**Summary**

| Step | Command / action |
|------|-------------------|
| Config | Copy `mcp/codex-config.toml.example` to `~/.codex/config.toml` (or merge), then restart Codex |
| One-time | `cd mcp/read-file-cache-mcp && composer install` |
| Before Codex | `php mcp/read-file-cache-mcp/bin/warm-cache` (from repo root) |
| Grep: ripgrep | Install `rg` (e.g. `winget install BurntSushi.ripgrep.MSVC` on Windows) so grep MCP works |
| Optional semantic | `php mcp/semantic-code-search-mcp/bin/index-codebase` (from repo root; needs Ollama) |
| Plan files | Do not rewrite `.cursor/plans/*.plan.md`; see AGENTS.md and .cursor/plans/README.md §7 |

**Session start checklist**

- Run `php mcp/read-file-cache-mcp/bin/warm-cache` once per session or after a big pull (from repo root).
- If using semantic search: run `index_status` (or the CLI below) to confirm the index is ready; if not, run `php mcp/semantic-code-search-mcp/bin/index-codebase`.
- Optional: agents can call `warm_cache` and `index_status` at session start when the MCP is available.
- Or double-click `warm-and-index.bat` (Windows, repo root) to run warm_cache, index_codebase, and index_status.
