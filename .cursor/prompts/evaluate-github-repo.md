# Codex prompt: Evaluate a GitHub repo for adoption into UPOS

Use this prompt when you want Codex (or another agent) to inspect a GitHub repository, package, or upstream example and decide how it should land in this codebase.

---

## Task

Evaluate the referenced GitHub repository or package for adoption into this UPOS checkout.

Your answer must end with one explicit result:

- `adopt`
- `adapt`
- `reject`

Do not stay at “maybe”.

## Required workflow

1. Confirm the local repo shape first:
   - `project_map`
   - `resource://composer`
   - any relevant module-local manifest such as `Modules/Aichat/package.json` or `Modules/Projectauto/package.json`
2. Read the relevant local docs:
   - `ai/external-adoption.md`
   - `ai/laravel-conventions.md`
   - `ai/security-and-auth.md`
   - `ai/ui-components.md`
   - `ai/product-copilot-patterns.md` if the upstream repo is an in-app assistant or browser agent
3. Read the upstream README, license, manifests, release/maintenance signal, and examples.
4. Decide whether the upstream source is:
   - dependency
   - pattern-only
   - reference-only
   - product-copilot inspiration
5. Map the landing path into this repo's structure:
   - route
   - Form Request
   - Util
   - controller
   - Blade/module assets

## Mandatory output sections

1. `Decision`
2. `Type`
3. `Why`
4. `Compatibility`
5. `Security and tenant impact`
6. `Landing path`
7. `Verification`
8. `Rollback`

## Rules

- Do not recommend verbatim upstream file-tree copying.
- Do not recommend a dependency without compatibility, license, and maintenance notes.
- Do not ignore `business_id`, permissions, Form Requests, Utils, or Metronic UI constraints.
- If the repo is better as product inspiration than as a dependency, say so directly.
