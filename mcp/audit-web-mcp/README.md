# Audit Web MCP Server

Playwright-powered MCP server for auditing one URL (`scope: "single"`) or a route prefix (`scope: "prefix"`).

## Install

```bash
cd mcp/audit-web-mcp
composer install
```

From repo root, ensure Playwright dependencies are installed:

```bash
npm install
npx playwright install
```

## Tool

- Tool name: `audit_web`
- Key input:
  - `url` (required)
  - `scope`: `single` | `prefix` (default `single`)
  - `pathPrefix` (required for `prefix`)
  - Auth options: `storage_state_path` or `login_url + login_username + login_password`
  - Optional: `steps[]`, `timeout`, `waitUntil`, `waitAfterLoadMs`
- Key output:
  - Single scope: `auditStatus`, `url`, `findings[]`, optional `runnerError`
  - Prefix scope: `scope`, `baseUrl`, `pathPrefix`, `auditStatus`, `total`, `passed`, `failed`, `results[]`

### Exit semantics

- Completed audit always exits `0`, even when findings exist (`auditStatus: "fail"`).
- Non-zero exit is reserved for runner-level failures (launch/config/script crash/invalid payload).

## Single URL usage

```json
{
  "url": "https://projectx/home",
  "scope": "single"
}
```

## Prefix usage

```json
{
  "url": "https://projectx",
  "scope": "prefix",
  "pathPrefix": "home",
  "storage_state_path": "tests/e2e/.auth/storage-state.json"
}
```

Notes:

- Prefix matching is explicit (`pathPrefix`) and does not rely on trailing slash behavior.
- Prefix route selection is GET/HEAD web routes only.
- Routes with required params are skipped unless seeded in future extensions.
- Prefix auditing is batch-first (`scripts/run-audit-batch.js`) with bounded concurrency.

## Route list strategy

Primary route source is:

```bash
php artisan route:list --json
```

If that fails in the current checkout/environment, the server falls back to Laravel bootstrap route introspection.

## Auth behavior

- Recommended: storage-state handoff (`storage_state_path`) from an interactive login run.
- For protected prefixes, auth input is required to avoid login-redirect noise.
- Credential fields are supported but should be treated as sensitive and never logged.

## Interactive audit

Optional headed mode:

```bash
npm run audit:interactive -- https://projectx/home
```

- Lets you login/interact manually before final report capture.
- Supports hover highlight + double-click element pick.
- Can save storage state for later headless audits.

**If no browser window appears:** Run the command from a normal terminal (PowerShell or Cmd) opened yourself, not from an IDE-integrated terminal. On Windows, the script uses launch args (`--disable-gpu`, etc.) to improve visibility; if it still fails, ensure Playwright browsers are installed (`npx playwright install` from repo root).

