# Codex MCP setup (this project)

Use this runbook to make the Codex MCP stack deterministic and fast in this repo.

## 1. Codex config

Copy or merge `mcp/codex-config.toml.example` into your user config:

- Windows: `%USERPROFILE%\.codex\config.toml`
- macOS/Linux: `~/.codex/config.toml`

PowerShell copy example:

```powershell
New-Item -ItemType Directory -Force -Path "$env:USERPROFILE\.codex"
Copy-Item "D:\wamp64\www\upos612\mcp\codex-config.toml.example" "$env:USERPROFILE\.codex\config.toml"
```

Then replace `<repo-root>` with your local checkout path (example: `D:/wamp64/www/upos612`).

Notes:

- GitNexus is pinned to `gitnexus@1.4.8` (no `@latest` drift).
- `semantic_code_search` is enabled with explicit workspace/index scope envs.

Restart Codex after config changes.

## 2. One-time installs

Read-file cache MCP:

```powershell
Set-Location D:\wamp64\www\upos612\mcp\read-file-cache-mcp
composer install
Set-Location D:\wamp64\www\upos612
```

Optional browser-audit MCP:

```powershell
Set-Location D:\wamp64\www\upos612\mcp\audit-web-mcp
composer install
npm install
npx playwright install
Set-Location D:\wamp64\www\upos612
```

## 3. Startup orchestration (single command)

Use `scripts/warm-cache.ps1` as the canonical startup entrypoint.

Startup profile:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\warm-cache.ps1 -Profile startup
```

Nightly embeddings profile:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\warm-cache.ps1 -Profile nightly-embeddings
```

Useful flags:

- `-DryRun`
- `-SkipSemantic`
- `-SkipGitNexus`
- `-WarmPath app`
- `-MaxFiles 5000`

Windows helper:

```powershell
.\warm-and-index.bat --profile startup
.\warm-and-index.bat --profile nightly-embeddings --dry-run
```

The script writes logs to `.cache/mcp-automation/`.

## 4. Hook automation (post-commit + post-merge)

Install managed blocks:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\manage-mcp-hooks.ps1 -Action install
```

Check status:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\manage-mcp-hooks.ps1 -Action status
```

Managed hook payloads live in:

- `scripts/hooks/post-commit-mcp.sh`
- `scripts/hooks/post-merge-mcp.sh`

Hook logs are written to `.cache/mcp-hooks/`.

## 5. Semantic and GitNexus cadence

Semantic (Ollama required):

```powershell
ollama pull nomic-embed-text
php mcp/semantic-code-search-mcp/bin/index-codebase --force
```

GitNexus graph refresh:

```powershell
npx -y gitnexus@1.4.8 analyze
```

Nightly deep graph (embeddings):

```powershell
npx -y gitnexus@1.4.8 analyze --embeddings
```

Health check (after setup/startup changes):

```powershell
php scripts/check-mcp-health.php
```

Optional deep semantic probe (slower, runs embed/search validation):

```powershell
$env:MCP_HEALTH_DEEP_SEMANTIC_PROBE='1'
php scripts/check-mcp-health.php
Remove-Item Env:MCP_HEALTH_DEEP_SEMANTIC_PROBE
```

## 6. Rollback toggles

Disable semantic quickly:

- Startup: add `-SkipSemantic`
- Config: comment/remove `[mcp_servers.semantic_code_search]` in `~/.codex/config.toml`

Disable GitNexus refresh quickly:

- Startup: add `-SkipGitNexus`
- Hooks: uninstall managed blocks

Disable managed hooks:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\manage-mcp-hooks.ps1 -Action uninstall
```

Note: scheduled-task register/unregister can require elevated privileges.

Revert pinned GitNexus (not recommended):

- Change `gitnexus@1.4.8` back to your preferred version in `~/.codex/config.toml`.

## 7. POS smoke auth bootstrap (optional)

Use this when smoke automation is blocked by `/login` redirect:

```powershell
.\scripts\audit-pos-smoke.ps1 -Mode bootstrap -BaseUrl https://upos612
.\scripts\audit-pos-smoke.ps1 -Mode single -BaseUrl https://upos612 -PosPath pos
.\scripts\audit-pos-smoke.ps1 -Mode matrix -BaseUrl https://upos612 -PathPrefix pos
```

Default auth state path:

`output/playwright/audit-web-mcp/reports/.auth/pos-admin.json`

## 8. Plan files are canonical

Do not rewrite `.cursor/plans/*.plan.md` when asked to execute a plan.
Follow `AGENTS.md` (`execute-plan`) and `.cursor/plans/README.md` section 7.

---

## Quick checklist

1. Config merged and Codex restarted.
2. `composer install` done for `mcp/read-file-cache-mcp` (and optional audit-web MCP deps).
3. Startup profile runs and writes logs.
4. `php scripts/check-mcp-health.php` reports required MCPs as `PASS`.
5. Hooks installed (or intentionally disabled).
