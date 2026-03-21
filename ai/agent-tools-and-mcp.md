# Agent Tools and MCP

This document explains which tool families an agent may have, when to use them, and how to use the in-repo MCP servers without baking machine-local assumptions into long-lived docs.

Tool names vary by platform. Treat the names below as tool families with common examples, not as a promise that every environment exposes the same command names.

---

## 0. Compatibility and Startup Check

### 0.1 PHP compatibility

- The **application** supports `php ^8.0` (`composer.json` at repo root).
- The MCP servers shipped in `mcp/` require **PHP `^8.1` or higher**. If MCP startup fails on a machine that can still run the app, check the PHP version used to launch the MCP server.

### 0.2 MCP server priority in this repo

| Server | Status | Use for |
|---|---|---|
| `laravel_mysql` | On-demand | Repo-aware routes, schema, migrations, tests, project map, and guarded repo introspection. Use only for repo-specific tasks (implement, analyze, audit, log-scan, route/schema/migration work). Skip for conceptual questions, tool comparisons, or general advice. |
| `audit_web` | Recommended for browser audits | Interactive or headless Playwright URL audits with persisted JSON/Markdown findings, screenshots, traces, and triage metadata |
| `grep` | Recommended | Exact string, selector, route, ID, translation key, and regex search |
| `read_file_cache` | Recommended | Fast cached line-based reads for workspace text files |
| `semantic_code_search` | Optional | Meaning-based discovery when exact symbols are unknown; requires indexing and Ollama |
| `chrome-devtools` | Optional adjunct | Live Chrome DevTools inspection for timing-sensitive, silent, performance, or ambiguous browser issues |

No single MCP server is strictly required for every task. If one is unavailable or degraded, fall back in a consistent order instead of guessing.

### 0.3 Startup capability check

At the start of a Codex or Cursor session, **first classify the task** using the intent router (§0.1b in AGENTS.md), then follow the appropriate capability tier:

**Always check (every task):**

1. Check `grep` for exact or regex search.
2. Check `read_file_cache` for workspace file reads.
3. If unavailable, fall back to the host platform's built-in grep, codebase search, and file read tools.

**On-demand (repo-specific tasks only — implement, analyze, investigate, execute-plan, tenant-audit, log-scan, lint-fix):**

4. Check `laravel_mysql` for repo-aware resources/tools such as `project_map`, routes, schema, and tests. **Skip this step entirely** for conceptual, comparison, explain, dependency-eval (generic), or external tool evaluation questions.
5. If the task is a browser audit, check `audit_web` and then `playwright`; keep `chrome-devtools` available for escalation.
6. Check `semantic_code_search` if meaning-based discovery is needed and the server is available.

This keeps Codex and Cursor behavior aligned even when their active MCP surfaces differ.

Default startup policy in this repo:

- **Always available (every task):** `grep`, `read_file_cache`
- **On-demand (repo-specific tasks only):** `laravel_mysql` — invoke `project_map`, schema, routes, or migrations only when the task requires live repo structure (implement, analyze, audit, log-scan, route/schema/migration work, symbol-impact analysis). Do **not** run `project_map` or `warm_cache` for conceptual questions, tool comparisons, external tool evaluations, or general advice.
- **Optional:** `semantic_code_search`

Repo-local startup check:

```bash
php scripts/check-mcp-health.php
```

The health check uses a **path-scoped** grep probe, confirms `read_file_cache` returns actual file text, and reports semantic readiness as `READY`, `NOT_INDEXED`, `STALE`, or `OLLAMA_UNAVAILABLE`.

Availability is not enough; do a quick health check for each server you depend on:

1. `laravel_mysql`: run a lightweight call such as `project_map`.
2. `audit_web`: run one lightweight public URL audit in headless mode and confirm structured JSON is returned.
3. `grep`: run a tiny exact search in a known file.
4. `read_file_cache`: run one small `read_file` and confirm file text is returned.
5. `semantic_code_search` (optional): run `index_status` before `search_code`.

Treat a tool as degraded if it repeatedly times out, returns empty/partial payloads, loops on stale index errors, or returns metadata without the expected content body.

### 0.3a Deep/external task startup macro

For **deep research**, **GitHub/trending intake**, or **external adaptation** tasks, use this startup order once the required MCPs are available.

**First, decide whether the task is repo-specific or generic:**

- **Generic** (e.g. "evaluate a tool", "compare two libraries", "explain a concept", "should I use X?"): skip steps 1–3; answer from reasoning + web sources + ≤2 targeted reads if needed. State in the reply if you skipped repo grounding and offer to add it on request.
- **Repo-specific** (e.g. "can we adopt X in UPOS?", "map landing files for this pattern", "does this conflict with our dependencies?"): proceed with all steps below.

1. Call `warm_cache` if `read_file_cache` is available and the session is cold.
2. Read `resource://project/map` (or use `project_map`) to confirm the live checkout shape before naming landing files or modules.
3. Read `resource://composer` to understand the current dependency surface before suggesting new packages.
4. Call `index_status` only if the task actually needs behavior-level discovery beyond exact search.
5. Only then branch into web fetch/search for upstream README, license, manifests, examples, and release notes.

This keeps external evaluation grounded in local repo truth instead of drifting toward generic advice.

### 0.4 Which MCPs use a disk cache or build step?

**semantic_code_search** and **read_file_cache** both support a disk-backed cache and a build/pre-warm step:

| Server | Disk cache / build step? | Why |
|--------|---------------------------|-----|
| **semantic_code_search** | Yes. Run `index_codebase` (MCP tool) or `php mcp/semantic-code-search-mcp/bin/index-codebase` (CLI); index is stored at `<repo-root>/.cache/semantic-code-search-mcp/`. | Semantic search needs an embedded index; it is built once (or when stale) and reused. |
| **grep** | No. No codebase index; runs ripgrep on each call. | By design: exact/pattern search is done live against the filesystem. See `mcp/grep-mcp/README.md`. |
| **read_file_cache** | **Yes.** Persistent disk cache at `MCP_READ_FILE_CACHE_ROOT`. Pre-build by calling the **`warm_cache`** tool once or run `php mcp/read-file-cache-mcp/bin/warm-cache` from repo root. | Two-tier: in-memory + SQLite on disk. Pre-warm so `read_file` is faster. See `mcp/read-file-cache-mcp/README.md` and §4.3 (Codex). |

**grep-mcp** never creates a cache. For **read_file_cache-mcp**, call **`warm_cache`** to pre-build the disk cache so `.cache/read-file-cache-mcp/` exists and `read_file` is faster.

---

## 1. Tool Inventory by Category

### 1.1 Search and discovery

| Tool family | Common names you may see | When to use |
|---|---|---|
| Semantic search | `codebase_search`, semantic search, `search_code` | Find code by meaning when you know the behavior but not the exact symbol. Prefer `search_code` when the semantic MCP is available. |
| Grep | `grep`, text search, instant Grep | Find exact strings, symbols, selectors, routes, translations, IDs, and regex matches. |
| Glob / file find | `glob_file_search`, file find | Find files by pattern such as `**/*.php` or `**/*Controller.php`. |

### 1.2 Read, edit, and write

| Tool family | Common names you may see | When to use |
|---|---|---|
| Read file | `read_file`, file read | Open a file for full context or a line slice. Prefer line slices over shell-based slicing. |
| Search-replace | `search_replace` | Make targeted edits when the change is narrow and exact. |
| Write | `write` | Create a new file or replace a file wholesale when that is cleaner than many small edits. |
| Delete file | `delete_file` | Remove a file only when the task explicitly requires deletion. |

### 1.3 Run, verify, and plan

| Tool family | Common names you may see | When to use |
|---|---|---|
| Run terminal command | `run_terminal_cmd`, terminal, shell | Run tests, artisan, composer, npm, git, or other verification commands. |
| Read lints / diagnostics | `read_lints`, diagnostics | Check syntax, lint, or IDE diagnostics after edits. |
| Todo list | `todo_write`, task list | Track multi-step work so steps are not dropped. |
| Plan | `CreatePlan`, `plan`, planner | Write the execution plan before non-trivial work. |

### 1.4 External and delegated work

| Tool family | Common names you may see | When to use |
|---|---|---|
| Web search | `web_search` | Look up docs, versions, errors, or current product behavior when the repo is not enough. |
| Fetch URL | `fetch`, `mcp_web_fetch` | Pull stable docs or API references from a known URL. |
| Browser audit MCP | `audit_web` | Run interactive or headless web audits that persist findings, screenshots, traces, and triage output. |
| Chrome DevTools MCP | `chrome-devtools` | Attach to a live Chrome debug session for silent failures, timing issues, network/performance inspection, and ambiguous audit findings. |
| Explore subagent | `mcp_task` with `explore` | Broad or parallel codebase discovery. |
| Shell subagent | `mcp_task` with `shell` | Command-heavy work such as git, artisan, composer, npm, or tests. |
| General-purpose subagent | `mcp_task` with `generalPurpose` | Multi-step research or bounded reasoning work. |

---

## 2. Fast Choice Rules

### 2.1 Default search/read split

- Use **grep** for exact strings, selectors, translation keys, route names, IDs, and regex matches.
- Use **semantic search** for “where is X done?” or “how does this flow work?” when the exact symbol is unknown.
- Use **grep or semantic search first**, then read only the narrowed files or line ranges.

### 2.2 File reading

- When `read_file_cache` is enabled and healthy, use it for workspace text-file reads.
- Prefer `offset` + `limit` slices when you only need a line range.
- For **large files** (e.g. >200 lines), use grep to find relevant sections and line numbers first, then read only those ranges with `offset` + `limit` instead of the whole file.
- If `read_file_cache` is unavailable or degraded, use the next safest repo-aware read tool in the environment.
- Do **not** use shell reads such as `Get-Content`, `cat`, or `rg` output as a substitute for file reading when a healthy read-file tool is available.
- **Stale-write risk:** The disk cache reflects the filesystem at warm time. If you (or the agent) just edited a file in the current session, re-read it via `read_file` with a forced disk read or use the host platform's native read tool to avoid seeing the pre-edit cached content. Do not re-read your own writes through a stale cache entry.

### 2.3 Exact search

- When `grep` MCP is enabled, use it as the default exact/pattern search tool in Codex.
- In Cursor Chat, the built-in Grep/instant Grep is acceptable when MCP grep is not available.
- Do **not** run `rg` or `grep` in the shell for repository search unless no repo-aware search tool exists in the current environment.

### 2.4 Repo-aware MCP preference

When `laravel_mysql` is enabled, prefer it over ad hoc shell exploration for:

- project structure (`project_map`)
- route discovery (`routes_list`, `route_details`)
- schema and migrations (`schema_snapshot`, `migrations_status`, `migration_show`)
- test runs (`run_tests`)
- guarded repository status (`git_status`)

### 2.5 `find_symbols` use

Use `find_symbols` only when you need structured symbol filtering such as `type=class` or `type=method`.

Do **not** use repeated `find_symbols` calls for simple “where is string X?” work. Use grep or semantic search first, then read the file once you know the path.

### 2.6 Fallback rule (unavailable or degraded)

If the preferred MCP server is unavailable or degraded:

1. Use the next fastest repo-aware tool in the current environment.
2. Keep the same behavior split: grep for exact, semantic/codebase for meaning, read-file for content.
3. Switch quickly; do not keep retrying a failing tool in a loop.
4. State the fallback briefly in your response when it materially affects confidence or speed.

### 2.7 Minimize edit/write round-trips (faster execution)

Total time scales with **number of tool calls** (each call adds round-trip + MCP overhead). To improve agent logic and speed:

- **Preferred:** One **Write** per file when creating or doing large changes to a file; use **Search-replace** only when the change is narrow and localized.
- **Preferred:** For multi-file tasks, **plan the full file list and directory layout first** (from the plan or a quick glob/grep). Create any missing directories at the start, then write or edit files; avoid discovering “directory missing” mid-task.
- **Avoid:** Many small Edit File calls when one Write per file would suffice; avoid starting edits before the target file list and required directories are known.
- **Plans:** Include a “Directories to create (if missing)” and a file list in the plan so the agent does not infer layout mid-run.

See `ai/agent-improvement.md` §2.6 for the full “minimize tool calls” guidance.

### 2.8 Faster analysis and scan workflow

For tasks like **auditing a module**, **cloning/porting code**, or **understanding a codebase** (e.g. “implement Aichat clone end-to-end”):

1. **Grep (or glob) first** — List files in the area (e.g. `Modules/Aichat`), then grep for patterns (e.g. `projectx|fabric|business_id`) to get **file paths and line numbers** of what needs change or review. Do not read every file in full yet.
2. **Targeted reads** — Read only the files or **line ranges** that grep (or semantic search) flagged. Use `offset` + `limit` for large files. Read 3–5 independent files **in parallel** when they don’t depend on each other.
3. **Full-file read only when needed** — Use a full-file read when you are about to edit that file or need to understand the whole unit (e.g. one controller). For “does this file reference X?” use grep; for “what does this method do?” read the method’s range or the file.
4. **No shell reads** — Use the platform read tool (or `read_file_cache`) with offset/limit instead of `Get-Content` or shell line-range hacks.

This keeps analysis fast: **grep → targeted/parallel reads → full read only when editing**.

### 2.9 External repo intake and deep research

Use this workflow when evaluating a GitHub repository, trending library, or external example:

1. **Confirm local truth first** — read `project_map`, `resource://composer`, and any relevant module manifest before discussing landing paths.
2. **Classify the upstream source** — dependency, pattern-only, reference-only, or product-copilot inspiration.
3. **Fetch upstream facts** — README, license, package manifests, release cadence, and security notes.
4. **Compare to repo conventions** — tenant scope, permissions, Form Requests, Utils, module boundaries, Metronic UI, asset/build flow, and MCP/tooling expectations.
5. **Finish with a decision** — `adopt`, `adapt`, or `reject`, plus landing files, verification, and rollback notes.

If exact or semantic search becomes degraded during this workflow, fall back immediately instead of retrying in a loop; external-repo work already spans local and web context, so repeated tool retries waste time fastest here.

---

## 3. MCP Servers in This Repo

### 3.1 Laravel MySQL MCP Server

- Location: `mcp/laravel-mysql-mcp/`
- Purpose: repo-aware routes, controllers, schema, migrations, tests, and guarded patching
- Status: **Recommended**
- README: [mcp/laravel-mysql-mcp/README.md](../mcp/laravel-mysql-mcp/README.md)

Useful resources:

- `resource://project/map`
- `resource://routes`
- `resource://schema/snapshot`
- `resource://migrations/status`
- `resource://composer`
- `resource://conventions`
- `resource://examples/golden`
- `resource://prompts/catalog`

Useful tools:

- `project_map`
- `routes_list`
- `route_details`
- `controller_source`
- `controller_methods`
- `find_symbols`
- `migrations_status`
- `migrations_list_files`
- `migration_show`
- `schema_snapshot`
- `show_create_table`
- `schema_diff`
- `explain_query`
- `index_health`
- `config_snapshot`
- `container_bindings`
- `run_phpstan`
- `run_pint`
- `run_tests`
- `apply_patch`
- `git_status`
- `list_env`

Safety model highlights:

- Default mode is `SAFE`.
- `PATCH` mode is required before `apply_patch` is available.
- Patch operations are checked before apply.
- Blocked paths include `.git/`, `vendor/`, `storage/`, `.env`, key, and certificate files.

### 3.2 Read File Cache MCP Server

- Location: `mcp/read-file-cache-mcp/`
- Purpose: cached line-based text-file reads inside the workspace
- Status: **Recommended**
- README: [mcp/read-file-cache-mcp/README.md](../mcp/read-file-cache-mcp/README.md)

Use when:

- the file is in the workspace
- you need a bounded text-file read
- you want `offset` + `limit` slices without shell startup

### 3.3 Grep MCP Server

- Location: `mcp/grep-mcp/`
- Purpose: guarded repo-wide exact and regex search
- Status: **Recommended**
- README: [mcp/grep-mcp/README.md](../mcp/grep-mcp/README.md)

Use when:

- you know the string, selector, route, translation key, or regex pattern
- you want consistent blocked-path guardrails
- you want search without any indexing step

### 3.4 Semantic Code Search MCP Server

- Location: `mcp/semantic-code-search-mcp/`
- Purpose: local semantic code search via `search_code`, `index_codebase`, and `index_status`
- Status: **Optional**
- README: [mcp/semantic-code-search-mcp/README.md](../mcp/semantic-code-search-mcp/README.md)

Use when:

- the exact symbol is unknown
- the question is behavioral or architectural
- Ollama and the local semantic index are available

Remember:

- semantic search needs an index; grep does **not**
- **Pre-flight check (required):** Before calling `search_code`, always call `index_status` first. If the response is `INDEX_NOT_READY`, run `index_codebase` before searching. If `INDEX_STALE`, run `index_codebase --force` before searching. Do **not** call `search_code` on a stale index — results will reflect an outdated codebase and can silently mislead.
- The index is automatically kept fresh by the post-commit git hook (`scripts/warm-cache.ps1` or `.git/hooks/post-commit`). If the hook is not installed, re-run `php mcp/semantic-code-search-mcp/bin/index-codebase` manually after significant code changes.
- For embedding model upgrade options (better quality, still local) see `mcp/semantic-code-search-mcp/README.md`.

### 3.5 Audit Web MCP Server

- Location: `mcp/audit-web-mcp/`
- Purpose: Playwright-powered single-URL and prefix audits with structured findings, triage metadata, persisted reports, screenshots, traces, and optional storage-state handoff
- Status: **Recommended for browser audits**
- README: [mcp/audit-web-mcp/README.md](../mcp/audit-web-mcp/README.md)

Use when:

- the user asks for `audit and fix: <url>` or `interactive web audit: <url>`
- you need a first-pass browser bug report before editing code
- you want persisted `report.json` and `report.md` under `output/playwright/audit-web-mcp/reports/`

Workflow:

1. Start the dedicated Chrome debug browser with `scripts/open-audit-chrome.ps1`.
2. Run `audit_web` in `mode=interactive` and `persist_report=true`.
3. Read the persisted report.
4. Escalate to Chrome DevTools MCP only if `triageSummary.shouldEscalateToDevtools` is `true` or the issue remains ambiguous.
5. After code changes, verify with Playwright MCP and a rerun of `audit_web`.

Detailed repo workflow: [ai/browser-audit-workflow.md](browser-audit-workflow.md)

### 3.6 Chrome DevTools MCP

- Location: user-scoped MCP config only; not shipped in this repo
- Purpose: live inspection of a dedicated Chrome debug browser on `http://127.0.0.1:9222`
- Status: **Optional adjunct**

Use when:

- the persisted `audit_web` report shows no findings but the bug is still visible
- the issue is timing-sensitive, performance-related, or strongly tied to network waterfalls
- you need live DevTools panels beyond Playwright screenshots/traces

---

## 4. Config Patterns for Codex and Cursor

### 4.1 Codex

Codex usually reads MCP config from `~/.codex/config.toml`.

Use placeholder paths in docs and replace them locally:

```toml
[mcp_servers.laravel_mysql]
command = "php"
args = ["<repo-root>/mcp/laravel-mysql-mcp/bin/server"]

[mcp_servers.grep]
command = "php"
args = ["<repo-root>/mcp/grep-mcp/bin/server"]
env = { MCP_GREP_WORKSPACE_ROOT = "<repo-root>" }

[mcp_servers.read_file_cache]
command = "php"
args = ["<repo-root>/mcp/read-file-cache-mcp/bin/server"]
env = { MCP_READ_FILE_WORKSPACE_ROOT = "<repo-root>", MCP_READ_FILE_CACHE_ROOT = "<repo-root>/.cache/read-file-cache-mcp" }

[mcp_servers.audit_web]
command = "php"
args = ["<repo-root>/mcp/audit-web-mcp/bin/server"]
env = { MCP_AUDIT_WEB_WORKSPACE_ROOT = "<repo-root>" }

[mcp_servers.semantic_code_search]
command = "php"
args = ["<repo-root>/mcp/semantic-code-search-mcp/bin/server"]
env = { MCP_SEMANTIC_WORKSPACE_ROOT = "<repo-root>", MCP_SEMANTIC_INDEX_ROOT = "<repo-root>/.cache/semantic-code-search-mcp", MCP_SEMANTIC_OLLAMA_HOST = "http://127.0.0.1:11434", MCP_SEMANTIC_EMBED_MODEL = "nomic-embed-text" }
```

Short local example:

- If your clone lives at `D:/path/to/rey`, then `<repo-root>` becomes `D:/path/to/rey`.

If you use the optional `cursor-tool-behavior` skill in Codex, keep it installed in your normal Codex/agent home rather than documenting a machine-specific absolute path in repo docs.

### 4.2 Cursor

Cursor MCP config may live in:

- project-level `.cursor/mcp.json`
- user-level `~/.cursor/mcp.json`

Example project-level config with placeholders:

```json
{
  "mcpServers": {
    "laravel_mysql": {
      "command": "php",
      "args": ["<repo-root>/mcp/laravel-mysql-mcp/bin/server"]
    },
    "grep": {
      "command": "php",
      "args": ["<repo-root>/mcp/grep-mcp/bin/server"],
      "env": {
        "MCP_GREP_WORKSPACE_ROOT": "<repo-root>"
      }
    },
    "read_file_cache": {
      "command": "php",
      "args": ["<repo-root>/mcp/read-file-cache-mcp/bin/server"],
      "env": {
        "MCP_READ_FILE_WORKSPACE_ROOT": "<repo-root>"
      }
    }
  }
}
```

If semantic search is enabled in Cursor, add the semantic server the same way and point it at your local Ollama/index setup.

Official Cursor docs: [docs.cursor.com/context/model-context-protocol](https://docs.cursor.com/context/model-context-protocol)

### 4.3 Codex: index codebase faster

If you use the MCP servers in the Codex extension, do the following so the codebase is indexed and cached for faster reads and search:

1. **Config** — In `~/.codex/config.toml`, register at least `grep` and `read_file_cache` with `<repo-root>` set to this project’s absolute path (for example `D:/wamp64/www/upos612`). Add `laravel_mysql` and optionally `semantic_code_search` if you use them.

2. **Read-file cache (faster file reads)** — Pre-warm the disk cache so `read_file` hits cache instead of the filesystem. Either:
   - **CLI (recommended):** From repo root run once (e.g. after clone or before a long Codex session):
     ```bash
     php mcp/read-file-cache-mcp/bin/warm-cache
     ```
     Optional: `--max-files=10000` or `--path=app` to limit scope.
   - **Agent:** The agent can call the `warm_cache` tool at the start of a session when `read_file_cache` is available (see AGENTS.md).
   - **Health check:** After warming the cache, run `php scripts/check-mcp-health.php` to confirm `read_file_cache` returns actual file text and the required MCPs are ready.

3. **Semantic search (optional)** — If you use the semantic_code_search server, build the index once so `search_code` works. From repo root:
   ```bash
   php mcp/semantic-code-search-mcp/bin/index-codebase
   ```
   Requires Ollama and the embed model (e.g. `ollama pull nomic-embed-text`).

   If Ollama is unavailable or the index is missing, the health check reports `OLLAMA_UNAVAILABLE` or `NOT_INDEXED` and the fallback stays `laravel_mysql` -> `grep` -> `read_file_cache`.

4. **Grep** — No index; ripgrep runs on each call. Ensure `rg` is on PATH so the grep MCP server works. For startup probes, use a **small path-scoped search** instead of a broad repo scan.

---

## 5. Cursor vs Codex Behavior Split

### 5.1 Exact vs semantic

- **Exact/pattern search:** use MCP `grep` in Codex when available; use Cursor's built-in Grep/instant Grep in Cursor when MCP grep is unavailable.
- **Meaning-based discovery:** use MCP `search_code` when semantic MCP is available; otherwise use the platform's built-in codebase/semantic search.

### 5.2 Index requirements

| Tool | Index required? | Why |
|---|---|---|
| Grep MCP | No | Runs ripgrep on demand |
| Read File Cache MCP | No | Reads files directly with guardrails |
| Laravel MySQL MCP | No | Uses repo-aware Laravel introspection |
| Semantic MCP | Yes | Uses embeddings and a local index |
| Cursor built-in semantic/codebase search | Yes | Managed by Cursor |
| Cursor built-in Grep | No (or internal only) | Pattern search |

---

## 6. What This Doc Does Not Cover

This document covers tool choice, fallback order, and MCP usage. It does **not** cover model training, fine-tuning, or platform-level inference controls such as temperature. For that boundary, read [ai/agent-improvement.md](agent-improvement.md).
