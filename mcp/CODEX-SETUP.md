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

- GitNexus is installed globally (`npm install -g gitnexus@1.6.1`); uses direct `gitnexus` command for fast startup.
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
- `-DeepSemanticProbe`
- `-RequireGitNexusReady`
- `-RequireSemanticReady`
- `-WarmPath app`
- `-MaxFiles 1000`

Windows helper:

```powershell
.\warm-and-index.bat --profile startup
.\warm-and-index.bat --profile nightly-embeddings --dry-run
```

The script writes logs to `.cache/mcp-automation/`.

## 4. Hook automation (pre-push only)

`install` adds a **pre-push** hook so semantic reindex + GitNexus analyze run **only when you `git push`**, and only if the commits being pushed touch indexed paths. Work runs **in the background** (push is not blocked). Semantic uses an **incremental** index (no `--force`); use `index-codebase --force` manually when you need a full rebuild.

`install` also **removes** managed blocks from **post-commit** and **post-merge** so you do not reindex on every local commit or after `git pull`.

Install managed blocks:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\manage-mcp-hooks.ps1 -Action install
```

Check status:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\manage-mcp-hooks.ps1 -Action status
```

Managed hook payload:

- `scripts/hooks/pre-push-mcp.sh`

Legacy payloads (not installed by default):

- `scripts/hooks/post-commit-mcp.sh`
- `scripts/hooks/post-merge-mcp.sh`

Hook logs are written to `.cache/mcp-hooks/` (e.g. `pre-push.log`).

## 5. Semantic and GitNexus cadence

Semantic (fully local Hugging Face):

```powershell
python -m pip install -r mcp/semantic-code-search-mcp/scripts/requirements.txt
python -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('BAAI/bge-base-en')"
php mcp/semantic-code-search-mcp/bin/index-codebase --force
```

GitNexus graph refresh:

```powershell
gitnexus analyze
```

Nightly deep graph (embeddings):

```powershell
gitnexus analyze --embeddings
```

Health check (after setup/startup changes):

```powershell
php scripts/check-mcp-health.php
```

Strict shared-code startup contract:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\warm-cache.ps1 -Profile startup -RequireGitNexusReady
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

- Change the globally installed gitnexus version via `npm install -g gitnexus@<version>` if needed.

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
5. `gitnexus` is `READY` before shared-code edits and `semantic_code_search` is `READY` before behavior-level discovery.
6. Hooks installed (or intentionally disabled).
