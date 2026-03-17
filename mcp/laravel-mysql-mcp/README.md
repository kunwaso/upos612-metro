# Laravel MySQL MCP Server

Standalone MCP server for this Laravel repository.

## Status

- Recommended for repo-aware routes, schema, migrations, tests, and guarded repository introspection.

## Location

`mcp/laravel-mysql-mcp/`

## Requirements

- PHP `^8.1`
- Composer
- Root Laravel app dependencies installed (`vendor/` at repo root)

Note: the **application** supports `php ^8.0`, but this MCP server itself requires PHP `^8.1+`.

## Install

```bash
cd mcp/laravel-mysql-mcp
composer install
```

## Run (STDIO)

From repo root:

```bash
php mcp/laravel-mysql-mcp/bin/server
```

## Modes

- `SAFE` (default): read-only plus command tools; `apply_patch` is blocked
- `PATCH`: enables `apply_patch` with path and diff guardrails

Set mode:

```bash
set LARAVEL_MCP_MODE=PATCH
php mcp/laravel-mysql-mcp/bin/server
```

## Environment Variables

- `LARAVEL_MCP_MODE` (`SAFE` | `PATCH`)
- `LARAVEL_MCP_MAX_OUTPUT_BYTES` (default `204800`)
- `LARAVEL_MCP_MAX_PATCH_LINES` (default `400`)
- `LARAVEL_MCP_CACHE_TTL_ROUTES` (default `45`)
- `LARAVEL_MCP_CACHE_TTL_SCHEMA` (default `90`)
- `LARAVEL_MCP_CACHE_TTL_PROJECT` (default `90`)

## Codex Config

Typical Codex config file:

- `~/.codex/config.toml`

Use placeholder paths in repo docs and replace them locally:

```toml
[mcp_servers.laravel_mysql]
command = "php"
args = ["<repo-root>/mcp/laravel-mysql-mcp/bin/server"]
```

Short local example:

- If your clone lives at `D:/path/to/rey`, then `<repo-root>` becomes `D:/path/to/rey`.

## Resources

- `resource://project/map`
- `resource://routes`
- `resource://schema/snapshot`
- `resource://migrations/status`
- `resource://composer`
- `resource://conventions`
- `resource://examples/golden`
- `resource://prompts/catalog`

## Tools

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

## Prompts

- `optimize_controller`
- `migration_safety_check`
- `perf_tuning_sql`
