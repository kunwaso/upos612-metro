# External Adaptation Example Plan

Paths are relative to repo root unless stated.

## Design

Goal: evaluate one GitHub repository or upstream example for safe use in this repo and decide whether to `adopt`, `adapt`, or `reject`.

Repo-specific constraints:

- no tenant leaks; all tenant-facing data must honor `business_id`
- no controller-heavy new business workflows when a Util is the correct landing point
- no second UI system; all UI stays inside Metronic 8.3.3
- no blind dependency additions without compatibility, license, and rollback notes

## Public interface

- Add no new runtime API by default
- Output contract for the evaluation:
  - decision
  - why
  - landing path
  - verification
  - rollback

## Implementation steps

1. Confirm local truth with `project_map`, `resource://composer`, and relevant module manifests.
   Verify: the evaluation names the real landing modules/files in this checkout.

2. Read upstream README, license, manifests, example flows, and maintenance signal.
   Verify: the evaluation includes compatibility and license notes.

3. Classify the source as dependency, pattern-only, reference-only, or product-copilot inspiration.
   Verify: exactly one class is chosen.

4. Compare the upstream approach to repo rules for tenancy, auth, permissions, Form Requests, Utils, and Metronic UI.
   Verify: each of those areas is mentioned explicitly.

5. Produce a final `adopt`, `adapt`, or `reject` recommendation with landing path and rollback note.
   Verify: lints on changed files if any docs were updated; otherwise manual review of the final recommendation structure.

## Tests

- Manual dry-run: evaluate one library package
- Manual dry-run: evaluate one pattern-only repo
- Manual dry-run: evaluate one browser-agent/product-copilot repo

## Assumptions

- No DB migration
- No route changes
- No frontend contract change
- This plan describes evaluation work, not immediate implementation

## Done criteria

- Recommendation ends with `adopt`, `adapt`, or `reject`
- Compatibility, security, landing path, verification, and rollback are present
- Five checks from `AGENTS.md` §0.3 pass
