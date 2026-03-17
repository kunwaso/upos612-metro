# MCP Servers

Index of MCP servers in this repository.

**Codex users:** See [mcp/CODEX-SETUP.md](./CODEX-SETUP.md) for config (`codex-config.toml.example`), one-time install, and warm-cache steps.

- `laravel-mysql-mcp` - Laravel repo-aware routes/schema/tests/tools MCP server. **Recommended** when available for repo-aware introspection and verification.
  - README: [mcp/laravel-mysql-mcp/README.md](./laravel-mysql-mcp/README.md)
- `grep-mcp` - Guarded repo-wide ripgrep search for exact/pattern matches. **Recommended** for exact strings, symbols, routes, and regex. **No codebase index** — runs ripgrep on-demand (like instant grep).
  - README: [mcp/grep-mcp/README.md](./grep-mcp/README.md)
- `read-file-cache-mcp` - Safe cached line-based workspace file reads (`read_file`). **Recommended** for workspace file reads in MCP-aware clients.
  - README: [mcp/read-file-cache-mcp/README.md](./read-file-cache-mcp/README.md)
- `semantic-code-search-mcp` - Local semantic code search (`search_code`, `index_codebase`, `index_status`). **Optional** and **requires indexing** — run `index_codebase` when the index is missing or stale. Use when: meaning-based queries ("where is X done?", "how does Y work?"); you want a project-controlled alternative to Cursor's codebase index.
  - README: [mcp/semantic-code-search-mcp/README.md](./semantic-code-search-mcp/README.md)
