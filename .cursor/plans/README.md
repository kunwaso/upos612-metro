# Plan writing guide

This folder holds implementation and design plans (e.g. for features, refactors, or multi-step work). Plans should be **executable** with the repo’s build methodology (see [AGENTS.md](../AGENTS.md) §0.5 and §4).

---

## 1. Plan structure (template)

Use this structure so plans double as a spec and an execution checklist:

| Section | Purpose |
|--------|--------|
| **Design** | Goal, scope, repo-specific constraints (e.g. “UI uses `/stream` and `/regenerate`”). Resolve unknowns; for large features, note when user validation is needed before coding. |
| **Public interface** | Config keys, new/updated method signatures, return shapes. Makes contracts clear before implementation. |
| **Implementation steps** | Numbered list. Each step: what to do, which file(s), and a **Verify:** line (lints, tests, or smoke check). |
| **Tests** | Which test files and scenarios; where to use TDD (“write failing test first for step X”). |
| **Assumptions** | Explicit scope: e.g. “No DB migration”, “No route changes”, “No frontend contract change”. |
| **Done criteria** | Before calling work done: lints on changed files, relevant tests pass, five checks (AGENTS.md §0.3) and goal met. |

---

## 2. Verification per step

Every implementation step should have a **Verify:** line so the plan can be executed with AGENTS.md’s process:

- **Verify:** `php artisan test --filter=FeatureName`
- **Verify:** Read lints on `Modules/ProjectX/Utils/AIChatUtil.php`
- **Verify:** Manual smoke: send message with fabric insight, confirm tool is called

If a step has no verification, add at least: “Verify: lints on changed files.”

---

## 3. Paths and repo

- Prefer **relative paths** from repo root (e.g. `Modules/ProjectX/Config/config.php`) so the same plan works in any clone.
- If absolute paths are used, add a one-line note: “Paths relative to repo root unless stated.”

---

## 4. Scope and “no change” areas

State explicitly:

- **No DB migration** — when the feature doesn’t add or change persisted fields.
- **No route changes** — when existing routes are sufficient.
- **No frontend contract change** — when the API (e.g. SSE) stays the same and only server behavior changes.

This prevents unnecessary migration/route work and keeps scope clear.

---

## 5. Linking design and implementation

- If you have **two** plans for the same feature (e.g. design + implementation), either:
  - **Merge** into one plan: “Goal & design” then “Implementation (with Verify steps and Done criteria)”, or
  - **Cross-link**: at the top of the implementation plan add: “Design and flow: see `other_plan_name.plan.md`.”

---

## 6. Plan quality checklist (before locking)

- [ ] Every implementation step has a **Verify:** (lints, tests, or smoke).
- [ ] Paths are relative to repo root (or clearly scoped to one repo).
- [ ] Scope is explicit (migration yes/no, routes, frontend contract).
- [ ] Test section names files and scenarios; TDD is called out where desired.
- [ ] **Done** = five checks (AGENTS.md §0.3) + lints + relevant tests + goal met.

---

## 7. Generating plans (e.g. Codex)

When generating a plan, ask for:

- A **Verify:** line for each implementation step.
- Paths **relative to repo root**.
- A short **Done** section: “Lints on changed files; run tests X, Y; confirm five checks (AGENTS.md §0.3).”
- Explicit **scope**: “No DB migration”, “No route changes”, etc., when applicable.

This keeps generated plans aligned with AGENTS.md from the start.
