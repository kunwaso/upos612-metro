---
name: external-adaptation
description: Evaluate and adapt external repos, GitHub examples, and trending libraries into this codebase without copying their structure blindly. Use when the task involves upstream code, package evaluation, or pattern porting.
---

# External Adaptation

Use this skill when the task mentions:

- a GitHub repo or URL
- a trending library
- “copy this pattern from repo X”
- “should we adopt this package?”
- “port this example into UPOS”

## Core Rules

- Start with local repo truth, not upstream truth.
- Classify the source as `dependency`, `pattern-only`, `reference-only`, or `product-copilot inspiration`.
- End with `adopt`, `adapt`, or `reject`.
- Adapt the smallest useful pattern; do not copy the upstream file tree wholesale.

## Workflow

1. Read `ai/external-adoption.md`.
2. Confirm local shape with `project_map` or filesystem.
3. Read `resource://composer` and any module-local manifests when dependencies are involved.
4. Find the closest local feature or module before designing a landing path.
5. Fetch the upstream README, license, manifests, and examples.
6. Compare the upstream idea to repo conventions:
   - `business_id`
   - permissions
   - Form Requests
   - Utils
   - Metronic UI
   - module boundaries
7. Output one result:
   - `adopt`
   - `adapt`
   - `reject`

## Required Output

Every result must include:

1. Decision
2. Why
3. Landing files or modules
4. Verification
5. Rollback

## Do Not

- Do not paste upstream code verbatim when it bypasses repo conventions.
- Do not suggest a new dependency without compatibility, maintenance, license, and security notes.
- Do not route product-copilot ideas into the coding-agent workflow; send those to `ai/product-copilot-patterns.md`.
