# Agent Team Policy

**Owner:** UPOS Engineering
**Canonical home:** This file. Cross-linked from `mcp/README.md` and `readme.md`.

This document defines the **approved MCP server list**, data-handling rules, CI expectations, and local machine policy for any developer or AI agent working on this repo. It does **not** replace `AGENTS.md` (which owns process and lanes) or `ai/agent-tools-and-mcp.md` (which owns tool routing).

---

## 1. Approved MCP Servers

Only these MCP servers are approved for use in local and CI environments on this project. Any new server requires a team decision before it is enabled in `.cursor/mcp.json` or shared Codex config.

| Server | Status | Purpose |
|--------|--------|---------|
| `grep` (`grep-mcp`) | **Required** | Exact and regex codebase search |
| `read_file_cache` (`read-file-cache-mcp`) | **Required** | Cached workspace file reads |
| `laravel_mysql` (`laravel-mysql-mcp`) | **On-demand** | Repo-aware routes, schema, migrations, tests, project map |
| `gitnexus` | **Recommended** | Symbol impact, caller/callee graph, pre-commit scope check |
| `semantic_code_search` (`semantic-code-search-mcp`) | **Optional** | Meaning-based discovery when exact symbol is unknown |
| `audit_web` (`audit-web-mcp`) | **Optional** | Browser audits with persisted findings (Playwright) |
| `chrome-devtools` | **Optional adjunct** | Live Chrome DevTools for timing-sensitive or ambiguous issues |

See `mcp/README.md` for install instructions and startup order.

**Adding a new server:** raise it in a team discussion, assess the data access surface, document in `mcp/README.md`, and update this table.

---

## 2. Data-Handling Rules

These rules apply to every developer and AI agent on this project.

1. **No production DB URLs in MCP env.** MCP servers (e.g. `laravel_mysql`) must only point to local or staging databases. Never put a production connection string in `.cursor/mcp.json` or `~/.codex/config.toml`.
2. **No secrets in prompts.** API keys, passwords, tokens, and PII must not appear in AI prompts, plan files, or `ai/` docs.
3. **No production data in test fixtures.** Factories and seeders must generate synthetic data; do not copy real customer rows.
4. **Agent output stays in repo.** Plan files (`.cursor/plans/`), audit reports (`output/`), and agent transcripts stay inside the repo's standard paths; do not share agent output that contains real user data externally.
5. **Blocked paths for MCP tools:** `vendor/`, `storage/`, `.env`, key and certificate files are blocked by `laravel-mysql-mcp`'s safety model by default. Do not change `MCP_LARAVEL_SAFETY_MODE` without team approval.

---

## 3. CI Expectations

Every pull request to `main`/`master` must pass the following checks (defined in `.github/workflows/agent-compliance.yml`):

| Check | Script / step | Fail condition |
|-------|--------------|----------------|
| **Blade constitution** | `composer agents:compliance` | `@php` default/compute patterns in changed Blade files |
| **Tenant-scope heuristic** | `composer agents:tenant` | Bare `findOrFail`/`find` without any `business_id` in the whole file |
| **PHPStan level 0** | `composer phpstan` | Type errors at level 0 in `app/` and `Modules/` |
| **Translation parity** | `composer translation:audit` | Vietnamese translation missing keys |

**Weekly:** Full Blade scan (`composer agents:compliance:full`) runs every Sunday via the scheduled workflow and emails results to the repo maintainers.

**To suppress a finding:**
- Single line: add `{{-- agent-compliance:ignore --}}` (Blade) or `// agent-compliance:ignore-business_id` (PHP) on that line.
- Grandfathered file: add to `scripts/agent-compliance-blade-allowlist.txt` or `scripts/agent-compliance-tenant-allowlist.txt` with a comment.

---

## 4. Pre-Commit Local Hook (Optional)

CI is the enforcement point. Local pre-commit is optional but recommended for fast feedback.

**Install (Windows, one-time):**

```powershell
# From repo root
@'
#!/bin/sh
php scripts/check-agents-compliance.php
if [ $? -ne 0 ]; then
  echo "Blade constitution check failed. Fix violations or use agent-compliance:ignore."
  exit 1
fi
exit 0
'@ | Out-File -Encoding utf8 .git/hooks/pre-commit
```

**Install (macOS/Linux, one-time):**

```bash
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/sh
php scripts/check-agents-compliance.php
if [ $? -ne 0 ]; then
  echo "Blade constitution check failed. Fix violations or use agent-compliance:ignore."
  exit 1
fi
exit 0
EOF
chmod +x .git/hooks/pre-commit
```

The hook calls the same Composer script that CI uses, so results are consistent.

---

## 5. Machine-Specific Policy

Developers may optionally keep a local `agent-policy.local.md` (gitignored) for machine-specific notes such as "never enable X on this laptop" or "production read-only DB is available here via a separate MCP key."

Copy the template: `cp agent-policy.local.example.md agent-policy.local.md`

---

## 6. Compliance Script Reference

| Script | Composer alias | Purpose |
|--------|---------------|---------|
| `scripts/check-agents-compliance.php` | `agents:compliance` | Diff-mode Blade constitution check |
| `scripts/check-agents-compliance.php --full` | `agents:compliance:full` | Full-repo Blade scan with allowlist |
| `scripts/check-tenant-scope.php` | `agents:tenant` | Tenant-scope heuristic (diff-mode) |
| `scripts/check-mcp-health.php` | — | MCP server health check |
| `scripts/check-agents-compliance.php --help` | — | Script usage |

---

## 7. Related Documents

- `AGENTS.md` — process, lanes, five checks (policy source of truth)
- `ai/agent-tools-and-mcp.md` — tool routing, degraded-tool fallback, MCP config
- `mcp/README.md` — MCP server install, startup order, and health check
- `ai/known-issues.md` — §1.2 for the multi-tenant `business_id` issue this checker addresses
- `.cursor/rules/laravel-coding-constitution.mdc` — the Blade rules the compliance script encodes
