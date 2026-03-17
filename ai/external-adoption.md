# External Repo and GitHub Adoption

Read this before recommending or adapting code from GitHub, a trending list, a blog example, or a third-party package.

This repo is multi-tenant, Laravel 9, Metronic 8.3.3, and Utils-first. External code must be adapted to **this** shape rather than copied on its own terms.

---

## 1. Decide the Source Type First

Every external source must be classified before implementation:

| Type | Meaning | Default outcome |
|---|---|---|
| `dependency` | Add a Composer or npm package | Adopt only after compatibility, license, and security checks pass |
| `pattern-only` | Borrow the idea or flow, not the package/runtime | Usually `adapt` |
| `reference-only` | Read for inspiration; no direct code or dependency reuse | Usually `reject` for direct integration |
| `product-copilot inspiration` | In-app assistant or UI-agent pattern | Route to `ai/product-copilot-patterns.md` |

Finish every evaluation with one explicit result: **`adopt`**, **`adapt`**, or **`reject`**.

---

## 2. Confirm Local Truth Before Upstream Truth

Before reading the external repo deeply:

1. Confirm the live checkout shape with `project_map` or filesystem.
2. Read `resource://composer` to understand the current PHP/Laravel dependency surface.
3. Check module-local manifests when JS or Vite are involved:
   - `Modules/Aichat/package.json`
   - `Modules/Projectauto/package.json`
4. Read the closest local docs for the domain:
   - `ai/laravel-conventions.md`
   - `ai/database-map.md`
   - `ai/security-and-auth.md`
   - `ai/ui-components.md`

Do not decide landing files, modules, or dependencies until local truth is clear.

---

## 3. Upstream Intake Checklist

For every repo or package, gather:

1. README and setup model
2. License
3. Release cadence or recent maintenance signal
4. Composer/package manifests
5. Security model and secret-handling assumptions
6. Framework/runtime assumptions
7. Example integration path

If any of these are missing for a dependency proposal, default to `adapt` or `reject` rather than `adopt`.

---

## 4. Compatibility Checklist for This Repo

### 4.1 Laravel / PHP / Build

- Compatible with Laravel 9 and current PHP support expectations
- Does not require replacing the existing module structure
- Fits the current asset/build layout; prefer module-local JS/package landing over adding global root-side build assumptions

### 4.2 Tenant / Security

- All tenant-facing reads and writes can be scoped by `business_id`
- Permissions can be enforced with current Spatie patterns
- Web actions respect CSRF and auth middleware
- Any AI or assistant flow has PII, approval, and audit boundaries

### 4.3 Architecture

- Reusable business logic can live in `app/Utils/` or module `Utils/`
- Validation can live in Form Requests
- Controllers stay orchestration-focused
- UI can be rendered with Metronic 8.3.3 patterns
- Root/core behavior is not polluted when a hook, composer, or module boundary is the better landing point

### 4.4 Operational Risk

- Rollback is obvious
- Dependency removal is possible if adoption fails
- No unsafe global script injection or hidden background behavior

---

## 5. Landing Rules

### 5.1 If the result is `adopt`

- Name the package and exact landing surface.
- State whether it lands in root Composer, a module-local package manifest, or nowhere global.
- List compatibility, security, and rollback notes.

### 5.2 If the result is `adapt`

- Copy the **pattern**, not the file tree.
- Start from the closest local example.
- Map the final design into:
  - route
  - Form Request
  - Util
  - controller
  - Blade/module assets

### 5.3 If the result is `reject`

- State the blocker clearly:
  - incompatible stack
  - license risk
  - poor maintenance
  - unsafe security model
  - wrong landing shape for this repo

---

## 6. Special Rule for In-App Agents

If the external source is about browser agents, assistants, or natural-language UI control, do **not** treat it as a default coding-agent dependency.

Instead:

1. Route to `ai/product-copilot-patterns.md`
2. Keep the first rollout to one module and one safe use case
3. Require human approval for any state-changing action
4. Define tenant, auth, PII, and audit boundaries first

---

## 7. Required Output Format

Every external intake result should end with:

1. `Decision:` adopt / adapt / reject
2. `Why:` the minimum evidence that drove the decision
3. `Landing path:` files, modules, or packages that would change
4. `Verification:` tests, lints, manual checks
5. `Rollback:` how to back out safely

If you cannot produce all five, the evaluation is incomplete.
