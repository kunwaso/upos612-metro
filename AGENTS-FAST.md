# AGENTS-FAST.md - Quick Execution Guide

Use this file for day-to-day speed.  
Source of truth remains `AGENTS.md` and `ai/*.md`.

---

## 1) Rule Priority

Apply the first matching rule:

1. Direct user instruction for the current task
2. `AGENTS.md`
3. `.cursor/rules/*.mdc` (when applied by the editor)
4. Domain docs in `ai/*.md`
5. Existing local code patterns

If there is still conflict, apply the safest interpretation and note the assumption — only stop and ask if the conflict would cause data loss, a destructive action, or a security violation.

---

## 2) Mode Check

Before edits or commands, determine mode once:

1. `ask mode`: explain/investigate only (no file writes, no run commands)
2. `implement mode`: full flow (edit, run checks, verify)

If user already said "ask/explain" or "implement/make the change", follow that directly.

---

## 3) Intent Router (Fast)

Pick one lane first:

| Intent | Use when | Default flow |
|---|---|---|
| `tiny` | Single file or tightly scoped change | restate -> inspect -> edit -> verify |
| `explain` | User wants understanding only | search -> read -> answer |
| `analyze` | Audit module, clone, or understand codebase | project_map/filesystem -> grep first -> targeted/parallel reads -> full read only when editing |
| `dependency-eval` | Evaluate a GitHub repo, library, or dependency before adoption | project_map -> composer/manifests -> fetch upstream docs -> compare -> adopt/adapt/reject |
| `external-adapt` | Adapt a pattern or example from an external repo | project_map -> closest local example -> fetch upstream docs -> map into route/Form Request/Util/controller/view/module |
| `investigate` | "doesn't work", "stops after X" | grep -> read -> compare bind vs DOM update |
| `review` | User asks for review/audit | findings first -> evidence -> brief summary |
| `implement` | Default for fix/feature | inspect -> plan -> edit -> verify |
| `log-scan` | User asks to scan/fix Laravel log or fix issues from `storage/logs` | glob latest `storage/logs/laravel-*.log` -> read -> parse errors -> investigate -> fix -> verify |
| `lint-fix` | User asks to fix linter errors, IDE diagnostics, or "fix lint" | Read lints (scope) -> fix each finding -> re-run lints |
| `test-fix` | User asks to fix failing tests or pastes test output | parse output -> locate failure -> fix test or code -> re-run tests |
| `tenant-audit` | User asks to audit/fix tenant security, missing business_id, or route auth | grep checklist -> fix or report each finding |
| `known-issues-fix` | User asks to fix known-issues in an area or apply ai/known-issues.md | read known-issues for area -> apply mitigations/fixes |
| `full-autofix` | "run all autofixes", "check project", "health check", "autofix everything" | log-scan -> lint-fix -> optional tenant-audit / known-issues-fix |
| `web-audit` | User asks for `audit and fix: <url>` or `interactive web audit: <url>` | open audit Chrome -> interactive `audit_web` -> read persisted report -> optional Chrome DevTools escalation -> fix -> Playwright + `audit_web` verify |
| `product-copilot-eval` | User asks about an in-app assistant, guided UI helper, or ERP copilot | read product-copilot patterns -> read security/ui/Aichat docs -> define safe first use case |
| `design-audit` | User asks to audit a view/screen for a11y, contrast, responsive, or UI quality | read ai/ui-components.md + target Blade -> checklist (focus, contrast, structure, assets) -> report (and fix within Metronic if implement mode) |
| `design-polish` | User asks for a final design pass on a view or component | read view + ui-components -> improve hierarchy, spacing, copy within Metronic only; no new classes |
| `design-critique` | User asks for UX/review of a screen or flow | read view -> assess clarity, hierarchy, empty/error states -> short critique + Metronic-safe suggestions |

Use full `AGENTS.md` process for multi-file or higher-risk work.

## 3.1) Skill-First Flow

For anything larger than `tiny`, use this compact sequence:

1. Clarify intent and pick the lane.
2. Design in chunks instead of guessing the whole solution at once.
3. Write a verification-aware plan.
4. Execute in bounded tasks with clear file responsibility.
5. Review with evidence before finishing.

If a matching helper already exists in `.cursor/skills/` or `.cursor/prompts/`, use it.

---

## 4) Tiny-Task Fast Lane

For single-file or very small changes:

1. Restate the goal in one sentence.
2. Narrow target with grep/semantic search.
3. Read only the required file or line range.
4. Make minimal edit.
5. Run closest validation (lint/test/manual check).
6. Report what changed and what was verified.

**Analyze/scan (module audit, clone, codebase understanding):** Grep (or glob) first to list files and find references; read only flagged files or line ranges, in parallel when independent (3–5 per turn); full-file read only when editing. See `ai/agent-tools-and-mcp.md` §2.8.

---

## 5) Tool Ladder (Availability + Health)

Preferred order:

1. `laravel_mysql` for repo-aware routes/schema/tests/project map
2. `grep` for exact/pattern search
3. `read_file_cache` for file content and slices
4. `semantic_code_search` when exact symbols are unknown (optional)

Required baseline for this repo:

1. `laravel_mysql`
2. `grep`
3. `read_file_cache`

Optional:

1. `semantic_code_search`

Session-start check:

1. Keep exact startup commands centralized in `mcp/CODEX-SETUP.md`.
2. Run `php scripts/check-mcp-health.php` from repo root after installing MCP deps, warming caches, or changing local MCP config.
3. For deep or external work, start with: `project_map` -> `resource://composer` -> `index_status` after cache warm-up.
4. Treat `semantic_code_search` warnings as non-blocking unless the task needs behavior-level discovery.

If a preferred tool is available but unhealthy/degraded (for example timeout, empty content, metadata-only responses, stale index, repeated failure):

1. Use the next fastest repo-aware tool for that step.
2. Keep the same split: grep for exact, semantic for meaning, read tool for content.
3. Do not loop on the failing tool; fall back immediately.
4. Note the fallback briefly when it affects confidence or speed.

---

## 6) What To Read In `ai/`

Read only the domain doc(s) you touch:

| Task domain | Read first |
|---|---|
| UI/Blade (Metronic project-wide) | `ai/ui-components.md` |
| Architecture conventions | `ai/laravel-conventions.md` |
| DB queries/migrations | `ai/database-map.md` |
| Auth/security/permissions | `ai/security-and-auth.md` |
| Existing bug-prone areas | `ai/known-issues.md` |
| MCP/tool choice | `ai/agent-tools-and-mcp.md` |
| External repo / GitHub / trending evaluation | `ai/external-adoption.md` |
| Deep research / bounded delegation | `ai/research-and-delegation.md` |
| In-app agent / product copilot evaluation | `ai/product-copilot-patterns.md` + `Modules/Aichat/README.md` |
| Browser audit workflow | `ai/browser-audit-workflow.md` |
| ProjectAuto wizard workflow | `ai/projectauto-workflow-wizard.md` |
| Workflow tuning | `ai/agent-improvement.md` |
| ProjectX <-> root integration | `ai/projectx-integration.md` |

Read order for new lanes:

1. `dependency-eval`: `ai/external-adoption.md` -> `ai/agent-tools-and-mcp.md` -> relevant domain docs
2. `external-adapt`: `ai/research-and-delegation.md` -> `ai/external-adoption.md` -> closest local feature/docs
3. `product-copilot-eval`: `ai/product-copilot-patterns.md` -> `ai/security-and-auth.md` -> `ai/ui-components.md` -> `Modules/Aichat/README.md`

---

## 7) Non-Negotiables

1. Tenant safety: scope tenant data with `business_id`.
2. Keep business logic in Utils, controllers orchestration-focused.
3. Use Form Requests for validation.
4. Check permissions before mutations.
5. Use the correct UI mode: **Metronic 8.3.3 project-wide** (see `ai/ui-components.md` and AGENTS.md Section 10).

---

## 8) Done Gate

Before saying done:

1. Goal is satisfied.
2. Rules and conventions were followed.
3. Evidence is real (files/tests/lints/manual checks).
4. No obvious caller impact was skipped.
5. Response states what changed and what was verified.

For deep details and edge cases, jump to `AGENTS.md`.
