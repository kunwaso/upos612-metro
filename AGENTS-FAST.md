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

If there is still conflict, apply the safest interpretation and note the assumption — only stop and ask if the conflict would cause data loss, a destructive action, or a security violation. For the full conflict-resolution rule see `AGENTS.md` §0.1.

## 1.1) Document Ownership

Use one primary home per rule family:

1. `AGENTS.md` = policy, mode, intent lanes, five checks, and review gate
2. `AGENTS-FAST.md` = fast routing and short execution defaults
3. `ai/agent-tools-and-mcp.md` = tool choice, MCP fallback, and startup rules
4. `.cursor/plans/README.md` = phased-plan structure and verification checklist
5. `readme.md` = lightweight entry points and examples only

If a short doc and a canonical doc overlap, follow the canonical doc and trim the short doc to a summary plus a link.

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
| `tiny` | Single file or tightly scoped change | restate -> if location unknown open `ai/entrypoints/INDEX.md` -> grep/read smallest target -> edit -> verify changed scope |
| `explain` | User wants understanding only | reasoning or grep -> targeted read -> answer; stop before heavy repo tools unless repo truth is required |
| `analyze` | Audit module, clone, or understand codebase | project_map/filesystem -> grep first -> targeted/parallel reads -> full read only when editing |
| `dependency-eval` | Evaluate a GitHub repo, library, or dependency before adoption | project_map -> composer/manifests -> fetch upstream docs -> compare -> adopt/adapt/reject |
| `external-adapt` | Adapt a pattern or example from an external repo | project_map -> closest local example -> fetch upstream docs -> map into route/Form Request/Util/controller/view/module |
| `investigate` | "doesn't work", "stops after X" | restate flow -> grep exact identifier -> targeted read -> compare bind vs DOM update -> fix or answer |
| `review` | User asks for review/audit | findings first -> evidence -> brief summary |
| `implement` | Default for fix/feature | if location unknown open `ai/entrypoints/INDEX.md` -> inspect -> plan with files/verification -> edit -> verify changed scope first, then broader checks if needed |
| `log-scan` | User asks to scan/fix Laravel log or fix issues from `storage/logs` | glob latest `storage/logs/laravel-*.log` -> read -> parse errors -> investigate -> fix -> verify |
| `lint-fix` | User asks to fix linter errors, IDE diagnostics, or "fix lint" | Read lints (scope) -> fix each finding -> re-run lints |
| `test-fix` | User asks to fix failing tests or pastes test output | parse output -> locate failure -> fix test or code -> re-run tests |
| `tenant-audit` | User asks to audit/fix tenant security, missing business_id, or route auth | grep checklist -> fix or report each finding |
| `known-issues-fix` | User asks to fix known-issues in an area or apply ai/known-issues.md | read known-issues for area -> apply mitigations/fixes |
| `full-autofix` | "run all autofixes", "check project", "health check", "autofix everything" | log-scan -> lint-fix -> optional tenant-audit / known-issues-fix |
| `web-audit` | User asks for `audit and fix: <url>` or `interactive web audit: <url>` | open audit Chrome -> interactive `audit_web` -> read persisted report -> optional Chrome DevTools escalation -> fix -> Playwright + `audit_web` verify |
| `product-copilot-eval` | User asks about an in-app assistant, guided UI helper, or ERP copilot | read `ai/product-copilot-patterns.md` -> `ai/aichat-authz-baseline.md` + `Modules/Aichat/README.md` when Aichat is in scope -> define approval boundaries, safe first use case, and rollout scope; no `project_map` or `warm_cache` unless explicitly scoping for this repo |
| `design-audit` | User asks to audit a view/screen for a11y, contrast, responsive, or UI quality | read ai/ui-components.md + target Blade -> checklist (focus, contrast, structure, assets) -> report (and fix within Metronic if implement mode) |
| `design-polish` | User asks for a final design pass on a view or component | read view + ui-components -> improve hierarchy, spacing, copy within Metronic only; no new classes |
| `design-critique` | User asks for UX/review of a screen or flow | read view -> assess clarity, hierarchy, empty/error states -> short critique + Metronic-safe suggestions |

Use full `AGENTS.md` process for multi-file or higher-risk work.

### 3.2) Lane → Expected Artifact

Each lane should produce a specific output shape so "done" is unambiguous:

| Lane | Expected artifact on completion |
|------|--------------------------------|
| `tiny` | Code change + one verification note (lint or smoke check) |
| `explain` | Answer with evidence (file refs or reasoning) and stated caveats |
| `analyze` | Structured findings list with file paths and evidence per finding |
| `implement` | Code + tests/lints result + what changed and what was verified |
| `investigate` | Root-cause diagnosis with evidence chain (file:line, flow, reproduction) |
| `review` | Numbered findings with severity, evidence, and optional fix suggestions |
| `log-scan` | Log excerpt per issue + fix location + verification result |
| `lint-fix` | Clean lint output (or list of unfixable items with reasons) |
| `test-fix` | Green test output (or list of remaining failures with diagnosis) |
| `tenant-audit` | Checklist with pass/fail per item + grep evidence per finding |
| `execute-plan` | Phase checkboxes from `.cursor/plans/` + per-phase verification |
| `web-audit` | Persisted report + fix list + re-audit verification |
| `dependency-eval` | Adopt / adapt / reject decision with comparison evidence |
| `external-adapt` | Landing file list + adapted code + verification |
| `design-audit` | Checklist (focus, contrast, structure, assets) with pass/fail per item |

If the final output does not match the expected artifact shape, the lane is not complete.

Stop conditions for the most common lanes:

1. Conceptual question: answer from reasoning first; only add repo grounding if it changes the answer.
2. Repo-aware explain: stop after `grep` + targeted `read_file` unless schema/routes truth is still missing.
3. Small syntax/lint bug: search/read first, then run one narrowed check such as `php -l path/to/File.php`.
4. Degraded semantic tooling: skip it immediately and continue with `grep` + `read_file_cache`.

## 3.1) Skill-First Flow

For anything larger than `tiny`, use this compact sequence:

1. Clarify intent and pick the lane.
2. Design in chunks instead of guessing the whole solution at once.
3. Write a verification-aware plan.
4. Execute in bounded tasks with clear file responsibility.
5. Review with evidence before finishing.

If a matching helper already exists in `.cursor/skills/` or `.cursor/prompts/`, use it.

For multi-file work, name the target files or scopes, ownership split, no-change areas, and per-phase verification before editing.

---

## 4) Tiny-Task Fast Lane

For single-file or very small changes:

1. Restate the goal in one sentence.
2. If location is unknown, open `ai/entrypoints/INDEX.md` and one area map first.
3. Narrow target with grep/semantic search.
4. Read only the required file or line range.
5. Make minimal edit.
6. Run closest validation on the narrowed scope. For PHP syntax issues, use grep/read first and run `php -l` only on the suspected or changed file.
7. Report what changed and what was verified.

**Analyze/scan (module audit, clone, codebase understanding):** Grep (or glob) first to list files and find references; read only flagged files or line ranges, in parallel when independent (3–5 per turn); full-file read only when editing. See `ai/agent-tools-and-mcp.md` §2.8.

---

## 5) Tool Ladder (Availability + Health)

Preferred order for most tasks:

1. `grep` for exact/pattern search
2. `read_file_cache` for file content and slices
3. `gitnexus` for shared-code impact, unfamiliar architecture, and pre-commit scope checks
4. `laravel_mysql` for repo-aware routes/schema/tests/project map when the task actually needs repo structure truth
5. `semantic_code_search` when exact symbols are unknown and health says `READY`

Required baseline for this repo:

1. `grep`
2. `read_file_cache`
3. `laravel_mysql` on demand for repo-specific structure, schema, routes, or tests
4. `gitnexus` on demand for shared-code edits, unfamiliar architecture, and pre-commit scope checks

Optional:

1. `semantic_code_search`

### 5.1) Five-Tool Handoff (Codex)

Use this exact handoff so tools complement each other instead of overlapping:

1. **Exact symbol/string work** -> `grep` -> `read_file_cache` -> `laravel_mysql` only if route/schema/test context is needed.
2. **Meaning/architecture work** -> `semantic_code_search` (`index_status` first) -> `gitnexus` (`query`/`context`) -> `grep` for exact confirmation -> `read_file_cache`.
3. **Before editing Util/controller methods** -> run `gitnexus_impact` for blast radius and flag HIGH/CRITICAL risk.
4. **Before commit** -> run `gitnexus_detect_changes` to confirm affected scope and execution-flow impact.
5. **If semantic degrades** (`NOT_INDEXED`, `STALE`, `EMBEDDER_UNAVAILABLE`) -> fallback immediately to `gitnexus` + `grep` + `read_file_cache` + `laravel_mysql`.

### 5.1a) Hard Search Routing

Use this strict split unless the current host lacks the named tool:

1. Exact lookup (ID, selector, symbol, route, translation key, literal string, regex) -> `grep` first.
2. Behavior/architecture lookup with unknown symbol -> semantic search only if health/index says `READY`.
3. If semantic is `NOT_INDEXED`, `STALE`, or `EMBEDDER_UNAVAILABLE` -> skip semantic immediately and continue with `grep` + `read_file_cache`.
4. Do not use shell `rg`/`grep` for discovery while a repo-aware grep tool exists; only use shell search as last fallback when the host exposes no repo-aware grep/search tool.
5. For mixed tasks, semantic may shortlist candidates, but confirm concrete edit locations with `grep` before editing.

Session-start check:

1. Keep exact startup commands centralized in `mcp/CODEX-SETUP.md`.
2. On a cold repo-specific session, run `powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\warm-cache.ps1 -Profile startup`.
3. Run `php scripts/check-mcp-health.php` from repo root after startup, dependency changes, or MCP config changes.
4. If `gitnexus` is not `READY`, refresh it before shared-code edits or refactors.
5. If `semantic_code_search` is `READY`, use it for behavior-level discovery; otherwise fall back immediately to GitNexus + grep + read_file_cache.
6. For deep or external repo-specific work, start with: `project_map` -> `resource://composer` -> `index_status` after cache warm-up.

If a preferred tool is available but unhealthy/degraded (for example timeout, empty content, metadata-only responses, stale index, repeated failure):

1. Use the next fastest repo-aware tool for that step.
2. Keep the same split: grep for exact, semantic for meaning, read tool for content.
3. Do not loop on the failing tool; fall back immediately.
4. Note the fallback briefly when it affects confidence or speed.

Quick routing examples:

1. "What is DPO?" -> answer from reasoning; no repo setup.
2. "In this repo, where is the CMS product hero rendered?" -> `grep` -> `read_file_cache`; stop there unless layout ownership is still unclear.
3. "Fix syntax error in `app/Utils/ContactFeedUtil.php`" -> `grep`/read the failing area first -> run `php -l app/Utils/ContactFeedUtil.php` only after the file is narrowed.

### 5.2) Startup Contract

For repo-specific `implement`, `analyze`, and `execute-plan` work:

1. Startup once with `scripts/warm-cache.ps1 -Profile startup`.
2. Health-check once with `php scripts/check-mcp-health.php`.
3. Use GitNexus before shared Util/controller/model edits.
4. Use semantic search only when the health check reports `READY`.

---

## 6) What To Read In `ai/`

Read only the domain doc(s) you touch:

| Task domain | Read first |
|---|---|
| UI/Blade (Metronic project-wide) | `ai/ui-components.md` |
| Architecture conventions | `ai/laravel-conventions.md` |
| DB queries/migrations | `ai/database-map.md` |
| Auth/security/permissions | `ai/security-and-auth.md` |
| Unclear entry points / where to start reading | `ai/entrypoints/INDEX.md` first, then one area map, then the relevant domain doc(s) below |
| Existing bug-prone areas | `ai/known-issues.md` |
| MCP/tool choice | `ai/agent-tools-and-mcp.md` |
| External repo / GitHub / trending evaluation | `ai/external-adoption.md` |
| Deep research / bounded delegation | `ai/research-and-delegation.md` |
| In-app agent / product copilot evaluation | `ai/product-copilot-patterns.md` + `Modules/Aichat/README.md` |
| Aichat module (authz, capabilities, entry points) | `ai/aichat-authz-baseline.md` + `Modules/Aichat/README.md` |
| Contact Feeds (Feeds tab, providers, env) | `ai/contact-feeds.md` |
| Browser audit workflow | `ai/browser-audit-workflow.md` |
| ProjectAuto wizard workflow | `ai/projectauto-workflow-wizard.md` |
| Workflow tuning | `ai/agent-improvement.md` |
| ProjectX <-> root integration | `ai/projectx-integration.md` |

Read order for new lanes:

1. `dependency-eval`: `ai/external-adoption.md` -> `ai/agent-tools-and-mcp.md` -> relevant domain docs
2. `external-adapt`: `ai/research-and-delegation.md` -> `ai/external-adoption.md` -> closest local feature/docs
3. `product-copilot-eval`: `ai/product-copilot-patterns.md` -> `ai/security-and-auth.md` -> `ai/ui-components.md` -> `ai/aichat-authz-baseline.md` -> `Modules/Aichat/README.md`
4. Aichat code changes (authz/capabilities): `ai/aichat-authz-baseline.md` -> `Modules/Aichat/README.md` -> `ai/security-and-auth.md`
5. Contact Feeds: `ai/contact-feeds.md` -> `ai/security-and-auth.md` (if exposing tenant data)

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
