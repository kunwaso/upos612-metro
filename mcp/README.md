# MCP Servers

Index of MCP servers in this repository.

**Codex users:** See [mcp/CODEX-SETUP.md](./CODEX-SETUP.md) for config (`codex-config.toml.example`), one-time install, and warm-cache steps.

Recommended startup order in this repo:

1. `grep` - required
2. `read_file_cache` - required
3. `laravel_mysql` - required on demand for repo-aware structure/schema/routes
4. `gitnexus` - recommended for shared-code edits, refactors, and architecture work
5. `semantic_code_search` - optional but recommended for behavior-level discovery when ready
6. `audit_web` - optional (browser audits/smoke)

Run `powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\warm-cache.ps1 -Profile startup` and then `php scripts/check-mcp-health.php` from repo root to verify the current machine is ready. The health check uses path-scoped probes for grep, validates that `read_file_cache` returns actual file text, reports `gitnexus` readiness as `READY`, `STALE`, or `MISSING_INDEX`, reports `audit_web` readiness as `READY`, `MISSING_DEPENDENCIES`, or `PLAYWRIGHT_UNAVAILABLE`, and reports semantic readiness as `READY`, `NOT_INDEXED`, `STALE`, or `EMBEDDER_UNAVAILABLE`.

- `laravel-mysql-mcp` - Laravel repo-aware routes/schema/tests/tools MCP server. **Recommended** when available for repo-aware introspection and verification.
  - README: [mcp/laravel-mysql-mcp/README.md](./laravel-mysql-mcp/README.md)
- `grep-mcp` - Guarded repo-wide ripgrep search for exact/pattern matches. **Recommended** for exact strings, symbols, routes, and regex. **No codebase index** — runs ripgrep on-demand (like instant grep).
  - README: [mcp/grep-mcp/README.md](./grep-mcp/README.md)
- `read-file-cache-mcp` - Safe cached line-based workspace file reads (`read_file`). **Recommended** for workspace file reads in MCP-aware clients.
  - README: [mcp/read-file-cache-mcp/README.md](./read-file-cache-mcp/README.md)
- `gitnexus` - MCP-backed code-intelligence graph for impact, context, query, rename, and change detection. **Recommended** before shared-code edits and refactors.
- `audit-web-mcp` - Playwright-powered URL/prefix audits with persisted `report.json` and `report.md`. **Optional** and recommended for interactive browser audits and smoke checks behind auth.
  - README: [mcp/audit-web-mcp/README.md](./audit-web-mcp/README.md)
- `semantic-code-search-mcp` - Local semantic code search (`search_code`, `index_codebase`, `index_status`). **Optional** and **requires indexing** — run `index_codebase` when the index is missing or stale. Use when: meaning-based queries ("where is X done?", "how does Y work?"); you want a project-controlled alternative to Cursor's codebase index.
  - README: [mcp/semantic-code-search-mcp/README.md](./semantic-code-search-mcp/README.md)

When the exact symbol is known, prefer `grep` + `read_file_cache` + `laravel_mysql`. Reach for GitNexus before shared-code edits or unfamiliar execution flows, and reach for semantic search when the question is behavioral or architectural and exact strings are not enough.
