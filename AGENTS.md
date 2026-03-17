# AGENTS.md — AI Coding Guide for This Project

**Last verified:** 2026-03-06  
**Owner:** UPOS Engineering (AI Workflow Maintainers)

This file is the complete policy reference for any AI coding agent working on this codebase.
For routine work, start with `AGENTS-FAST.md`, then use this file for full policy and edge cases.
Before writing code, read the relevant domain document in `ai/`.

Process lives here (design → plan → execute, TDD, verification, five checks). Domain reference (UI, DB, auth, conventions, known issues) lives in `ai/`. Fast execution defaults live in `AGENTS-FAST.md`.

---

## 0. Document Control

### 0.1 Rule Priority (If Instructions Conflict)

Apply the first matching rule in this order:
1. Direct user instruction for the current task
2. `AGENTS.md` (this document)
3. Cursor rules in `.cursor/rules/*.mdc` (when applied by the editor — e.g. Laravel constitution, Blade refactor)
4. Domain-specific `ai/*.md` files
5. Existing local code patterns

If a conflict still cannot be resolved safely, stop and ask the user before writing code.

<!-- start ask mode -->

### 0.1a Ask mode vs Implement mode (when environment has no mode selector)

When the environment does **not** provide a clear "Ask" vs "Agent/Implement" mode (e.g. no Ask/Agent toggle):

1. **Before doing any write/edit/delete or run command**, ask the user once at the start:
   - *"Is this **ask mode** (explain/answer only, no file changes) or **implement mode** (I may edit files and run commands)?"*
2. **Ask mode** — Answer, explain, search, and read only. Do **not** use: write, search_replace, delete, or run terminal. Proceed with explanation or investigation only.
3. **Implement mode** — Proceed with the full workflow (design → plan → execute), including file edits and commands when needed.

If the user has already stated "ask" or "implement" (or "just explain", "make the change", etc.), treat that as the mode and do not ask again. When in doubt, ask once before making any changes.

<!-- end ask mode -->

### 0.1b Fast intent router

Use the first matching lane before doing broader workflow:

| Intent | Use when | Default tool order | Notes |
|---|---|---|---|
| `tiny` | Single-file or tightly scoped request | restate goal → inspect target → edit → verify | Prefer this lane for low-risk, bounded changes. |
| `explain` | User wants understanding, not changes | grep/semantic search → read → answer | No code edits. |
| `analyze` | Audit module, clone, or understand codebase | grep first → targeted/parallel reads → full read only when editing | See ai/agent-tools-and-mcp.md §2.8. |
| `investigate` | Something is broken or unclear | grep → read → compare render/update flow → answer or fix | Use 0.4a/0.4b for “stops working” bugs. |
| `review` | User asks for a review or audit | grep/read changed area → identify findings → verify evidence | Findings first, summary second. |
| `plan` | User wants design or implementation plan | inspect repo truth → write numbered plan → list verification | Do not invent missing repo facts. |
| `execute-plan` | User attaches or references `.cursor/plans/*.plan.md` to execute or "plan from" | read plan → execute phases/tasks in order; derive steps that match the plan as written | Do **not** rewrite, restructure, or replace the plan. See .cursor/plans/README.md §7. |
| `implement` | User wants a fix or feature | inspect → numbered plan → edit → verify | Default when the user asks to make the change. |
| `log-scan` | User asks to scan/fix Laravel log, check logs, or fix issues from `storage/logs` | glob latest log → read log → parse errors → investigate → fix → verify | See 0.4e. Use implement mode. |
| `lint-fix` | User asks to fix linter errors, IDE diagnostics, or "fix lint" | Read lints (scope: path or repo) → fix each finding → re-run lints | See 0.4f. Use implement mode. |
| `test-fix` | User asks to fix failing tests or pastes test output | parse test output → locate failure → fix test or code → re-run tests | See 0.4g. Use implement mode. |
| `tenant-audit` | User asks to audit/fix tenant security, missing business_id, or route auth | grep checklist → fix or report each finding | See 0.4h. Use implement mode. |
| `known-issues-fix` | User asks to fix known-issues in an area or apply ai/known-issues.md | read known-issues for area → apply mitigations/fixes | See 0.4i. Use implement mode. |
| `full-autofix` | User says "run all autofixes", "check project", "health check", or "autofix everything" | log-scan → lint-fix → optional tenant-audit / known-issues-fix | See 0.4j. Use implement mode. |
| `design-audit` | User asks to audit a view/screen for a11y, contrast, responsive, or UI quality | read ai/ui-components.md + target Blade → checklist (focus, contrast, structure, assets) → report (and fix within Metronic if implement mode) | Scope: Metronic only; no theme change. |
| `design-polish` | User asks for a final design pass on a view or component | read view + ui-components → improve hierarchy, spacing, copy within Metronic only; no new classes | Scope: Metronic only. |
| `design-critique` | User asks for UX review of a screen or flow | read view → assess clarity, hierarchy, empty/error states → short critique + Metronic-safe suggestions | Findings first; no theme change. |

For trivial single-file or tightly scoped work, use the `tiny` lane with a **2–3 step micro-plan**: restate goal → inspect the file/area → edit and verify. Use the full long-form plan for multi-file or higher-risk work.

### 0.2 How to think and solve coding (six steps)

1. **Understand the message** - Goal, scope, constraints; explicit vs implicit.
2. **Use context** - Workspace rules, open/recent files, @-mentions, repo state; don't scan blindly.
3. **Decide what to do** - Answer / find / plan / implement; choose actions (search -> read -> edit).
4. **Narrow where to look** - Area (core vs module, layer); entry points from AGENTS.md/ai/; search/grep for exact files/symbols.
5. **Form task + objects + order** - One-line task, list of objects, order of work, constraints; ask or search if something's missing.
6. **Execute** - Read relevant code, minimal changes, check rules and patterns; iterate if needed.

### 0.2a Start from the user's description

Before searching or editing, **restate and structure the user's request** so the goal and steps are explicit. Use this as the starting point for investigation or implementation.

1. **Restate** – In one or two sentences, state: what the user wants (fix / feature / explanation), in what context (e.g. "on fabric-manager Budget tab"), and what goes wrong or what success looks like.
2. **Sequence** – If the user described a flow (e.g. "I do A, then B, then C fails"), write it as a short numbered list: Step 1 … Step 2 … Step 3 (observed result).
3. **Identify unknowns** – Note any missing details (which page, which button, which role) and resolve them via search or a short clarifying question before coding.
4. **Use this as the plan** – Let this structured description drive where you search (which IDs, which files) and what you verify. Don't guess; follow the flow in the code.

Example: User says "when im in fabric-manager/fabric/... open budget can ai chat. then i switch to setting and i close the ai chat after that when i re click again on kt_drawer_chat_toggle button but the chat drawer no respones" → Restate as: (1) On fabric-manager Budget, AI chat works. (2) User switches to Settings tab and closes the chat. (3) User clicks the chat toggle again; the drawer does not open. Then search for the toggle handler and where that button is rendered and updated.

### 0.3 Before you respond: five checks

Before you answer or stop, verify:

- **Goal** - Does this address what the user asked? (fix, feature, explanation, etc.)
- **Rules** - Does it fit AGENTS.md, .cursor rules, project conventions?
- **Evidence** - Does it match what you just read (files, search results)? No obvious contradictions?
- **Completeness** - Are you missing something obvious? (e.g. you edited the Util but didn't check the controller -> one more read.)
- **Correctness** - Any red flags? (syntax, wrong method, wrong file?)

Do not send the final response until all five checks pass. If any check fails, do one more read or edit, then re-run the checks.

### 0.4 Tools you can use when reasoning

When you reason again and need more information or another step, choose from these tools (use them instead of guessing):

**Search and discovery**
- **Semantic search** - Find code by meaning (e.g. "Where is the system prompt built?"). When the semantic code search MCP is enabled in Codex or Cursor, use its `search_code` tool for semantic/contextual codebase queries in preference to the built-in codebase index. If semantic MCP is not available, use the host's codebase or semantic search (e.g. Cursor's codebase indexing). Use it for: how something works, where something is done, and "where is X done?" queries when the exact symbol is unknown.
- **Grep** - Exact text or regex search in files. Use for: symbol names, strings, file paths, file types. Use the **grep tool** (the tool call) for pattern search; in MCP-aware clients for this repo, prefer the configured `grep` MCP server for exact/pattern search, while Cursor Chat uses its built-in Grep (may appear as "instant Grep" or "Grep (beta)"). Grep does not require codebase indexing; only semantic search uses an index. Do not run `rg`, `grep`, or similar commands in the shell — the tool avoids shell startup.
- **Glob / file find** - Find files by name or pattern (e.g. `**/*.php`, `**/ChatUtil.php`).

**Read**
- **Read file** - Open a file to get full context. Use when: you need to see the whole method, config, or view before editing or answering. When you only need a line range (for example lines 206-256), prefer a read-file tool with `offset` + `limit` if the platform supports it. For **large files** (e.g. >200 lines), use grep to find relevant sections first, then read with `offset` + `limit`; do not read the whole file for scan/audit. Do not spawn a shell just to slice a file; on Windows the shell startup cost is often slower than the read itself.
- **Grep first, then read only what's needed** - When discovering where something lives or what to edit, use grep (or grep MCP) or semantic search to narrow to candidate files and line numbers first; then read only those files or ranges. Avoid broad read passes across many files before narrowing.
- **Parallel reads** - When reading multiple independent files (e.g. for analyze/scan or module audit), issue 3–5 read calls in the same turn instead of one-by-one. Full-file read only when you are about to edit or need the whole unit; otherwise use grep + targeted reads. See ai/agent-tools-and-mcp.md §2.8.
- **If the `read_file_cache` MCP server is enabled and healthy:** use its `read_file(path, offset?, limit?)` tool for reading workspace text files. If it is unavailable or degraded, fall back to the next safest repo-aware read path and note the fallback briefly.

**Edit and write**
- **Search-replace** - Change one or all occurrences in a file. Use for: minimal, targeted edits.
- **Write** - Create or overwrite a file. Use for: new files or large replacements.
- **Delete** - Remove a file when the task requires it.

**Run and verify**
- **Run terminal command** - Run tests, artisan, composer, migrations. Use when: you need to verify behavior or run the app.
- **Read lints** - Read linter/IDE diagnostics for a file or directory. Use after edits to catch syntax or style issues.

**Plan and organize**
- **Todo list** - Track multi-step tasks (pending, in progress, done). Use for: complex or multi-file work.
- **Plan** - Write a short plan (overview, steps, todos) before implementing when the task is non-trivial.

**External**
- **Web search** - Look up docs, errors, or versions when the codebase is not enough.
- **Fetch URL** - Pull live docs, API references, or examples from a known URL. Prefer this over guessing when the answer is on a stable page.
- **Ask user** - Ask a multiple-choice or clarifying question when goal or scope is unclear.

**Platform / MCP (when available)**
- **read_file_cache MCP** - When this server is enabled and healthy, use its `read_file(path, offset?, limit?)` tool for workspace file reads. At the start of a Codex or Cursor session, call `warm_cache` once (no args) so subsequent `read_file` calls hit the disk cache and are faster. If responses are degraded (for example empty payloads, metadata-only output, repeated failures), use the next fastest repo-aware fallback and continue.
- **Semantic code search MCP** - When enabled, use its `search_code` tool for natural-language and contextual codebase queries. Treat it as this project's semantic codebase index and prefer it over the built-in codebase index when both are available. If it returns INDEX_NOT_READY or INDEX_STALE, run `index_codebase` then retry. If semantic MCP is not available, use the host's codebase/semantic search. Follow `ai/agent-tools-and-mcp.md` for when to use it vs grep MCP.
- **grep MCP** - When available in MCP-aware clients (for example Codex), use the repo-configured `grep` MCP server for exact string/regex and glob-constrained search. Use semantic/index search for higher-level “where/how is this done?” discovery, then refine with grep. Follow `ai/agent-tools-and-mcp.md` for contract details and guardrails.
- **Subagents (task)** - Delegate discrete work with a clear spec and return format. Use `explore` for broad or parallel codebase discovery, `shell` for git/artisan/composer/test work, and `generalPurpose` for multi-step search or reasoning.
- **Generate image** - Use only when the user explicitly asks for an image or visual asset.
- **Edit notebook** - Use for Jupyter notebooks or cell-by-cell notebook edits.
- **Laravel MCP / other MCP servers** - When enabled in the environment, prefer repo-aware MCP tools and resources for routes, schema, project maps, tests, and guarded edits instead of ad hoc grep or shell commands.
- **find_symbols (Laravel MCP)** - Use sparingly. Prefer one or a few broad queries (e.g. higher limit, or one search term) rather than many narrow find_symbols calls. For simple "where is string X?" or "occurrences of Y", use a single grep or semantic search instead of a long series of find_symbols. Once you know the file path, use read_file (read_file_cache) to read it once instead of calling find_symbols again for more context.

**Use for speed** - Run independent tool calls in parallel, narrow with grep (or semantic search when the exact symbol is unknown) first, then read only the files or ranges you need, use read-file `offset`/`limit` for file slices instead of shell commands, use Fetch URL for live docs, and use subagents or MCP tools when they can complete discovery or verification faster.

Use tools in sequence when one step depends on the previous (e.g. search -> read -> edit -> run tests -> read lints); when steps are independent, run multiple tools in parallel (see 0.4c). For "why doesn't X work?" follow 0.4a (finish the investigation) before editing. Don't answer from guesswork when a tool can give evidence.

### 0.4d Tool health fallback (unavailable vs degraded)

Treat tool state as one of three states:

1. **Available + healthy** — preferred output shape and stable responses
2. **Available but degraded** — timeouts, empty/partial content, metadata-only payloads, stale index loops, repeated call failures
3. **Unavailable** — tool/server not present in this environment

Fallback rule:

1. Use preferred tool when healthy.
2. If degraded or unavailable, switch immediately to the next fastest repo-aware tool for that step (search, read, verify).
3. Keep the behavior split: exact via grep, meaning via semantic/codebase, content via read tools.
4. State the fallback briefly when it materially affects speed or confidence.

### 0.4e Scan Laravel log and autofix

When the user asks to scan the latest Laravel log for issues and autofix (or similar), follow this workflow. Requires **implement mode** (edits and commands allowed).

1. **Identify latest log file** — Laravel daily logs live in `storage/logs/` with names `laravel-YYYY-MM-DD.log`. Determine the latest by:
   - Globbing `storage/logs/laravel-*.log` and choosing the file with the most recent date in the filename (or by file modification time if needed). Prefer the current date when present (e.g. `laravel-2026-03-10.log`).

2. **Read and parse for issues** — Read the latest log file (use read_file with offset/limit if very large). Focus on:
   - Lines with `[ERROR]`, `[critical]`, or stack traces (exception messages, file paths, line numbers).
   - Recurring warnings that indicate a real bug (e.g. undefined index, missing relationship).

3. **For each distinct issue** — Treat as an **investigate** task (see 0.4a): identify the code path (file:line from stack trace or context), read the relevant code, determine root cause, then apply a minimal fix. Run lints and any relevant tests after edits.

4. **Scope and safety** — Fix only issues that are clearly actionable from the log (e.g. missing null check, wrong method, typo). If the cause is ambiguous or the fix would be large/risky, report the finding and suggest a manual fix or ask the user instead of autofixing. Do not blindly change code without tracing the error to a specific location.

5. **Verification** — After fixes: run Read lints on changed files; run the closest relevant test or a quick smoke check if applicable; optionally re-check the log (or ask user to reproduce) to confirm the issue is gone.

### 0.4f Lint / diagnostics autofix

When the user asks to fix linter errors, IDE diagnostics, or "fix lint" (or similar), follow this workflow. Requires **implement mode**.

1. **Scope** — Use the path the user gave (e.g. `app/Utils`, a module, or a file). If none, run Read lints on the repo or on recently changed files.
2. **Read lints** — Call Read lints on the scope. Collect all diagnostics (syntax, undefined symbols, unused vars, type errors, PSR/style).
3. **Fix each finding** — For each diagnostic: open the file and line, apply a minimal safe fix (e.g. remove unused import, add type hint, fix undefined variable). Skip fixes that would change behavior without user confirmation (e.g. changing logic to satisfy a static analysis hint).
4. **Re-run lints** — After edits, run Read lints again on changed files. Repeat until the scope is clean or only non-auto-fixable items remain; list those for the user.

### 0.4g Failing-tests autofix

When the user asks to fix failing tests or pastes test output (e.g. `php artisan test` or filtered run), follow this workflow. Requires **implement mode**.

1. **Parse test output** — From the user's message or pasted output, identify: failed test name(s), file path, line number, and failure message (assertion or exception).
2. **Locate cause** — Read the failing test and the production code it exercises. Decide whether the failure is (a) wrong test expectation (update test) or (b) broken implementation (fix code). Do not change behavior of production code to satisfy a wrong test without user confirmation.
3. **Apply minimal fix** — Fix the test or the implementation. Prefer the smallest change that makes the test pass.
4. **Re-run tests** — Run the same test command (e.g. `php artisan test --filter=ModuleX`). If more tests fail, repeat. Report final status (all pass / remaining failures with suggestion).

### 0.4h Tenant / security audit

When the user asks to audit or fix tenant security, missing `business_id`, or route auth (e.g. "tenant audit", "check for missing business_id", "audit routes for auth"), follow this workflow. Requires **implement mode**.

1. **Checklist** — Run a defined audit (grep/semantic search as needed):
   - **Tenant:** `Model::findOrFail($id)` or `Model::find($id)` without `where('business_id', ...)` on tenant models (see `ai/database-map.md`, `ai/known-issues.md` §1.2). Routes or code that load tenant data without scoping by `business_id`.
   - **Auth:** Routes that modify state or serve tenant data but are not behind `auth` or appropriate middleware (see `routes/web.php` and `ai/security-and-auth.md`).
2. **Fix or report** — For each finding: if the fix is safe and standard (e.g. add `->where('business_id', $business_id)` before `findOrFail`, or add route to auth group), apply it. If the fix is ambiguous or risky, report the location and suggest a fix instead of editing.
3. **Verification** — After edits: run Read lints; run relevant tests if any; optionally run `php artisan route:list` to confirm middleware.

### 0.4i Known-issues–aware fix

When the user asks to fix known-issues in an area, apply `ai/known-issues.md`, or "fix issues from known-issues", follow this workflow. Requires **implement mode**.

1. **Identify area** — Use the path or module the user named (e.g. HomeController, a specific module, multi-tenant queries). If unclear, ask or default to the area referenced in the request.
2. **Read known-issues** — Open `ai/known-issues.md` and find sections that apply to that area (e.g. §1.2 business_id, §2.1 fat controllers, §2.2 dashboard, module-specific traps).
3. **Apply mitigations** — For each applicable entry: check the codebase for the described anti-pattern or violation; where the fix is clearly documented (e.g. "add business_id to query", "move logic to Util"), apply it. Where the doc only warns (e.g. "avoid adding more to this controller"), do not add new violations; optionally refactor if the user asked for a broader fix.
4. **Update known-issues** — If you fix something that was listed as a known issue, update or remove that entry in `ai/known-issues.md` so the doc stays accurate (see "When to update this document" at the top of that file).
5. **Verification** — Run Read lints on changed files; run relevant tests if any.

**General rule:** When implementing any fix in an area that appears in `ai/known-issues.md`, apply the documented mitigation or fix pattern where applicable so fixes are consistent with project policy.

### 0.4j Full autofix / health check

When the user says "run all autofixes", "check project", "health check", or "autofix everything" (or similar), run the following chain in **implement mode**. This activates log-scan, lint-fix, and optionally tenant-audit and known-issues-fix in one go.

1. **Log-scan (0.4e)** — Identify latest `storage/logs/laravel-*.log`, read and parse for errors/stack traces, fix each actionable issue, verify.
2. **Lint-fix (0.4f)** — Run Read lints on the repo (or on paths the user specified); fix each finding; re-run lints until clean or list remaining.
3. **Optional: tenant-audit (0.4h)** — If the user specified an area or module, or said "include security", run the tenant/security checklist (business_id, route auth), fix or report.
4. **Optional: known-issues-fix (0.4i)** — If the user specified an area or module, run known-issues–aware fix for that area; otherwise skip or default to "recently changed" / `app/` if no other task was given.

**Trigger phrases:** "run all autofixes", "check project", "full project health check", "autofix everything", "health check".

### 0.4a Investigation order (when finding why something doesn't work)

For "why doesn't X work?" or "Y stops working after Z", use this order so evidence drives the fix:

1. **Grep** for the exact UI identifier (ID, class, data attribute, or symbol) the user mentioned or that controls the behavior.
2. **Read** the file(s) that reference it — e.g. the script or component that attaches the handler.
3. **Grep** in views/layouts/templates for that identifier to see where the control is rendered (stable layout vs replaceable content like tab body, modal, AJAX-injected HTML).
4. **Search** (grep or semantic) for code that updates that region — e.g. `innerHTML`, `replaceWith`, Turbo, Livewire, partial fetch.
5. Compare: "Who binds the handler?" vs "Who replaces or re-renders that DOM?" If the node that had the listener is replaced, the fix is usually **event delegation** (listen on a stable parent, use `event.target.closest(...)`) or **re-bind after update**.

Prefer **grep** when you know the exact symbol; use **semantic search** when you need "where is X done?" and don't know the symbol.

Complete steps 1–5 before proposing or making a code change; the fix (e.g. event delegation or re-bind) follows from step 5.

### 0.4b Discovery recipe for "doesn't work after Y"

For bugs like "button/link doesn't work after navigation or after X", follow this checklist:

1. **Grep** for the button's ID or class to find the handler and the template that renders it.
2. **Read** the handler: when does it attach (load time vs later)? To which element (that exact node vs a parent)?
3. **See where the button is rendered**: in the global layout (same on every page) or inside content that can be replaced (tabs, modals, AJAX)?
4. **Search** for code that updates that region (e.g. `innerHTML`, `replaceWith`, Turbo, Livewire).
5. If the **update replaces the node** that had the listener, the bug is "listener attached to a node that gets replaced." Fix: **event delegation** (listen on a stable ancestor, use `event.target.closest(...)`) or re-run binding after the partial update.

### 0.4c Multi-step reasoning (refinements)

Apply these so tool use and conclusions match evidence and avoid guesswork:

- **Iterative rounds / expand context** — If a search returns only a snippet or a signature (e.g. class/function name without body), read the full file or a larger range before editing or concluding. Do not base edits or answers on partial context.
- **Parallel tool use** — When steps do not depend on each other (e.g. reading three files, grepping two areas), run multiple tools in the same turn. For analyze/scan tasks (audit, clone, understand codebase): grep first to narrow to files and line numbers, then read only the needed files or line ranges in parallel (e.g. 3–5 reads per turn); full-file read only when editing. Use sequence only when one step’s result informs the next.
- **When to delegate to a subagent** — Use `explore` for broad or parallel discovery, `shell` for command-heavy work, and `generalPurpose` for multi-step search or reasoning. Give the subagent a discrete task, what evidence to collect, and what it must return. Run verification after it completes.
- **Mandatory post-edit verification** — After every code edit, run **Read lints** on the changed file(s) before considering the task done. If lints report any diagnostics, treat them as a **lint-fix subtask**: fix (or list unfixable items) before concluding. For logic or route changes, run the relevant tests when they exist.
- **When to ask the user vs keep searching** — If one critical fact is missing (e.g. which page, which role, which branch) and search has not found it, ask one focused question before implementing. Do not guess critical scope or identity.
- **Tie fix to evidence** — When proposing a fix, reference the evidence that led to the diagnosis (file and place, e.g. “in `projectx-ai-chat.js` the handler is on a node that gets replaced”) and state how the fix follows (e.g. “→ use event delegation on a stable parent”).
- **When to use a todo list** — For 3+ distinct steps or multiple files, create a todo list and update status (pending / in progress / completed) as each step is done. Use it to avoid dropping steps and to show progress.
- **Empty or wrong search results** — If the first grep or semantic search returns nothing or the wrong area, try alternate symbols, names, or a different query (e.g. semantic if grep failed, or a different identifier) before concluding the code does not exist.
- **When to use 0.4a/0.4b vs general flow** — Use **0.4a** and **0.4b** only for “doesn’t work” / “stops working” / “no response” bugs. For feature requests (“add X”, “how do I…?”) or explanations, use the general six steps (0.2) and the tool sequence in 0.4; do not force the investigation order onto non-bug tasks. For new features (new page, CRUD, module feature), after the six steps follow **Section 4** (migration → model → form request → util → controller → view).
- **When diagnosis is still unclear** — If after investigation you have two or more plausible causes, or key evidence (e.g. logs, network) is missing, do not guess. State what you found, what is still ambiguous, and either ask one focused question or suggest a small diagnostic step (e.g. “add a console.log here and try again”) before proposing a fix.
- **After editing, check impact** — After changing a file, consider callers and related code (e.g. controller if you changed a Util, view if you changed a route). Read or run the relevant tests so you don’t miss an obvious dependency.

### 0.5 Fixed process: design → plan → execute (non-trivial work)

For non-trivial features or multi-file changes, follow this sequence. It improves how the agent solves issues by making design, plan, and verification explicit.

**When to use:** New feature (new page, CRUD, module feature), refactor touching multiple layers, or any task with 3+ distinct steps or multiple files. For small bugs or single-file edits, 0.2 + 0.4a/0.4b + five checks are enough.

For a trivial single-file task, a short numbered micro-plan is acceptable:

1. Restate the goal
2. Inspect the exact file/area
3. Edit and verify

1. **Design** — Restate goal and context (0.2a). Resolve unknowns (search or one focused question). For large features, present the design in short chunks so the user can validate before you implement. Do not start coding until the goal and scope are clear.
2. **Plan** — Write a numbered list of tasks. Each task should have: what to do, which file(s) or area, and a **verification step** (e.g. “run lints,” “run `php artisan test --filter=X`”). Use a todo list and update status as you go (0.4c). The plan should be clear enough to execute in order without guessing.
3. **Execute** — Implement in plan order. After each task (or each batch of related edits), run the verification step for that task (lints, tests). For large plans, work in batches and verify between batches so you don’t drift.
4. **TDD when adding or changing behavior** — For new business logic (Util method, new endpoint, new feature behavior): write or run a failing test first (or run existing tests to see the failure), then add minimal code to pass, then run tests again. For bug fixes: establish the failing case (reproduce or test), then fix, then verify the test or scenario passes. Prefer **evidence over claims** — don’t declare “fixed” without running the relevant test or check.
5. **Verification before completion** — Do not declare the work done until: (a) Read lints has been run on all changed files and passes (if lints report issues, treat as a lint-fix subtask per 0.4f and fix or list before concluding), (b) relevant tests have been run if they exist and pass, (c) the five checks (0.3) pass, and (d) the goal is actually met (e.g. the button works, the new page loads). If the platform supports subagents, discrete tasks can be delegated with a clear spec and verification step; the same “verify before done” rule applies per task.

---

## 1. Project Overview

**UPOS** is a multi-tenant **Point of Sale (POS) / ERP system** built on:

| Layer | Technology |
|---|---|
| Backend | Laravel 9, PHP ^8.0 |
| Modules | nwidart/laravel-modules |
| Database | MySQL — multi-tenant via `business_id` column |
| Frontend | Blade templates + **Metronic 8.3.3** (Bootstrap 5) — project-wide |
| Permissions | spatie/laravel-permission (role/permission system) |
| API Auth | Laravel Passport (OAuth2) |
| Data Tables | Yajra DataTables (AJAX-driven) |
| PDFs | mPDF |
| Excel | Maatwebsite Excel |
| Business Logic | `app/Utils/` Util classes |

### Active Modules

Essentials, Accounting, AssetManagement, Cms, Connector, Crm, Ecommerce, FieldForce,
Manufacturing, ProductCatalogue, Project, Repair, Spreadsheet, Superadmin, Woocommerce,
AiAssistance, Hms, InboxReport, CustomDashboard, Gym, ZatcaIntegrationKsa, **ProjectX**

### Repository Snapshot (This Checkout)

- Do **not** trust long-lived docs for exact module, controller, middleware, or template counts. Verify live repo shape with the filesystem and `project_map`.
- The `Modules/` directory contents vary by checkout; compare `Modules/*` against `modules_statuses.json` before referencing any module in code or UI.
- `modules_statuses.json` may list enabled modules whose folders are not present locally. Confirm availability before depending on them.

> **Note:** The entire project uses **Metronic 8.3.3**. See [Section 10](#10-metronic-833--project-wide-ui) for asset paths and UI reference.

### Key File Locations

| What | Where |
|---|---|
| Core controllers | `app/Http/Controllers/` |
| Core models | `app/` (e.g. `app/Transaction.php`) — NOT `app/Models/` |
| Business logic | `app/Utils/` |
| Middleware | `app/Http/Middleware/` |
| Core views | `resources/views/` |
| Module code | `Modules/[Name]/` |
| UI theme source (core) | Metronic HTML reference: `public/html/` |
| Metronic assets (core) | `public/assets/` — use `asset('assets/...')` in core Blade |
| UI theme source (ProjectX) | `Modules/ProjectX/Resources/html/` or `public/html/` |
| Metronic assets (ProjectX) | `public/modules/projectx/` — use `asset('modules/projectx/...')` |
| AI docs | `ai/` |
| MCP servers | `mcp/` (for example `mcp/laravel-mysql-mcp/`) |
| Helpers | `app/Http/helpers.php` |
| Routes | `routes/web.php`, `routes/api.php` |

---

## 2. Golden Rules — Non-Negotiable

These rules apply to every single task, regardless of scope.

### 2.1 Think Before You Code

**State your plan as a numbered list before touching any file.**
If you can't describe the complete end-to-end flow in steps, you don't understand the problem yet.
Only after the plan is clear should you write code.

### 2.2 Read Before You Write

Before modifying any file:
1. Read the file completely
2. Read the relevant `ai/` document for your domain
3. Check `ai/known-issues.md` for known traps in the area you're touching

### 2.3 UI — Metronic 8.3.3 Project-Wide

- Read `ai/ui-components.md` before writing any Blade markup (core or module).
- **Never invent new CSS classes.**
- **The entire project uses Metronic 8.3.3** (Bootstrap 5). All new or migrated Blade views — core (`resources/views/`) and ProjectX (`Modules/ProjectX/Resources/views/`) — use Metronic patterns from the HTML reference and `ai/ui-components.md`.
- **Legacy core views** that still use old Bootstrap/AdminLTE or other legacy classes: when you touch them, migrate to Metronic or preserve only if the task explicitly excludes migration.
- See [Section 10](#10-metronic-833--project-wide-ui) for asset paths and component reference.

### 2.4 Multi-Tenant Security

- **Every query on tenant data must include** `->where('business_id', $business_id)`
- Get business_id from: `request()->session()->get('user.business_id')`
- Verify resource ownership: use `Model::where('business_id', $business_id)->findOrFail($id)`
- **Never** do `Model::findOrFail($id)` alone on tenant models

### 2.5 Business Logic in Utils, Not Controllers

- New business logic goes into the appropriate `app/Utils/*Util.php` class
- Controllers orchestrate: validate → call Util → return response
- Do NOT add more code to already-large controller methods
- Do NOT introduce a new generic Service or Repository layer for business workflows — use Utils. Existing thin infrastructure adapters (for example mailer implementations in ProjectX) may remain when they are not a second domain layer.

### 2.5a View Data in Controller, Not Blade

- **All view data** (including defaults and derived values such as precision, step, min for inputs) **must be prepared in Controller, relevant Util/presenter logic, ViewModel when already established, or view composer.** The view only receives and renders.
- **Do not add `@php` blocks in Blade** to default variables (e.g. `$config = $config ?? helper()`) or to compute values (e.g. currency/quantity precision, step, min). If existing views contain such blocks, do **not** copy them into new or touched views — prepare the data in the controller (or Util / view composer) and pass it to the view.
- Aligns with `.cursor/rules/laravel-coding-constitution.mdc` and `blade-refactor-clean-architecture.mdc`.

### 2.5b Formatting alignment (root and modules)

- Currency, quantity, and number display must use the same source everywhere: session/business settings populated by `SetSessionData`.
- **PHP:** Root code uses `App\Utils\Util::num_f()` / `num_uf()`; module code uses injected `App\Utils\ModuleUtil`, which inherits `num_f()`, `num_uf()`, `format_date()`, and `uf_date()`.
- **Blade:** Use `@format_currency($amount)` and `@num_format($number)` for display. Do **not** hardcode `number_format(..., 2)`, `number_format(..., 4)`, or duplicate currency symbol logic in views.
- Reference the session keys and Blade directives already documented in `ai/laravel-conventions.md` §5.3 and §6.4.

### 2.5c Config — Format and locale from session only

- Currency, quantity, and date/time precision, separators, and locale come from **session** and **Business** (`SetSessionData`).
- Modules must **not** define their own currency/quantity/date precision settings in module config. Module config is for module-specific feature flags and options only.
- Use `session('business')`, `session('business.currency_precision')`, `session('currency')`, and related session values as the source of truth.

### 2.6 Always Validate — Always Use Form Requests

- Never use `$request->input()` raw in queries
- All user input must go through a Form Request class
- See `ai/laravel-conventions.md` for Form Request pattern

### 2.7 Always Check Permissions

Before every create / update / delete action:
```php
if (!auth()->user()->can('module.action')) {
    return $this->respondUnauthorized(__('messages.unauthorized_action'));
}
```

### 2.8 Solve Completely

- No half-fixes
- No `// TODO` left in committed code
- If you can't complete a feature, say so and explain why — don't leave broken state
- Test every edge case before declaring done

### 2.9 Never Break What Exists

- Read the test suite (`tests/`) before touching shared Util methods
- Do not edit existing migrations — create new ones
- Do not modify `pos_boot()` in helpers.php
- Do not edit `modules_statuses.json` manually

### 2.10 Use Existing Response Helpers

For AJAX/JSON responses, always use:
```php
$this->respondSuccess(__('lang_v.success'))
$this->respondWithError(__('messages.error'))
$this->respondWentWrong($exception)
$this->respondUnauthorized()
```

---

## 3. UI Development Workflow

Follow these steps for any **new or migrated** Blade view (core or module). The whole project uses **Metronic 8.3.3**.

1. **Identify** the required UI elements: card, table, form, modal, badge, etc.
2. **Open** `ai/ui-components.md` — find the exact Metronic component you need
3. **Find** the closest matching HTML reference in `public/html/` (core) or `Modules/ProjectX/Resources/html/` (see Section 10 table)
4. **Find** an existing Blade view with similar layout (e.g. core `resources/views/` or `Modules/ProjectX/Resources/views/`)
5. **Build** using only documented Metronic/Bootstrap 5 class patterns — no custom invented classes
6. **Check** the result matches the Metronic HTML reference; use correct asset paths: core `asset('assets/...')`, ProjectX `asset('modules/projectx/...')`

**When rebuilding a page to match a reference template** (e.g. from `public/html/account/` or another theme HTML): **(1) Layout match first** — copy the reference structure into Blade with static/placeholder content so the layout is complete and matches; **(2) Then add controller data** — wire form action, submit, and controller variables into the view and partials. See `.cursor/rules/ui-layout-first-then-data.mdc` for the full rule.

### 3.1 Page Template (Metronic)

Use the Metronic layout and card structure from `public/html/layouts.html` and `public/html/widgets/` (or the same paths under `Modules/ProjectX/Resources/html/`). Example structure:

```blade
@extends('layouts.app')

@section('title', __('lang_v.page_title'))

@section('content')
    <!-- Breadcrumb + toolbar per Metronic html/toolbars.html -->
    <!-- Main content card: card, card-header, card-body from html/widgets/ -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Section Title</h3>
        </div>
        <div class="card-body">
            <!-- Content -->
        </div>
    </div>
@endsection

@section('javascript')
<script>
    // Page-specific JS
</script>
@endsection
```

Use asset paths: **core** `asset('assets/...')` (Metronic in `public/assets/`); **ProjectX** `asset('modules/projectx/...')`. See Section 10.

---

## 4. Feature Development Workflow

For every new feature or bug fix, follow this sequence. For non-trivial work (multi-file, new feature), use the fixed process in **0.5** (design → plan → execute, TDD, verification) first; then apply the steps below.

```
1. READ  →  Understand the relevant route, controller, model, migration, and Blade view
2. PLAN  →  Write a numbered step-by-step plan (route → validation → controller → util → view)
3. CHECK →  Review ai/ docs for the domain (ui, database, security, conventions)
4. BUILD →  Implement top-down:
            migration → model → form request → util method → controller → blade view
5. VERIFY → Confirm every edge case is handled; no unhandled exceptions; no TODOs

Do not add or change any Blade form field or controller/Util persistence until the migration
that adds or changes the corresponding column(s) exists (and is run, or is part of the same change set).
For settings and form-backed features: every persisted field must have a migration column.
```

### 4.1 Adding a New CRUD Feature

```
Step 1: Migration
  → php artisan make:migration create_my_table --create=my_table
  → Always include business_id + foreign key
  → For settings/forms: add a column for every field the Form Request will persist (cross-check rules() vs table columns)

Step 2: Model
  → Create app/MyModel.php (not app/Models/)
  → Namespace: App
  → Use $guarded = ['id']
  → Add relationships

Step 3: Form Request
  → app/Http/Requests/StoreMyModelRequest.php
  → authorize() checks permission
  → rules() validates all inputs

Step 4: Util Method
  → Add to relevant app/Utils/XxxUtil.php
  → Accept $business_id as first parameter
  → Always scope queries by business_id

Step 5: Controller
  → Inject Util via constructor
  → Thin controller: validate → util → respond
  → Check permissions before every mutation

Step 6: Route
  → Add to routes/web.php inside auth + SetSessionData middleware group
  → Follow naming: module.index, module.create, module.store, etc.

Step 7: Blade View
  → Follow ai/ui-components.md (Metronic)
  → Use Metronic card/layout patterns
  → @csrf on all forms
  → @can gates on action buttons
```

### 4.2 Adding a New Module Feature

```
Step 1: Scaffold  →  php artisan module:make MyFeature
Step 2: Register  →  modules_statuses.json auto-updated
Step 3: Routes    →  Modules/MyFeature/Routes/web.php
Step 4: Controller → Modules/MyFeature/Http/Controllers/
Step 5: Views     →  Modules/MyFeature/Resources/views/
Step 6: Migrations → Modules/MyFeature/Database/Migrations/
Step 7: Run       →  php artisan module:migrate MyFeature
```

- **Module controllers:** Extend `App\Http\Controllers\Controller` so response helpers and `getMpdf()` are available.
- **Controller reuse:** When a module CRUD flow mirrors root behaviour, copy the existing root/module controller pattern first and keep the controller orchestration-focused (validate → Util → response) instead of inventing a new structure.
- **Module business logic:** Put module-specific business logic in module `Utils` (for example `Modules/ProjectX/Utils/*Util.php`). Use injected `App\Utils\ModuleUtil` for shared helpers such as `num_f()`, `num_uf()`, `format_date()`, `uf_date()`, and `getModuleData()`.
- **Module models:** Keep module-specific models in `Modules/ModuleName/Entities/` (or `app/` only when the model is truly shared). Use the same conventions as root: `$guarded = ['id']`, relationships, and `business_id` scoping.
- **No new generic Services/Repositories:** Do not introduce a separate Service or Repository layer in modules for business workflows. Thin adapter/integration classes are acceptable when they wrap infrastructure only.

When the module must **extend core (root) behaviour** (e.g. product form, sales flow, home redirect), use **hooks** (`getModuleData`) and **view composers** only — do not add module-specific code to root controllers or views. For ProjectX, see [Section 10.6](#106-projectx-integration-with-root-core).

---

## 5. File Map

```
app/
├── Http/
│   ├── Controllers/          ← Core controllers (verify exact count via project_map)
│   ├── Middleware/           ← Middleware files (verify exact count via project_map)
│   └── helpers.php           ← Global helper functions
├── Utils/                    ← Business logic (verify exact count via filesystem/project_map)
├── [Model].php               ← Models at root of app/
├── Providers/
├── Events/ + Listeners/
└── Rules/

Modules/                      ← nwidart modules present in this checkout
routes/
├── web.php                   ← Core web routes
└── api.php                   ← API routes

resources/views/              ← Core Blade views
├── layouts/
│   └── partials/
│       ├── header.blade.php
│       └── sidebar.blade.php
└── components/               ← Reusable Blade components

database/
├── migrations/               ← Migration files (verify exact count via filesystem/project_map)
├── seeders/
└── factories/

public/assets/                ← Metronic 8.3.3 assets for core (CSS, JS, media, plugins); use asset('assets/...')
public/html/                  ← Metronic 8.3.3 HTML reference for core Blade (copy of theme HTML)

Modules/ProjectX/             ← Metronic 8.3.3 (project-wide)
├── Resources/assets/         ← Metronic source for ProjectX
├── Resources/html/           ← Metronic HTML reference (or use public/html/)
├── Resources/views/          ← ProjectX Blade views
├── Http/Controllers/         ← ProjectX controllers
├── Utils/                    ← ProjectX business logic
└── Routes/                   ← ProjectX routes
public/modules/projectx/      ← Published Metronic assets for ProjectX; use asset('modules/projectx/...')

ai/                           ← AI reference documents (this folder)
├── ui-components.md          ← ALL UI patterns
├── laravel-conventions.md    ← Coding conventions
├── database-map.md           ← Models and DB schema
├── security-and-auth.md      ← Auth, middleware, permissions
├── known-issues.md           ← Bugs, debt, traps
├── agent-improvement.md      ← Workflow improvement (reasoning, preferences; what’s in-repo vs training)
├── agent-tools-and-mcp.md    ← Tool choice, MCP usage, and speed guidance for agents
└── projectx-integration.md   ← ProjectX hooks, view composers, root compatibility

mcp/                          ← MCP servers and related docs
├── laravel-mysql-mcp/        ← Laravel + MySQL MCP server (tools, resources, prompts, safety docs)
├── grep-mcp/                 ← Guarded ripgrep search (exact/regex; no index)
├── read-file-cache-mcp/      ← Cached line-based workspace file reads
└── semantic-code-search-mcp/ ← Local semantic code search (optional; requires index)

.cursor/rules/                 ← Cursor rule files (*.mdc); applied by editor when relevant
├── laravel-coding-constitution.mdc   ← Blade/view-data separation, validation, testing
├── blade-refactor-clean-architecture.mdc
└── (other project-specific rules)
```

---

## 6. How to Use the ai/ Folder

**What `ai/` is for:** `ai/` holds **domain reference** — project-specific knowledge the agent must read before writing code in that domain. It does **not** define the process (that lives in AGENTS.md: six steps, design → plan → execute, five checks). Use `ai/` to answer “how does this project do X?” and “what patterns and traps exist here?” so the agent builds in line with the codebase.

Fast path: for routine tasks, start with `AGENTS-FAST.md`, then read only the domain doc(s) below for the area you are touching.

Always read the relevant document before writing code in that domain:

| Domain | Read This |
|---|---|
| Building any Blade view / UI | `ai/ui-components.md` |
| Structuring a controller, Util, model, or route | `ai/laravel-conventions.md` |
| Writing any database query or migration | `ai/database-map.md` |
| Adding/modifying routes, middleware, permissions | `ai/security-and-auth.md` |
| Working on any existing file or fixing bugs | `ai/known-issues.md` |
| Improving agent workflow (reasoning, preferences, minimize tool calls; what works in-repo vs training-only) | `ai/agent-improvement.md` |
| Agent tools, MCP, or speeding up answers and implementation | `ai/agent-tools-and-mcp.md` |
| Formatting (currency, quantity, numbers) in root or modules | `ai/laravel-conventions.md` §5.3 and §6.4; modules follow the same session-driven rules via `ModuleUtil` |
| ProjectX or any module extending core (root) via hooks/views | [Section 10.6](#106-projectx-integration-with-root-core) and `ai/projectx-integration.md` |

When you discover a new trap or fix something already documented in `ai/known-issues.md`, update that file (see "When to update this document" at the top of `ai/known-issues.md`).

---

## 7. Common Commands

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan permission:cache-reset

# Migrations
php artisan migrate
php artisan migrate --path=Modules/ModuleName/Database/Migrations

# Modules
php artisan module:make ModuleName
php artisan module:enable ModuleName
php artisan module:disable ModuleName
php artisan module:migrate ModuleName

# Composer
composer dump-autoload

# Artisan helpers
php artisan tinker
php artisan route:list --name=sell
```

---

## 8. Development Standards at a Glance

Quick condensed version: `AGENTS-FAST.md` sections 6–8.  
This table is a summary only; deep rules remain in Sections 2, 3, 4, and 10 of this file.

| Topic | Rule |
|---|---|
| Model location | `app/ModelName.php` — not `app/Models/` |
| Model namespace | `namespace App;` |
| Mass assignment | `protected $guarded = ['id']` |
| Tenant isolation | Every query: `->where('business_id', $business_id)` |
| Validation | Form Request classes — no inline `$request->validate()` |
| Permissions | `auth()->user()->can('module.action')` before every mutation |
| Business logic | `app/Utils/*Util.php` — inject via controller constructor |
| Formatting | Root uses `Util::num_f()` / `num_uf()`; modules use injected `ModuleUtil`; Blade uses `@format_currency()` / `@num_format()` |
| Module controllers | Extend `App\Http\Controllers\Controller`; keep controller flow validate → Util → response and use response helpers |
| Module business logic | Module `Utils` + injected `ModuleUtil`; do not add a new generic Service/Repository layer |
| Module models | Keep in module `Entities` or `app/` if shared; use `$guarded = ['id']`, relationships, and `business_id` scoping |
| Format / locale config | Precision, separators, and locale come from session/business settings, not module config |
| JSON responses | `respondSuccess()` / `respondWithError()` / `respondWentWrong()` |
| UI classes | Metronic 8.3.3 project-wide — use only documented Metronic/Bootstrap 5 patterns |
| CSRF | `@csrf` on every form |
| Output escaping | `{{ }}` always — `{!! !!}` only for trusted sanitized content |
| Translations | `__('lang_v.key')` — never hardcode UI strings |
| New modules | `php artisan module:make ModuleName` |
| Database indexing | Index all `business_id`, `type`, `status`, `contact_id` columns |

---

## 9. Before Calling Any Feature "Done"

Run through this checklist:

- [ ] Plan was stated as numbered steps before coding
- [ ] `ai/` documents were read for domains touched
- [ ] All new routes are behind `auth` + `SetSessionData`
- [ ] All queries include `business_id` scope
- [ ] All mutations check `auth()->user()->can()`
- [ ] All forms have `@csrf`
- [ ] Validation is in Form Request classes
- [ ] UI uses Metronic 8.3.3 only (see ai/ui-components.md and Section 10)
- [ ] Response helpers are used for AJAX endpoints
- [ ] No hardcoded strings — all text uses translation keys
- [ ] No `console.log()` or `dd()` or `dump()` left in code
- [ ] No TODO comments left in delivered code
- [ ] Edge cases are handled (empty states, missing data, permission denied)
- [ ] `ai/known-issues.md` was checked for traps in the modified area
- [ ] If a documented known issue was fixed or a new trap was discovered, `ai/known-issues.md` was updated (see that file for how)
- [ ] Asset paths and Metronic reference followed (Section 10)
- [ ] Every new or changed persisted field (including settings/form fields) has a migration that adds or alters the column; migration has been run (or documented to run)

### 9.1 Minimal Test Matrix (Run Based on Change Type)

| Change Type | Minimum Verification |
|---|---|
| Route/controller/util logic | `php artisan test --filter=<FeatureOrUnitName>` (or closest existing tests) + manual endpoint smoke test |
| Request validation / permissions | Verify allowed + denied paths (403/unauthorized behavior) for at least one role each |
| Database migration / schema | `php artisan migrate --pretend` (review SQL) + run migration in local DB + rollback check when possible |
| Blade/UI changes (any) | Visual check against `public/html/` or `Modules/ProjectX/Resources/html/`; use Metronic classes only; core `asset('assets/...')`, ProjectX `asset('modules/projectx/...')` |
| Multi-tenant data access | Confirm all tenant queries include `where('business_id', $business_id)` and cross-business access fails |
| Module wiring/autoload | `composer dump-autoload` + `php artisan module:enable ModuleName` (if needed) + route smoke test |
| Caches/permissions side effects | Run `php artisan permission:cache-reset` when roles/permissions are touched |

If automated tests are missing in the touched area, document the manual checks performed in the PR/task notes.

---

## 10. Metronic 8.3.3 — Project-Wide UI

**The entire project uses Metronic 8.3.3** (Bootstrap 5). Core Blade views and all modules (including ProjectX) use Metronic patterns.

### 10.1 Asset Paths

**Core (root) — Metronic in `public/`:**
- **HTML reference:** `public/html/` (copy of Metronic 8.3.3 UI HTML)
- **Assets:** `public/assets/` (CSS, JS, images, plugins)

In **core** Blade views and layouts, use:
```blade
{{ asset('assets/css/style.bundle.css') }}
{{ asset('assets/plugins/global/plugins.bundle.js') }}
{{ asset('assets/media/...') }}
```

**ProjectX module:**  
Source: `Modules/ProjectX/Resources/assets/`  
Published to: `public/modules/projectx/`

```blade
{{-- CORRECT — ProjectX Metronic assets --}}
{{ asset('modules/projectx/css/style.bundle.css') }}
{{ asset('modules/projectx/plugins/global/plugins.bundle.js') }}
{{ asset('modules/projectx/media/logos/default-small.svg') }}

{{-- WRONG — extra assets/ segment --}}
{{ asset('modules/projectx/assets/media/...') }}
```

After modifying ProjectX `Resources/assets/`, re-publish:
```bash
php artisan vendor:publish --tag=projectx-assets --force
```

### 10.2 HTML Component Reference (Project-Wide)

Before writing any Blade markup, find the matching component:

- **Core (root) views:** `public/html/` (same structure as below: layouts, dashboards, forms, etc.)
- **ProjectX:** `Modules/ProjectX/Resources/html/` or `public/html/`

| Need | Look in (`public/html/` or `Modules/ProjectX/Resources/html/`) |
|------|------------------------------------------------------------------|
| Page layouts, sidebars, headers | `html/layouts.html`, `html/asides.html`, `html/toolbars.html` |
| Dashboard cards & charts | `html/dashboards/*.html` |
| Data tables & listings | `html/apps/ecommerce/sales/listing.html`, `html/apps/customers/list.html` |
| Forms, inputs, selects, editors | `html/forms/*.html`, `html/forms/editors/*.html` |
| Modals & popups | `html/utilities/modals/**/*.html` |
| Wizards & steppers | `html/utilities/wizards/*.html` |
| Cards, statistics, KPI widgets | `html/widgets/statistics.html`, `html/widgets/mixed.html` |
| User profiles & account pages | `html/account/*.html`, `html/pages/user-profile/*.html` |
| Authentication pages | `html/authentication/**/*.html` |
| Invoices | `html/apps/invoices/**/*.html` |
| Chat | `html/apps/chat/*.html` |
| Search overlays | `html/utilities/search/*.html` |

### 10.3 UI Workflow

1. **IDENTIFY** — What UI element do you need? (card, table, form, modal, widget)
2. **FIND** — Open the matching HTML reference in `public/html/` (core) or `Modules/ProjectX/Resources/html/` (ProjectX)
3. **COPY** — Use the exact HTML structure, classes, and data attributes
4. **ADAPT** — Replace static text with `{{ }}`, add `@csrf`, wire routes with `route()`
5. **VERIFY** — Use correct asset paths: core `asset('assets/...')`, ProjectX `asset('modules/projectx/...')`; no invented classes

### 10.4 Allowed Class Patterns (Metronic / Bootstrap 5 only)

| Element | Classes |
|---------|---------|
| Cards | `card`, `card-flush`, `card-body`, `card-header`, `card-title`, `card-toolbar` |
| Tables | `table`, `table-row-dashed`, `table-row-gray-300`, `align-middle`, `gs-0`, `gy-4` |
| Buttons | `btn`, `btn-primary`, `btn-light-primary`, `btn-sm`, `btn-icon` |
| Badges | `badge`, `badge-light-success`, `badge-light-danger`, `badge-light-warning` |
| Forms | `form-control`, `form-select`, `form-check`, `form-label`, `form-control-solid` |
| Icons | `ki-duotone ki-{name}` with `<span class="pathN"></span>` children |
| Modals | `modal`, `modal-dialog`, `modal-content`, `modal-header`, `modal-body` |
| Grid | `row`, `col-md-6`, `col-lg-4`, `g-5`, `g-xl-10` (Bootstrap 5 grid) |
| Text | `text-gray-900`, `fw-bold`, `fw-semibold`, `fs-6`, `fs-7` |

### 10.5 What NOT to Do

- **Never** invent new CSS classes — only use what exists in Metronic (`style.bundle.css` or project Metronic build)
- **Never** use native HTML5 date/time inputs (`type="date"`, `type="time"`, `type="datetime-local"`). Use Metronic Flatpickr or Daterangepicker per `Resources/html/`.
- **Never** add an extra `assets/` segment: `asset('modules/projectx/assets/...')` is wrong
- **Never** add module-specific logic to root controllers or root views — use [hooks and view composers](#106-projectx-integration-with-root-core) only (ProjectX).

### 10.6 ProjectX integration with root (core)

When ProjectX needs to change or extend **root** behaviour (core controllers, core views, or core flows):

- **Do** integrate via **hooks and view composers** only:
  - **Hooks:** Implement methods in `Modules\ProjectX\Http\Controllers\DataController` that the root calls via `ModuleUtil::getModuleData()` (e.g. `after_product_saved`, `before_product_deleted`, `after_sale_saved`, `user_permissions`, `modifyAdminMenu`). The root must not contain ProjectX-specific logic.
  - **View composers:** In `ProjectXServiceProvider`, register view composers for **core** view names (e.g. `product.create`, `product.edit`) to inject ProjectX-related variables. Core views use null coalescing (e.g. `$projectx_enabled ?? false`) so they work with or without the module.
- **Do not** add ProjectX-specific code to root controllers (e.g. `ProductController`, `SellPosController`, `HomeController`) or to root views beyond optional null-coalesced variables and documented extension points. This keeps the module installable on any “same root base” without patching core files.

Reference: `ai/projectx-integration.md` for the stable hooks/view-composer pattern and root compatibility checklist.
