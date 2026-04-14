# CLAUDE.md — Codex Project Instructions

This file bootstraps any Codex-compatible agent (GPT 5.x, Claude, etc.) with the same policy, tools, and domain knowledge that Cursor agents receive automatically.

**Read this file first, then follow the references below.**

---

## 1. Process and Policy

The canonical process doc is **`AGENTS.md`** (design → plan → execute, TDD, five checks, intent router, investigation order). Read it before any non-trivial task.

For routine work, start with **`AGENTS-FAST.md`** (condensed intent router, quick checklist, dev standards).

**Rule priority** (first match wins):

1. Direct user instruction for the current task
2. `AGENTS.md`
3. `.cursor/rules/*.mdc` (read them as reference even outside Cursor — they contain the Laravel constitution, Blade clean-architecture rules, and external-adaptation safety)
4. Domain docs in `ai/*.md`
5. Existing local code patterns

---

## 2. Domain Docs (`ai/`)

Before writing code in any domain, read the matching doc:

| Domain | Read |
|--------|------|
| Any Blade / UI | `ai/ui-components.md` |
| Controller, Util, model, route | `ai/laravel-conventions.md` |
| DB query or migration | `ai/database-map.md` |
| Routes, middleware, permissions | `ai/security-and-auth.md` |
| Bugs, debt, known traps | `ai/known-issues.md` |
| Agent tools, MCP, speed tips | `ai/agent-tools-and-mcp.md` |
| External repo / GitHub intake | `ai/external-adoption.md` |
| ProjectX hooks / root compat | `ai/projectx-integration.md` |

Full table: `AGENTS.md` §6.

---

## 3. MCP Servers

Codex reads MCP config from `~/.codex/config.toml` (Windows: `%USERPROFILE%\.codex\config.toml`).

Copy `mcp/codex-config.toml.example` into that file, replace `<repo-root>` with your checkout path, and restart Codex. Full setup: `mcp/CODEX-SETUP.md`.

Available servers (should match `.cursor/mcp.json` for parity):

| Server | Purpose |
|--------|---------|
| `laravel_mysql` | Laravel + MySQL introspection (routes, schema, project map) |
| `grep` | Guarded ripgrep search (requires `rg` on PATH) |
| `read_file_cache` | Cached workspace file reads |
| `audit_web` | Browser audit via Playwright |
| `semantic_code_search` | Local semantic search (optional; requires local Hugging Face embeddings) |
| `gitnexus` | Code intelligence graph — impact, context, rename, query |

---

## 4. Key Rules (from `.cursor/rules/`)

These rules apply in Cursor via editor-triggered `.mdc` files. Codex agents must follow them as well:

- **Blade is presentation only.** No business logic, no variable defaults, no `@php $x = $x ?? ...`. All view data comes from Controller / Util / view composer.
- **Controllers are thin.** Validate → call Util → return response. No inline business rules.
- **Business logic in `app/Utils/*Util.php`** (or module `Utils/`). No new Service/Repository layer.
- **Every tenant query must include** `->where('business_id', $business_id)`.
- **Validation via FormRequest.** No inline `$request->validate()`.
- **Permissions checked before every mutation:** `auth()->user()->can(...)`.
- **UI: Metronic 8.3.3 project-wide.** Never invent CSS classes. See `ai/ui-components.md`.
- **Models at `app/ModelName.php`** (not `app/Models/`), `$guarded = ['id']`.
- **JSON responses:** `respondSuccess()` / `respondWithError()` / `respondWentWrong()`.

Full constitution: `.cursor/rules/laravel-coding-constitution.mdc`.

---

## 5. Repo-Local Skills and Prompts

Reusable workflow helpers live in:

- `.cursor/skills/` — workflow skills (tool selection, senior-dev patterns, external adaptation, deep research)
- `.cursor/prompts/` — repeatable prompt templates

Use them when the task matches instead of re-deriving the workflow.

---

<!-- gitnexus:start -->
# GitNexus — Code Intelligence

This project is indexed by GitNexus as **upos612** (18617 symbols, 51206 relationships, 300 execution flows). Use the GitNexus MCP tools to understand code, assess impact, and navigate safely.

> If any GitNexus tool warns the index is stale, run `npx gitnexus analyze` in terminal first.

## Always Do

- **MUST run impact analysis before editing any symbol.** Before modifying a function, class, or method, run `gitnexus_impact({target: "symbolName", direction: "upstream"})` and report the blast radius (direct callers, affected processes, risk level) to the user.
- **MUST run `gitnexus_detect_changes()` before committing** to verify your changes only affect expected symbols and execution flows.
- **MUST warn the user** if impact analysis returns HIGH or CRITICAL risk before proceeding with edits.
- When exploring unfamiliar code, use `gitnexus_query({query: "concept"})` to find execution flows instead of grepping. It returns process-grouped results ranked by relevance.
- When you need full context on a specific symbol — callers, callees, which execution flows it participates in — use `gitnexus_context({name: "symbolName"})`.

## When Debugging

1. `gitnexus_query({query: "<error or symptom>"})` — find execution flows related to the issue
2. `gitnexus_context({name: "<suspect function>"})` — see all callers, callees, and process participation
3. `READ gitnexus://repo/upos612/process/{processName}` — trace the full execution flow step by step
4. For regressions: `gitnexus_detect_changes({scope: "compare", base_ref: "main"})` — see what your branch changed

## When Refactoring

- **Renaming**: MUST use `gitnexus_rename({symbol_name: "old", new_name: "new", dry_run: true})` first. Review the preview — graph edits are safe, text_search edits need manual review. Then run with `dry_run: false`.
- **Extracting/Splitting**: MUST run `gitnexus_context({name: "target"})` to see all incoming/outgoing refs, then `gitnexus_impact({target: "target", direction: "upstream"})` to find all external callers before moving code.
- After any refactor: run `gitnexus_detect_changes({scope: "all"})` to verify only expected files changed.

## Never Do

- NEVER edit a function, class, or method without first running `gitnexus_impact` on it.
- NEVER ignore HIGH or CRITICAL risk warnings from impact analysis.
- NEVER rename symbols with find-and-replace — use `gitnexus_rename` which understands the call graph.
- NEVER commit changes without running `gitnexus_detect_changes()` to check affected scope.

## Tools Quick Reference

| Tool | When to use | Command |
|------|-------------|---------|
| `query` | Find code by concept | `gitnexus_query({query: "auth validation"})` |
| `context` | 360-degree view of one symbol | `gitnexus_context({name: "validateUser"})` |
| `impact` | Blast radius before editing | `gitnexus_impact({target: "X", direction: "upstream"})` |
| `detect_changes` | Pre-commit scope check | `gitnexus_detect_changes({scope: "staged"})` |
| `rename` | Safe multi-file rename | `gitnexus_rename({symbol_name: "old", new_name: "new", dry_run: true})` |
| `cypher` | Custom graph queries | `gitnexus_cypher({query: "MATCH ..."})` |

## Impact Risk Levels

| Depth | Meaning | Action |
|-------|---------|--------|
| d=1 | WILL BREAK — direct callers/importers | MUST update these |
| d=2 | LIKELY AFFECTED — indirect deps | Should test |
| d=3 | MAY NEED TESTING — transitive | Test if critical path |

## Resources

| Resource | Use for |
|----------|---------|
| `gitnexus://repo/upos612/context` | Codebase overview, check index freshness |
| `gitnexus://repo/upos612/clusters` | All functional areas |
| `gitnexus://repo/upos612/processes` | All execution flows |
| `gitnexus://repo/upos612/process/{name}` | Step-by-step execution trace |

## Self-Check Before Finishing

Before completing any code modification task, verify:
1. `gitnexus_impact` was run for all modified symbols
2. No HIGH/CRITICAL risk warnings were ignored
3. `gitnexus_detect_changes()` confirms changes match expected scope
4. All d=1 (WILL BREAK) dependents were updated

## Keeping the Index Fresh

After committing code changes, the GitNexus index becomes stale. Re-run analyze to update it:

```bash
npx gitnexus analyze
```

If the index previously included embeddings, preserve them by adding `--embeddings`:

```bash
npx gitnexus analyze --embeddings
```

To check whether embeddings exist, inspect `.gitnexus/meta.json` — the `stats.embeddings` field shows the count (0 means no embeddings). **Running analyze without `--embeddings` will delete any previously generated embeddings.**

> Claude Code users: A PostToolUse hook handles this automatically after `git commit` and `git merge`.

## CLI

| Task | Read this skill file |
|------|---------------------|
| Understand architecture / "How does X work?" | `.claude/skills/gitnexus/gitnexus-exploring/SKILL.md` |
| Blast radius / "What breaks if I change X?" | `.claude/skills/gitnexus/gitnexus-impact-analysis/SKILL.md` |
| Trace bugs / "Why is X failing?" | `.claude/skills/gitnexus/gitnexus-debugging/SKILL.md` |
| Rename / extract / split / refactor | `.claude/skills/gitnexus/gitnexus-refactoring/SKILL.md` |
| Tools, resources, schema reference | `.claude/skills/gitnexus/gitnexus-guide/SKILL.md` |
| Index, status, clean, wiki CLI commands | `.claude/skills/gitnexus/gitnexus-cli/SKILL.md` |

<!-- gitnexus:end -->
